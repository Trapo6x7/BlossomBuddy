<?php

namespace App\Console\Commands;

use App\Services\PlantSearchService;
use Illuminate\Console\Command;

class TestPlantSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plants:search-test {query : Le nom de plante à rechercher}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste le système de recherche de plantes en français';

    public function __construct(
        private PlantSearchService $plantSearchService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query');
        
        $this->info("🔍 Recherche pour: \"{$query}\"");
        $this->newLine();

        // Test de recherche normale
        $this->info("📋 Résultats de recherche:");
        $plants = $this->plantSearchService->searchPlants($query, 5);
        
        if ($plants->isEmpty()) {
            $this->warn("❌ Aucune plante trouvée");
        } else {
            foreach ($plants as $plant) {
                $this->line("✅ {$plant->common_name}");
                if ($plant->french_name) {
                    $this->line("   🇫🇷 Nom français: {$plant->french_name}");
                }
                if ($plant->alternative_names && count($plant->alternative_names) > 0) {
                    $this->line("   📝 Autres noms: " . implode(', ', $plant->alternative_names));
                }
                $this->newLine();
            }
        }

        // Test d'autocomplétion
        $this->info("💡 Suggestions d'autocomplétion:");
        $suggestions = $this->plantSearchService->getAutocompleteSuggestions($query, 5);
        
        if (empty($suggestions)) {
            $this->warn("❌ Aucune suggestion");
        } else {
            foreach ($suggestions as $suggestion) {
                $source = $suggestion['source'] === 'database' ? '💾' : '📖';
                $this->line("{$source} {$suggestion['text']}");
                if (isset($suggestion['secondary'])) {
                    $this->line("   → {$suggestion['secondary']}");
                }
            }
        }

        $this->newLine();

        // Test de find-or-suggest
        $this->info("🎯 Test find-or-suggest:");
        $result = $this->plantSearchService->findOrSuggestPlant($query);
        
        if ($result['found']) {
            $plant = $result['plant'];
            $this->info("✅ Plante trouvée avec confiance {$result['confidence']}:");
            $this->line("   {$plant->common_name}");
            if ($plant->french_name) {
                $this->line("   🇫🇷 {$plant->french_name}");
            }
        } else {
            $this->warn("❌ {$result['message']}");
            if (!empty($result['suggestions'])) {
                foreach ($result['suggestions'] as $suggestion) {
                    $this->line("   💡 {$suggestion['text']}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
