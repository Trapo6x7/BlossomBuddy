<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeocodingService;
use App\Services\WeatherApiServiceInterface;

class TestGpsWeather extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:gps-weather {city?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test le géocodage automatique et la récupération météo avec GPS';

    /**
     * Execute the console command.
     */
    public function handle(GeocodingService $geocodingService, WeatherApiServiceInterface $weatherService)
    {
        $city = $this->argument('city') ?? 'Lyon, France';
        
        $this->info("🌍 Test du géocodage automatique pour: {$city}");
        $this->newLine();
        
        // Test du géocodage
        $this->info("🔍 Géocodage de la ville...");
        $coordinates = $geocodingService->getCoordinatesFromCity($city);
        
        if ($coordinates) {
            $this->info("✅ Coordonnées trouvées:");
            $this->line("   📍 Latitude: {$coordinates['latitude']}");
            $this->line("   📍 Longitude: {$coordinates['longitude']}");
            $this->line("   📍 Adresse formatée: {$coordinates['formatted_address']}");
            $this->newLine();
            
            // Test météo avec coordonnées
            $this->info("🌤️  Test météo avec coordonnées GPS...");
            try {
                $weatherData = $weatherService->getWeatherByCoordinates(
                    $coordinates['latitude'], 
                    $coordinates['longitude']
                );
                
                $this->info("✅ Données météo récupérées:");
                $this->line("   🌡️  Température: {$weatherData['current']['temp_c']}°C");
                $this->line("   🌥️  Conditions: {$weatherData['current']['condition']['text']}");
                $this->line("   📍 Localisation API: {$weatherData['location']['name']}, {$weatherData['location']['region']}, {$weatherData['location']['country']}");
                $this->line("   🔄 Type de requête: {$weatherData['_query_type']}");
                
            } catch (\Exception $e) {
                $this->error("❌ Erreur météo: " . $e->getMessage());
            }
            
        } else {
            $this->error("❌ Impossible de géocoder la ville: {$city}");
        }
        
        $this->newLine();
        $this->info("🧪 Test du géocodage inversé...");
        
        // Test géocodage inversé avec des coordonnées connues (Lyon)
        $testLat = 45.764043;
        $testLon = 4.835659;
        
        $reverseCity = $geocodingService->getCityFromCoordinates($testLat, $testLon);
        
        if ($reverseCity) {
            $this->info("✅ Géocodage inversé réussi:");
            $this->line("   📍 Coordonnées: {$testLat}, {$testLon}");
            $this->line("   🏙️  Ville trouvée: {$reverseCity}");
        } else {
            $this->error("❌ Échec du géocodage inversé");
        }
        
        $this->newLine();
        $this->info("✨ Tests terminés!");
    }
}
