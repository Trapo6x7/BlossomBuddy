<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Convertit un nom de ville en coordonnées GPS
     *
     * @param string $cityName Nom de la ville (ex: "Lyon, France")
     * @return array|null ['latitude' => float, 'longitude' => float, 'formatted_address' => string] ou null
     */
    public function getCoordinatesFromCity(string $cityName): ?array
    {
        $cacheKey = 'geocoding_' . md5(strtolower($cityName));
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return $cached;
        }

        // Option 1: Utiliser l'API WeatherAPI pour le géocodage (gratuit)
        $coordinates = $this->geocodeWithWeatherApi($cityName);
        
        if ($coordinates) {
            // Cache pendant 24 heures (les coordonnées de villes ne changent pas)
            Cache::put($cacheKey, $coordinates, now()->addHours(24));
            return $coordinates;
        }

        return null;
    }

    /**
     * Géocodage via WeatherAPI (que vous utilisez déjà)
     */
    private function geocodeWithWeatherApi(string $cityName): ?array
    {
        try {
            $apiKey = config('services.weather_api.key');
            $apiUrl = config('services.weather_api.url');
            
            // Utiliser l'endpoint search de WeatherAPI
            $response = Http::get($apiUrl . '/search.json', [
                'key' => $apiKey,
                'q' => $cityName
            ]);

            if ($response->successful()) {
                $results = $response->json();
                
                if (!empty($results)) {
                    $firstResult = $results[0];
                    return [
                        'latitude' => (float) $firstResult['lat'],
                        'longitude' => (float) $firstResult['lon'],
                        'formatted_address' => $firstResult['name'] . ', ' . $firstResult['region'] . ', ' . $firstResult['country']
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur géocodage WeatherAPI: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Géocodage via OpenStreetMap Nominatim (gratuit, backup)
     */
    private function geocodeWithNominatim(string $cityName): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'BlossomBuddy/1.0'
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $cityName,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1
            ]);

            if ($response->successful()) {
                $results = $response->json();
                
                if (!empty($results)) {
                    $result = $results[0];
                    return [
                        'latitude' => (float) $result['lat'],
                        'longitude' => (float) $result['lon'],
                        'formatted_address' => $result['display_name']
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur géocodage Nominatim: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Géocodage inversé - obtenir l'adresse depuis des coordonnées
     *
     * @param float $latitude
     * @param float $longitude
     * @return string|null
     */
    public function getCityFromCoordinates(float $latitude, float $longitude): ?string
    {
        $cacheKey = 'reverse_geocoding_' . md5("{$latitude},{$longitude}");
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'BlossomBuddy/1.0'
            ])->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'addressdetails' => 1
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['address'])) {
                    $address = $result['address'];
                    $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? null;
                    $region = $address['state'] ?? $address['region'] ?? null;
                    $country = $address['country'] ?? null;
                    
                    $formattedAddress = implode(', ', array_filter([$city, $region, $country]));
                    
                    // Cache pendant 24 heures
                    Cache::put($cacheKey, $formattedAddress, now()->addHours(24));
                    
                    return $formattedAddress;
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur géocodage inversé: ' . $e->getMessage());
        }

        return null;
    }
}