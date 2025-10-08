<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\WeatherApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeatherApiServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weatherService = new WeatherApiService();
    }

    public function test_weather_api_returns_data_successfully()
    {
        // Mock de la réponse API WeatherAPI
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => [
                    'name' => 'Paris',
                    'country' => 'France',
                    'region' => 'Ile-de-France'
                ],
                'current' => [
                    'temp_c' => 22.5,
                    'condition' => [
                        'text' => 'Partly cloudy'
                    ],
                    'humidity' => 65,
                    'wind_kph' => 15.2
                ]
            ], 200)
        ]);

        $weather = $this->weatherService->getWeather('Paris');

        // Vérifications
        $this->assertIsArray($weather);
        $this->assertArrayHasKey('location', $weather);
        $this->assertArrayHasKey('current', $weather);
        $this->assertEquals('Paris', $weather['location']['name']);
        $this->assertEquals(22.5, $weather['current']['temp_c']);
    }

    public function test_weather_data_is_cached()
    {
        // Vide le cache avant le test
        Cache::flush();

        // Mock de la réponse API
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Paris'],
                'current' => ['temp_c' => 20]
            ], 200)
        ]);

        // Premier appel - doit faire l'appel API
        $weather1 = $this->weatherService->getWeather('Paris');
        
        // Deuxième appel - doit utiliser le cache
        $weather2 = $this->weatherService->getWeather('Paris');

        // Vérifications
        $this->assertEquals($weather1, $weather2);
        
        // Vérifie que les données sont en cache
        $cacheKey = 'weather_' . md5('Paris');
        $this->assertTrue(Cache::has($cacheKey));
        
        // Vérifie qu'un seul appel API a été fait
        Http::assertSentCount(1);
    }

    public function test_weather_api_handles_failure()
    {
        // Mock d'une réponse d'erreur API
        Http::fake([
            'api.weatherapi.com/*' => Http::response([], 404)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erreur lors de la récupération des données météo');

        $this->weatherService->getWeather('VilleInexistante');
    }

    public function test_weather_controller_integration()
    {
        // Mock de la réponse API météo
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Lyon'],
                'current' => ['temp_c' => 18.5]
            ], 200),
            // Mock de l'API Plant pour éviter le rate limit
            'perenual.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'common_name' => 'Test Plant'
                    ]
                ]
            ], 200)
        ]);

        // Test de la route POST /plant avec météo
        $response = $this->postJson('/plant', [
            'common_name' => 'Test Plant',
            'ville' => 'Lyon',
            'watering' => 'medium',
            'watering_general_benchmark' => json_encode(['value' => '7-10', 'unit' => 'days'])
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'plant',
            'weather' => [
                'location',
                'current'
            ]
        ]);
        
        // Vérifie que les données météo sont présentes
        $this->assertEquals('Lyon', $response->json('weather.location.name'));
        $this->assertEquals(18.5, $response->json('weather.current.temp_c'));
    }
}