<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherApiService implements WeatherApiServiceInterface
{
    public function getWeather(string $location): array
    {
        return $this->fetchWeatherData($location);
    }

    public function getWeatherByCoordinates(float $latitude, float $longitude): array
    {
        $coordinates = "{$latitude},{$longitude}";
        return $this->fetchWeatherData($coordinates);
    }

    public function getWeatherData(?string $location = null, ?float $latitude = null, ?float $longitude = null): array
    {
        // Priorité aux coordonnées GPS (plus précises)
        if ($latitude !== null && $longitude !== null) {
            return $this->getWeatherByCoordinates($latitude, $longitude);
        }

        // Fallback sur le nom de ville
        if ($location) {
            return $this->getWeather($location);
        }

        throw new \InvalidArgumentException('Au moins une localisation (ville ou coordonnées GPS) doit être fournie');
    }

    /**
     * Méthode privée pour récupérer les données météo
     *
     * @param string $query Ville ou coordonnées "lat,lon"
     * @return array
     */
    private function fetchWeatherData(string $query): array
    {
        $cacheKey = 'weather_' . md5($query);
        $weather = Cache::get($cacheKey);

        if ($weather) {
            // Ajouter info sur la source du cache
            $weather['_cached'] = true;
            $weather['_cache_key'] = $cacheKey;
            return $weather;
        }

        // Appel à l'API WeatherAPI current.json
        $apiKey = config('services.weather_api.key');
        $apiUrl = config('services.weather_api.url');
        
        $response = Http::get($apiUrl . '/current.json', [
            'key' => $apiKey,
            'q' => $query, // Peut être "Paris" ou "48.8566,2.3522"
            'aqi' => 'no'
        ]);

        if ($response->failed()) {
            throw new \Exception("Erreur lors de la récupération des données météo pour: {$query}");
        }

        $weather = $response->json();
        
        // Ajouter des métadonnées utiles
        $weather['_cached'] = false;
        $weather['_query'] = $query;
        $weather['_timestamp'] = now()->toISOString();
        
        // Déterminer si c'était une requête par coordonnées
        $weather['_query_type'] = $this->isCoordinatesQuery($query) ? 'coordinates' : 'location';
        
        // Cache pendant 2 heures comme spécifié
        Cache::put($cacheKey, $weather, now()->addHours(2));

        return $weather;
    }

    /**
     * Vérifie si la requête contient des coordonnées GPS
     *
     * @param string $query
     * @return bool
     */
    private function isCoordinatesQuery(string $query): bool
    {
        // Format: "latitude,longitude" ex: "48.8566,2.3522"
        return preg_match('/^-?\d+\.?\d*,-?\d+\.?\d*$/', $query) === 1;
    }

    /**
     * Extrait les coordonnées d'une réponse météo
     *
     * @param array $weatherData
     * @return array|null [latitude, longitude] ou null si non disponible
     */
    public function extractCoordinates(array $weatherData): ?array
    {
        if (isset($weatherData['location']['lat']) && isset($weatherData['location']['lon'])) {
            return [
                'latitude' => (float) $weatherData['location']['lat'],
                'longitude' => (float) $weatherData['location']['lon']
            ];
        }

        return null;
    }

    /**
     * Obtient le nom de lieu convivial d'une réponse météo
     *
     * @param array $weatherData
     * @return string|null
     */
    public function extractLocationName(array $weatherData): ?string
    {
        $location = $weatherData['location'] ?? [];
        
        $parts = array_filter([
            $location['name'] ?? null,
            $location['region'] ?? null,
            $location['country'] ?? null
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }
}
