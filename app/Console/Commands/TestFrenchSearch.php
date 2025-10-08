<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlantSearchService;
use App\Models\Plant;

class TestFrenchSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:french-search {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test la recherche de plantes en français';

    /**
     * Execute the console command.
     */
    public function handle(PlantSearchService $searchService)
    {
        $searchName = $this->argument('name') ?? 'rose';
        
        $this->info("🔍 Test de recherche pour: '{$searchName}'");
        $this->newLine();
        
        // Test de recherche
        $plant = $searchService->findPlantByName($searchName);
        
        if ($plant) {
            $this->info("✅ Plante trouvée!");
            $this->line("   🌱 Nom anglais: {$plant->common_name}");
            $this->line("   🇫🇷 Nom français: " . ($plant->french_name ?: 'Non traduit'));
            $this->line("   💧 Fréquence d'arrosage: {$plant->watering_frequency} jours");
            
            if ($plant->alternative_names) {
                $alternativesJson = is_array($plant->alternative_names) 
                    ? json_encode($plant->alternative_names)
                    : $plant->alternative_names;
                $alternatives = json_decode($alternativesJson, true);
                if ($alternatives) {
                    $this->line("   📝 Noms alternatifs: " . implode(', ', $alternatives));
                }
            }
        } else {
            $this->error("❌ Aucune plante trouvée pour: '{$searchName}'");
            
            // Suggestions
            $this->newLine();
            $this->info("💡 Plantes disponibles avec noms français:");
            $plantsWithFrench = Plant::whereNotNull('french_name')->take(10)->get(['common_name', 'french_name']);
            
            foreach ($plantsWithFrench as $availablePlant) {
                $this->line("   • {$availablePlant->french_name} → {$availablePlant->common_name}");
            }
        }
        
        $this->newLine();
        $this->info("🧪 Testez d'autres noms avec: php artisan test:french-search [nom]");
    }
}
