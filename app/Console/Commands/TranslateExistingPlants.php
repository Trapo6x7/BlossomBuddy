<?php

namespace App\Console\Commands;

use App\Models\Plant;
use App\Services\PlantTranslationService;
use Illuminate\Console\Command;

class TranslateExistingPlants extends Command
{
    protected $signature = 'plants:translate {--force : Force la traduction même si déjà traduit} {--dry-run : Affiche ce qui serait fait sans modifier}';
    protected $description = 'Traduit automatiquement les noms anglais des plantes existantes en français';

    public function handle(PlantTranslationService $translationService)
    {
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info('🌍 Début de la traduction des plantes existantes...');

        if ($dryRun) {
            $this->warn('🧪 MODE DRY-RUN : Aucune modification ne sera apportée à la base de données');
        }

        if ($force) {
            $this->warn('🔄 Mode FORCE activé - Les traductions existantes seront écrasées');
        }

        $query = Plant::query();
        
        if (!$force) {
            $query->whereNull('french_name');
        }

        $plants = $query->get();
        
        $this->info("📊 {$plants->count()} plante(s) à traiter");
        $this->info("📚 {$translationService->getAvailableTranslationsCount()} traductions disponibles");
        $this->newLine();

        if ($plants->isEmpty()) {
            $this->info('✅ Toutes les plantes sont déjà traduites !');
            return Command::SUCCESS;
        }

        $translated = 0;
        $skipped = 0;

        foreach ($plants as $plant) {
            if ($force && $plant->french_name && !$dryRun) {
                $plant->update(['french_name' => null, 'alternative_names' => null]);
            }

            if (!$dryRun) {
                $wasTranslated = $translationService->translatePlant($plant);
                $plant->refresh();
                
                if ($wasTranslated && $plant->french_name) {
                    $translated++;
                    $this->line("✅ {$plant->common_name} → {$plant->french_name}");
                } else {
                    $skipped++;
                    $this->line("❌ {$plant->common_name} (pas de traduction trouvée)");
                }
            } else {
                $tempPlant = new Plant();
                $tempPlant->common_name = $plant->common_name;
                $tempPlant->french_name = null;
                
                $wouldTranslate = $translationService->translatePlant($tempPlant);
                
                if ($wouldTranslate && $tempPlant->french_name) {
                    $translated++;
                    $this->line("🧪 [DRY-RUN] {$plant->common_name} → {$tempPlant->french_name}");
                } else {
                    $skipped++;
                    $this->line("🧪 [DRY-RUN] {$plant->common_name} → pas de traduction trouvée");
                }
            }
        }

        $this->newLine();
        $this->info('📊 Résumé de la traduction:');
        $this->info("   ✅ Plantes traduites: {$translated}");
        $this->info("   ❌ Plantes non traduites: {$skipped}");
        
        if ($translated > 0) {
            $percentage = round(($translated / $plants->count()) * 100, 1);
            $this->info("   📈 Taux de réussite: {$percentage}%");
        }

        if ($skipped > 0) {
            $this->newLine();
            $this->warn("💡 Pour les plantes non traduites:");
            $this->line("   • Traduire manuellement via API PUT /plants/{id}/french-names");
            $this->line("   • Ajouter traductions dans PlantTranslationService");
        }

        return Command::SUCCESS;
    }
}
