<?php

namespace App\Console\Commands;

use App\Models\Plant;
use Illuminate\Console\Command;

class CheckTranslationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plants:translation-status {--untranslated : Afficher seulement les plantes non traduites}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie le statut de traduction des plantes en base de données';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $showUntranslatedOnly = $this->option('untranslated');

        $this->info('📊 Statut de traduction des plantes');
        $this->newLine();

        // Statistiques générales
        $total = Plant::count();
        $translated = Plant::whereNotNull('french_name')->count();
        $untranslated = $total - $translated;

        $this->info("🌱 Total des plantes: {$total}");
        $this->info("✅ Traduites: {$translated}");
        $this->info("❌ Non traduites: {$untranslated}");
        
        $percentage = $total > 0 ? round(($translated / $total) * 100, 1) : 0;
        $this->info("📈 Pourcentage traduit: {$percentage}%");

        $this->newLine();

        if ($showUntranslatedOnly) {
            if ($untranslated > 0) {
                $this->warn("🔍 Plantes non traduites:");
                $untranslatedPlants = Plant::whereNull('french_name')->get();
                
                foreach ($untranslatedPlants as $plant) {
                    $this->line("❓ ID: {$plant->id} - {$plant->common_name}");
                }
            } else {
                $this->info("🎉 Toutes les plantes sont traduites !");
            }
        } else {
            // Afficher un échantillon de plantes traduites
            $this->info("✅ Échantillon de plantes traduites:");
            $translatedSample = Plant::whereNotNull('french_name')
                ->limit(10)
                ->get();

            foreach ($translatedSample as $plant) {
                $this->line("🌿 {$plant->common_name} → {$plant->french_name}");
                if ($plant->alternative_names && count($plant->alternative_names) > 0) {
                    $alternatives = implode(', ', $plant->alternative_names);
                    $this->line("   📝 {$alternatives}");
                }
            }

            if ($translated > 10) {
                $remaining = $translated - 10;
                $this->line("   ... et {$remaining} autres plantes traduites");
            }

            if ($untranslated > 0) {
                $this->newLine();
                $this->warn("❌ Plantes non traduites:");
                $untranslatedPlants = Plant::whereNull('french_name')->limit(5)->get();
                
                foreach ($untranslatedPlants as $plant) {
                    $this->line("❓ {$plant->common_name}");
                }

                if ($untranslated > 5) {
                    $remaining = $untranslated - 5;
                    $this->line("   ... et {$remaining} autres non traduites");
                }
            }
        }

        $this->newLine();
        
        if ($untranslated > 0) {
            $this->info("💡 Commandes utiles:");
            $this->info("   • php artisan plants:translate --dry-run  (voir ce qui serait traduit)");
            $this->info("   • php artisan plants:translate             (appliquer les traductions)");
            $this->info("   • php artisan plants:translation-status --untranslated");
        }

        $this->info("🔍 Test de recherche:");
        $this->info("   • php artisan plants:search-test \"érable\"");
        $this->info("   • php artisan plants:search-test \"sapin\"");

        return Command::SUCCESS;
    }
}
