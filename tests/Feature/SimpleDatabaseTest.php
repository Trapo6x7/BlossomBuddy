<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plant;
use App\Models\UserPlant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user()
    {
        $user = User::factory()->create();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email
        ]);
    }

    public function test_can_create_plant()
    {
        $plant = Plant::create([
            'common_name' => 'Test Plant',
            'scientific_name' => json_encode(['Test scientificus']),
            'family' => 'Test Family',
            'type' => 'test',
            'cycle' => 'Annual',
            'watering' => 'Average',
            'watering_general_benchmark' => json_encode(['value' => '7', 'unit' => 'days']),
            'description' => 'A test plant',
            'image_url' => 'https://example.com/test.jpg',
            'thumbnail_url' => 'https://example.com/test-thumb.jpg',
            'medium_url' => 'https://example.com/test-medium.jpg',
            'regular_url' => 'https://example.com/test-regular.jpg',
            'license' => 1,
            'license_name' => 'Test License',
            'license_url' => 'https://example.com/license'
        ]);

        $this->assertDatabaseHas('plants', [
            'id' => $plant->id,
            'common_name' => 'Test Plant'
        ]);
    }

    public function test_can_create_user_plant()
    {
        $user = User::factory()->create();
        $plant = Plant::create([
            'common_name' => 'Test Plant',
            'scientific_name' => json_encode(['Test scientificus']),
            'family' => 'Test Family',
            'type' => 'test',
            'cycle' => 'Annual',
            'watering' => 'Average',
            'watering_general_benchmark' => json_encode(['value' => '7', 'unit' => 'days']),
            'description' => 'A test plant',
            'image_url' => 'https://example.com/test.jpg',
            'thumbnail_url' => 'https://example.com/test-thumb.jpg',
            'medium_url' => 'https://example.com/test-medium.jpg',
            'regular_url' => 'https://example.com/test-regular.jpg',
            'license' => 1,
            'license_name' => 'Test License',
            'license_url' => 'https://example.com/license'
        ]);

        $userPlant = UserPlant::create([
            'user_id' => $user->id,
            'plant_id' => $plant->id,
            'city' => 'Test City',
            'last_watered_at' => now(),
            'watering_preferences' => []
        ]);

        $this->assertDatabaseHas('user_plants', [
            'id' => $userPlant->id,
            'user_id' => $user->id,
            'plant_id' => $plant->id
        ]);
    }
}