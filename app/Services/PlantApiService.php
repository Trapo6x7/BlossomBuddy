<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Plant;
use App\Models\BackfillState;
use App\Services\PlantTranslationService;
use App\Exceptions\ApiRateLimitException;

class PlantApiService implements PlantApiServiceInterface
{
    private PlantTranslationService $translationService;

    public function __construct(PlantTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    public function getAllPlants(): array
    {
        $apiKey = config('services.plant_api.key');
        $response = Http::get('https://perenual.com/api/v2/species-list', [
            'key' => $apiKey,
            'page' => 1
        ]);
        
        return $response->json('data') ?? [];
    }

    public function getPlantData(string $commonName): ?array
    {

        $apiKey = config('services.plant_api.key');
        $detailsApiUrl = config('services.plant_api.details_url');
        // 1. Recherche l'id de la plante
        $searchUrl = 'https://perenual.com/api/v2/species-list';
        $response = Http::get($searchUrl, [
            'key' => $apiKey,
            'q' => $commonName
        ]);
        $plants = $response->json('data');
        $id = null;
        if (is_array($plants)) {
            foreach ($plants as $plant) {
                if (strtolower($plant['common_name'] ?? '') === strtolower($commonName)) {
                    $id = $plant['id'];
                    break;
                }
            }
        }
        // 2. Appelle l'API de détails avec l'id
        if ($id) {
            $detailsUrl = str_replace('{id}', $id, $detailsApiUrl);
            $detailsResponse = Http::get($detailsUrl, [
                'key' => $apiKey
            ]);
            return $detailsResponse->json();
        }
        return null;
    }


    public function updatePlantsFromApi(): void
    {
        $apiKey = config('services.plant_api.key');
        $detailsApiUrl = config('services.plant_api.details_url');
        if (!$detailsApiUrl) {
            throw new \Exception('URL_DE_PLANTAPI non définie dans .env');
        }

        // Récupérer ou créer l'état du backfill
        $backfillState = BackfillState::getOrCreate('plants_backfill');
        
        // Si le backfill précédent était terminé, on le remet à zéro
        if ($backfillState->is_completed) {
            $backfillState->reset();
        }

        $page = $backfillState->last_page + 1; // Reprendre à la page suivante
        $hasMore = true;
        $plantsProcessed = 0;
        
        \Illuminate\Support\Facades\Log::info("🔄 Reprise du backfill à la page {$page}");
        
        try {
            while ($hasMore) {
                \Illuminate\Support\Facades\Log::info("📄 Traitement de la page {$page}");
                
                $response = Http::get('https://perenual.com/api/v2/species-list', [
                    'key' => $apiKey,
                    'page' => $page
                ]);
                
                // Vérification du rate limit
                if ($response->failed() || isset($response->json()['X-RateLimit-Exceeded'])) {
                    // Sauvegarder l'état avant de lever l'exception
                    $backfillState->updateCheckpoint($page - 1, null, [
                        'last_error' => 'Rate limit exceeded',
                        'interrupted_at' => now()->toISOString()
                    ]);
                    throw new ApiRateLimitException($response);
                }
                
                $plants = $response->json('data') ?? [];
                
                // Debug : affiche la réponse complète pour voir les erreurs
                if ($page === 1) {
                    \Illuminate\Support\Facades\Log::info('API Response page 1:', $response->json());
                }
                
                $hasMore = count($plants) > 0;
                
                if (!$hasMore) {
                    // Plus de données, marquer comme terminé
                    $backfillState->markCompleted();
                    \Illuminate\Support\Facades\Log::info("✅ Backfill terminé à la page {$page}");
                    break;
                }
                
                foreach ($plants as $plantData) {
                    $id = $plantData['id'] ?? null;
                    if ($id) {
                        try {
                            $detailsUrl = str_replace('{id}', $id, $detailsApiUrl);
                            $detailsResponse = Http::get($detailsUrl, [
                                'key' => $apiKey
                            ]);
                            
                            if ($detailsResponse->failed()) {
                                \Illuminate\Support\Facades\Log::warning("❌ Erreur lors de la récupération des détails pour la plante ID {$id}");
                                continue;
                            }
                            
                            $details = $detailsResponse->json();
                            $image = $details['default_image'] ?? [];

                            $plant = Plant::updateOrCreate(
                                ['common_name' => $details['common_name'] ?? ''],
                                [
                                    'scientific_name' => json_encode($details['scientific_name'] ?? []),
                                    'family' => $details['family'] ?? '',
                                    'type' => $details['type'] ?? '',
                                    'cycle' => $details['cycle'] ?? '',
                                    'watering' => $details['watering'] ?? '',
                                    'watering_general_benchmark' => $details['watering_general_benchmark'] ?? [],
                                    'description' => $details['description'] ?? '',
                                    'image_url'     => $image['original_url'] ?? '',
                                    'thumbnail_url' => $image['thumbnail'] ?? '',
                                    'medium_url'    => $image['regular_url'] ?? '',
                                    'regular_url'   => $image['regular_url'] ?? '',
                                    'license' => isset($image['license']) ? intval($image['license']) : null,
                                    'license_name'  => $image['license_name'] ?? '',
                                    'license_url'   => $image['license_url'] ?? '',
                                ]
                            );
                            
                            // 🌍 Traduction automatique immédiate pour chaque nouvelle plante
                            if (!$plant->french_name) {
                                $this->translationService->translatePlant($plant);
                            }
                            
                            $plantsProcessed++;
                            
                            // Mettre à jour le checkpoint tous les 10 éléments pour éviter trop d'écritures
                            if ($plantsProcessed % 10 === 0) {
                                $backfillState->updateCheckpoint($page, $id, [
                                    'plants_processed_this_session' => $plantsProcessed
                                ]);
                            }
                            
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("❌ Erreur lors du traitement de la plante ID {$id}: " . $e->getMessage());
                            continue;
                        }
                    }
                }
                
                // Sauvegarder l'état après chaque page
                $backfillState->updateCheckpoint($page, null, [
                    'plants_processed_this_session' => $plantsProcessed,
                    'last_successful_page' => $page
                ]);
                
                $page++;
                
                // Petite pause pour éviter de surcharger l'API
                usleep(100000); // 0.1 seconde
            }
            
        } catch (\Exception $e) {
            // Sauvegarder l'état en cas d'erreur
            $backfillState->updateCheckpoint($page - 1, null, [
                'last_error' => $e->getMessage(),
                'interrupted_at' => now()->toISOString(),
                'plants_processed_this_session' => $plantsProcessed
            ]);
            
            \Illuminate\Support\Facades\Log::error("❌ Erreur lors du backfill: " . $e->getMessage());
            throw $e;
        }
    }
}
