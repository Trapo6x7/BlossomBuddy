<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Plant;
use App\Exceptions\ApiRateLimitException;

class PlantApiService implements PlantApiServiceInterface
{
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

        $page = 1;
        $hasMore = true;
        while ($hasMore) {
            $response = Http::get('https://perenual.com/api/v2/species-list', [
                'key' => $apiKey,
                'page' => $page
            ]);
            
            // Vérification du rate limit
            if ($response->failed() || isset($response->json()['X-RateLimit-Exceeded'])) {
                throw new ApiRateLimitException($response);
            }
            
            $plants = $response->json('data') ?? [];
            
            // Debug : affiche la réponse complète pour voir les erreurs
            if ($page === 1) {
                \Illuminate\Support\Facades\Log::info('API Response page 1:', $response->json());
            }
            
            $hasMore = count($plants) > 0;
            foreach ($plants as $plantData) {
                $id = $plantData['id'] ?? null;
                if ($id) {
                    $detailsUrl = str_replace('{id}', $id, $detailsApiUrl);
                    $detailsResponse = Http::get($detailsUrl, [
                        'key' => $apiKey
                    ]);
                    $details = $detailsResponse->json();
                    $image = $details['default_image'] ?? [];

                    Plant::updateOrCreate(
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
                }
            }
            $page++;
        }
    }
}
