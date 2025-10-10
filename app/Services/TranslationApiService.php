<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TranslationApiService
{
    private string $baseUrl = 'https://api.mymemory.translated.net/get';
    private int $requestDelay = 200; // 200ms entre les requêtes pour éviter le rate limiting

    /**
     * Traduit un texte de l'anglais vers le français
     */
    public function translateToFrench(string $englishText): ?string
    {
        $cacheKey = 'translation_' . md5($englishText);
        
        // Vérifier le cache d'abord
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Petite pause pour éviter le rate limiting
            usleep($this->requestDelay * 1000);
            
            $response = Http::timeout(10)->get($this->baseUrl, [
                'q' => $englishText,
                'langpair' => 'en|fr'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['responseData']['translatedText'])) {
                    $translation = $data['responseData']['translatedText'];
                    
                    // Mettre en cache pour 24h
                    Cache::put($cacheKey, $translation, now()->addDay());
                    
                    Log::info("API Translation: '{$englishText}' → '{$translation}'");
                    return $translation;
                }
            }
            
            Log::warning("Translation API failed for: {$englishText}");
            return null;
            
        } catch (\Exception $e) {
            Log::error("Translation API error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Traduit un nom de plante avec post-processing spécialisé
     */
    public function translatePlantName(string $englishName): ?array
    {
        $translation = $this->translateToFrench($englishName);
        
        if (!$translation) {
            return null;
        }

        // Post-processing pour améliorer la traduction des plantes
        $frenchName = $this->improvePlantTranslation($translation, $englishName);
        $alternatives = $this->generateAlternatives($frenchName, $englishName);

        return [
            'french_name' => $frenchName,
            'alternatives' => $alternatives
        ];
    }

    /**
     * Améliore la traduction automatique pour les noms de plantes
     */
    private function improvePlantTranslation(string $translation, string $original): string
    {
        // Corrections courantes pour les noms de plantes
        $corrections = [
            // Corrections de termes botaniques
            'Feuille' => 'Feuilles',
            'érable japonais' => 'Érable du Japon',
            'chêne anglais' => 'Chêne pédonculé',
            'pin blanc américain' => 'Pin blanc du Canada',
            'sapin blanc européen' => 'Sapin pectiné',
            
            // Corrections de majuscules
            'fir' => 'Sapin',
            'maple' => 'Érable',
            'oak' => 'Chêne',
            'pine' => 'Pin',
            
            // Corrections d'articles
            'le ' => '',
            'la ' => '',
            'les ' => '',
        ];

        $improved = $translation;
        
        foreach ($corrections as $search => $replace) {
            $improved = str_ireplace($search, $replace, $improved);
        }

        // Première lettre en majuscule
        $improved = ucfirst(trim($improved));
        
        return $improved;
    }

    /**
     * Génère des noms alternatifs pour la plante
     */
    private function generateAlternatives(string $frenchName, string $englishName): array
    {
        $alternatives = [];
        
        // Ajouter le nom anglais comme alternative
        $alternatives[] = $englishName;
        
        // Ajouter des variations basées sur des mots-clés
        $keywords = [
            'Japanese' => 'du Japon',
            'European' => 'européen',
            'American' => 'américain',
            'White' => 'blanc',
            'Red' => 'rouge',
            'Silver' => 'argenté',
            'Noble' => 'noble',
            'Dwarf' => 'nain',
        ];
        
        foreach ($keywords as $english => $french) {
            if (str_contains($englishName, $english)) {
                $variant = str_replace($english, $french, $englishName);
                if ($variant !== $englishName && !in_array($variant, $alternatives)) {
                    $alternatives[] = $variant;
                }
            }
        }
        
        // Limiter à 3 alternatives max
        return array_slice(array_unique($alternatives), 0, 3);
    }

    /**
     * Test de connectivité de l'API
     */
    public function testApiConnection(): bool
    {
        try {
            $testResult = $this->translateToFrench('Hello');
            return $testResult !== null;
        } catch (\Exception $e) {
            Log::error("Translation API connection test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Traduit plusieurs plantes en lot avec gestion du rate limiting
     */
    public function translateBatch(array $plantNames): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;
        
        foreach ($plantNames as $name) {
            $translation = $this->translatePlantName($name);
            
            if ($translation) {
                $results[$name] = $translation;
                $successful++;
            } else {
                $results[$name] = null;
                $failed++;
            }
            
            // Pause plus longue entre les requêtes pour le batch
            usleep($this->requestDelay * 2 * 1000);
        }
        
        Log::info("Batch translation completed: {$successful} successful, {$failed} failed");
        
        return $results;
    }
}