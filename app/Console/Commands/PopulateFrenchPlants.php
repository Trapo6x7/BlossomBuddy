<?php

namespace App\Console\Commands;

use App\Models\Plant;
use Illuminate\Console\Command;

class PopulateFrenchPlants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plants:populate-french {--test : Mode test avec seulement quelques plantes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Peuple la base de données avec des plantes ayant des noms français pour tester le système de recherche';

    /**
     * Données de test avec noms français
     */
    private array $plantsData = [
        [
            'common_name' => 'Monstera deliciosa',
            'french_name' => 'Monstera',
            'alternative_names' => ['Faux philodendron', 'Plante gruyère'],
            'watering_general_benchmark' => ['frequency' => 'weekly', 'amount' => 'moderate']
        ],
        [
            'common_name' => 'Snake Plant',
            'french_name' => 'Sansevieria',
            'alternative_names' => ['Langue de belle-mère', 'Plante serpent'],
            'watering_general_benchmark' => ['frequency' => 'bi-weekly', 'amount' => 'low']
        ],
        [
            'common_name' => 'Rubber Plant',
            'french_name' => 'Caoutchouc',
            'alternative_names' => ['Ficus elastica', 'Hévéa'],
            'watering_general_benchmark' => ['frequency' => 'weekly', 'amount' => 'moderate']
        ],
        [
            'common_name' => 'Spider Plant',
            'french_name' => 'Plante araignée',
            'alternative_names' => ['Chlorophytum', 'Phalangère'],
            'watering_general_benchmark' => ['frequency' => 'weekly', 'amount' => 'moderate']
        ],
        [
            'common_name' => 'Aloe Vera',
            'french_name' => 'Aloès',
            'alternative_names' => ['Aloe vera'],
            'watering_general_benchmark' => ['frequency' => 'bi-weekly', 'amount' => 'low']
        ],
        [
            'common_name' => 'ZZ Plant',
            'french_name' => 'Zamioculcas',
            'alternative_names' => ['Plante ZZ', 'Zamioculcas zamiifolia'],
            'watering_general_benchmark' => ['frequency' => 'monthly', 'amount' => 'low']
        ],
        [
            'common_name' => 'Money Tree',
            'french_name' => 'Pachira',
            'alternative_names' => ['Arbre à argent', 'Châtaignier de Guyane'],
            'watering_general_benchmark' => ['frequency' => 'weekly', 'amount' => 'moderate']
        ],
        [
            'common_name' => 'Jade Plant',
            'french_name' => 'Plante de jade',
            'alternative_names' => ['Crassula', 'Arbre de jade'],
            'watering_general_benchmark' => ['frequency' => 'bi-weekly', 'amount' => 'low']
        ],
        [
            'common_name' => 'Lavender',
            'french_name' => 'Lavande',
            'alternative_names' => ['Lavandula'],
            'watering_general_benchmark' => ['frequency' => 'weekly', 'amount' => 'moderate']
        ],
        [
            'common_name' => 'Rosemary',
            'french_name' => 'Romarin',
            'alternative_names' => ['Rosmarinus'],
            'watering_general_benchmark' => ['frequency' => 'weekly', 'amount' => 'moderate']
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isTest = $this->option('test');
        $plantsToCreate = $isTest ? array_slice($this->plantsData, 0, 3) : $this->plantsData;

        $this->info('🌱 Début du peuplement des plantes avec noms français...');
        
        if ($isTest) {
            $this->info('🧪 Mode test activé - Création de ' . count($plantsToCreate) . ' plantes seulement');
        }

        $created = 0;
        $updated = 0;

        foreach ($plantsToCreate as $plantData) {
            $existing = Plant::where('common_name', $plantData['common_name'])->first();
            
            if ($existing) {
                // Mettre à jour avec les noms français
                $existing->update([
                    'french_name' => $plantData['french_name'],
                    'alternative_names' => $plantData['alternative_names']
                ]);
                $updated++;
                $this->line("🔄 Mis à jour: {$plantData['common_name']} → {$plantData['french_name']}");
            } else {
                // Créer nouvelle plante
                Plant::create($plantData);
                $created++;
                $this->line("✅ Créé: {$plantData['common_name']} → {$plantData['french_name']}");
            }
        }

        $this->newLine();
        $this->info("🎉 Terminé !");
        $this->info("📊 Résultats:");
        $this->info("   • {$created} plantes créées");
        $this->info("   • {$updated} plantes mises à jour");
        
        $this->newLine();
        $this->info("🔍 Vous pouvez maintenant tester la recherche avec:");
        $this->info("   • monstera");
        $this->info("   • sansevieria");
        $this->info("   • plante araignée");
        $this->info("   • aloès");

        return Command::SUCCESS;
    }
}
