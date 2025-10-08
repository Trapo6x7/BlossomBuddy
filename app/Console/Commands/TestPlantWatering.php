<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Plant;

class TestPlantWatering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:plant-watering {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test le calcul des dates d\'arrosage pour une plante';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plantName = $this->argument('name') ?? 'Rubber Plant';
        
        $plant = Plant::where('common_name', $plantName)->first();
        
        if (!$plant) {
            $this->error("Plante '{$plantName}' non trouvée");
            return;
        }

        $this->info("🌱 Test pour: {$plant->common_name}");
        $this->line("🇫🇷 Nom français: " . ($plant->french_name ?: 'Non traduit'));
        $this->newLine();
        
        // Afficher les données d'arrosage
        $this->info("💧 Données d'arrosage:");
        if ($plant->watering_general_benchmark) {
            $benchmark = $plant->watering_general_benchmark;
            $this->line("   📊 Benchmark: " . json_encode($benchmark, JSON_PRETTY_PRINT));
        } else {
            $this->line("   ❌ Aucune donnée benchmark");
        }
        
        if ($plant->watering_frequency) {
            $this->line("   🔢 Fréquence: {$plant->watering_frequency} jours");
        } else {
            $this->line("   ❌ Aucune fréquence spécifique");
        }
        
        $this->newLine();
        
        // Test du calcul de date
        $this->info("📅 Test de calcul de prochaine date d'arrosage:");
        $now = new \DateTime();
        $this->line("   🕐 Maintenant: " . $now->format('Y-m-d H:i:s'));
        
        try {
            $nextWatering = $plant->calculateNextWateringDate($now);
            $this->line("   📅 Prochain arrosage: " . $nextWatering->format('Y-m-d H:i:s'));
            
            $diff = $now->diff($nextWatering);
            $this->line("   ⏱️  Dans: {$diff->days} jour(s)");
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur calcul: " . $e->getMessage());
        }
    }
}
