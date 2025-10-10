<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlantApiServiceInterface;
use App\Models\Plant;
use App\Models\BackfillState;

class BackfillPlants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plants:backfill 
                            {--skip-translation : Ne pas lancer la traduction automatique}
                            {--force-restart : Force un redémarrage complet depuis la page 1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill des données des plantes depuis Perenual API avec traduction automatique et reprise automatique';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(PlantApiServiceInterface $plantApiService)
    {
        // Vérifier si l'utilisateur veut forcer un redémarrage
        if ($this->option('force-restart')) {
            $backfillState = BackfillState::where('process_name', 'plants_backfill')->first();
            if ($backfillState) {
                $this->warn('🔄 Redémarrage forcé du backfill...');
                $backfillState->reset();
            }
        }

        // Afficher le statut de reprise
        $backfillState = BackfillState::where('process_name', 'plants_backfill')->first();
        if ($backfillState && !$backfillState->is_completed && $backfillState->last_page > 0) {
            $this->info("🔄 Reprise du backfill à partir de la page " . ($backfillState->last_page + 1));
            $this->info("📊 Déjà traités: {$backfillState->processed_items} éléments");
        } else {
            $this->info('🌱 Début du backfill des plantes...');
        }
        
        // Compteur avant backfill
        $plantsCountBefore = Plant::count();
        
        try {
            $plantApiService->updatePlantsFromApi();
            
            // Compteur après backfill
            $plantsCountAfter = Plant::count();
            $newPlantsAdded = $plantsCountAfter - $plantsCountBefore;
            
            $this->info("✅ Backfill terminé - {$newPlantsAdded} nouvelles plantes ajoutées");
            
            // Lancer la traduction automatique sauf si explicitement désactivée
            if (!$this->option('skip-translation')) {
                $this->info('🌍 Lancement de la traduction automatique...');
                
                // Compter les plantes non traduites avant
                $untranslatedBefore = Plant::whereNull('french_name')->count();
                
                if ($untranslatedBefore > 0) {
                    $this->line("📊 {$untranslatedBefore} plante(s) à traduire...");
                    
                    // Lancer la commande de traduction
                    $translationResult = $this->call('plants:translate');
                    
                    if ($translationResult === 0) {
                        // Vérifier les résultats après traduction
                        $untranslatedAfter = Plant::whereNull('french_name')->count();
                        $translatedCount = $untranslatedBefore - $untranslatedAfter;
                        
                        $this->info("🎉 Traduction terminée - {$translatedCount} plante(s) traduites");
                        
                        if ($untranslatedAfter > 0) {
                            $this->warn("⚠️  {$untranslatedAfter} plante(s) restent non traduites");
                            $this->line("💡 Vous pouvez les traduire manuellement avec l'API PUT /plants/{id}/french-names");
                        }
                    } else {
                        $this->error('❌ Erreur lors de la traduction automatique');
                    }
                } else {
                    $this->info('✅ Toutes les plantes sont déjà traduites !');
                }
            } else {
                $this->warn('⏭️  Traduction automatique ignorée (--skip-translation)');
            }
            
            $this->newLine();
            $this->info('📊 Résumé final:');
            $this->info("   • Total des plantes: {$plantsCountAfter}");
            $this->info("   • Nouvelles plantes: {$newPlantsAdded}");
            
            if (!$this->option('skip-translation')) {
                $translated = Plant::whereNotNull('french_name')->count();
                $untranslated = Plant::whereNull('french_name')->count();
                $percentage = $plantsCountAfter > 0 ? round(($translated / $plantsCountAfter) * 100, 1) : 0;
                
                $this->info("   • Plantes traduites: {$translated} ({$percentage}%)");
                $this->info("   • Plantes non traduites: {$untranslated}");
            }
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error('❌ Erreur lors du backfill : ' . $e->getMessage());
            return 1;
        }
    }
}
