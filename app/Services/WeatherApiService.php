<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherApiService implements WeatherApiServiceInterface
{
    public function getWeather(string $location): array
    {
        $cacheKey = 'weather_' . md5($location);
        $weather = Cache::get($cacheKey);

        if ($weather) {
            return $weather;
        }

        // Remplace par l'URL de ton API météo et ajoute la clé si besoin
        $response = Http::get(env('WEATHER_API_URL'), [
            'location' => $location,
            'apikey' => env('WEATHER_API_KEY'),
        ]);

        $weather = $response->json();
        Cache::put($cacheKey, $weather, now()->addMinutes(30)); // Cache 30 min

        return $weather;
    }
}
