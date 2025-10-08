<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plant;
use App\Models\UserPlant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

class WateringScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $plant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur de test
        $this->user = User::factory()->create();
        
        // Créer une plante de test
        $this->plant = Plant::create([
            'common_name' => 'Test Rose',
            'scientific_name' => json_encode(['Rosa test']),
            'family' => 'Rosaceae',
            'type' => 'flower',
            'cycle' => 'Perennial',
            'watering' => 'Average',
            'watering_general_benchmark' => json_encode(['value' => '7', 'unit' => 'days']),
            'description' => 'A beautiful test rose',
            'image_url' => 'https://example.com/rose.jpg',
            'thumbnail_url' => 'https://example.com/rose-thumb.jpg',
            'medium_url' => 'https://example.com/rose-medium.jpg',
            'regular_url' => 'https://example.com/rose-regular.jpg',
            'license' => 1,
            'license_name' => 'Test License',
            'license_url' => 'https://example.com/license'
        ]);
    }

    public function test_watering_schedule_calculation_success()
    {
        // Authentifier l'utilisateur
        Sanctum::actingAs($this->user);

        // Mock de la réponse API météo
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => [
                    'name' => 'Paris',
                    'country' => 'France'
                ],
                'current' => [
                    'temp_c' => 22.0,
                    'humidity' => 65,
                    'condition' => [
                        'text' => 'Partly cloudy'
                    ]
                ]
            ], 200)
        ]);

        // Appeler l'endpoint
        $response = $this->postJson('/plant/watering-schedule', [
            'plant_common_name' => 'Test Rose',
            'city' => 'Paris',
            'last_watered_at' => '2025-10-06T10:00:00Z'
        ]);

        // Vérifications
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user_plant' => [
                'id',
                'user_id',
                'plant_id',
                'city',
                'last_watered_at',
                'next_watering_at',
                'plant'
            ],
            'watering_schedule' => [
                'next_watering_date',
                'hours_until_watering',
                'days_until_watering',
                'watering_frequency_days',
                'weather_adjustment',
                'recommendation'
            ],
            'weather_data' => [
                'location',
                'current'
            ]
        ]);

        // Vérifier que l'UserPlant a été créé
        $this->assertDatabaseHas('user_plants', [
            'user_id' => $this->user->id,
            'plant_id' => $this->plant->id,
            'city' => 'Paris'
        ]);

        // Vérifier que les calculs sont cohérents
        $schedule = $response->json('watering_schedule');
        $this->assertIsString($schedule['next_watering_date']);
        $this->assertIsNumeric($schedule['hours_until_watering']);
        $this->assertIsNumeric($schedule['days_until_watering']);
        $this->assertIsNumeric($schedule['watering_frequency_days']);
        $this->assertIsString($schedule['recommendation']);
    }

    public function test_watering_schedule_requires_authentication()
    {
        // Sans authentification
        $response = $this->postJson('/plant/watering-schedule', [
            'plant_common_name' => 'Test Rose',
            'city' => 'Paris'
        ]);

        $response->assertStatus(401);
    }

    public function test_watering_schedule_plant_not_found()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/plant/watering-schedule', [
            'plant_common_name' => 'Nonexistent Plant',
            'city' => 'Paris'
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Plante non trouvée dans la base de données'
        ]);
    }

    public function test_watering_schedule_weather_adjustment()
    {
        Sanctum::actingAs($this->user);

        // Mock météo très chaude et sèche (devrait réduire l'intervalle d'arrosage)
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Phoenix'],
                'current' => [
                    'temp_c' => 35.0, // Très chaud (-1 jour)
                    'humidity' => 20,  // Très sec (-1 jour)
                    'condition' => ['text' => 'Sunny'] // Ensoleillé (-1 jour)
                ]
            ], 200)
        ]);

        $response = $this->postJson('/plant/watering-schedule', [
            'plant_common_name' => 'Test Rose',
            'city' => 'Phoenix',
            'last_watered_at' => '2025-10-06T10:00:00Z'
        ]);

        $response->assertStatus(200);
        
        $schedule = $response->json('watering_schedule');
        // Devrait avoir un ajustement négatif (arroser plus souvent)
        $this->assertLessThan(0, $schedule['weather_adjustment']);
        // Fréquence devrait être réduite par rapport au benchmark de base (7 jours)
        $this->assertLessThan(7, $schedule['watering_frequency_days']);
    }

    public function test_watering_schedule_rainy_weather_adjustment()
    {
        Sanctum::actingAs($this->user);

        // Mock météo pluvieuse (devrait augmenter l'intervalle d'arrosage)
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'London'],
                'current' => [
                    'temp_c' => 15.0,
                    'humidity' => 85,
                    'condition' => ['text' => 'Light rain']
                ]
            ], 200)
        ]);

        $response = $this->postJson('/plant/watering-schedule', [
            'plant_common_name' => 'Test Rose',
            'city' => 'London',
            'last_watered_at' => '2025-10-06T10:00:00Z'
        ]);

        $response->assertStatus(200);
        
        $schedule = $response->json('watering_schedule');
        // Devrait avoir un ajustement positif (arroser moins souvent)
        $this->assertGreaterThan(0, $schedule['weather_adjustment']);
        // Fréquence devrait être augmentée par rapport au benchmark de base
        $this->assertGreaterThan(7, $schedule['watering_frequency_days']);
    }

    public function test_watering_schedule_updates_existing_user_plant()
    {
        Sanctum::actingAs($this->user);

        // Créer une UserPlant existante
        $existingUserPlant = UserPlant::create([
            'user_id' => $this->user->id,
            'plant_id' => $this->plant->id,
            'city' => 'Paris',
            'last_watered_at' => '2025-10-05T10:00:00Z'
        ]);

        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Paris'],
                'current' => [
                    'temp_c' => 20.0,
                    'humidity' => 60,
                    'condition' => ['text' => 'Clear']
                ]
            ], 200)
        ]);

        $response = $this->postJson('/plant/watering-schedule', [
            'plant_common_name' => 'Test Rose',
            'city' => 'Paris',
            'last_watered_at' => '2025-10-07T10:00:00Z'
        ]);

        $response->assertStatus(200);

        // Vérifier que l'UserPlant existante a été mise à jour
        $updatedUserPlant = UserPlant::find($existingUserPlant->id);
        $this->assertEquals('2025-10-07T10:00:00Z', $updatedUserPlant->last_watered_at->toISOString());
        
        // Vérifier qu'on n'a pas créé de doublon
        $this->assertEquals(1, UserPlant::where([
            'user_id' => $this->user->id,
            'plant_id' => $this->plant->id,
            'city' => 'Paris'
        ])->count());
    }
}