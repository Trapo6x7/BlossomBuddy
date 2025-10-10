<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackfillState;

class BackfillStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plants:backfill-status 
                            {--reset : Remet à zéro l\'état du backfill}
                            {--details : Affiche les détails complets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Affiche le statut du backfill des plantes et permet de le réinitialiser';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $backfillState = BackfillState::where('process_name', 'plants_backfill')->first();

        if ($this->option('reset')) {
            if (!$backfillState) {
                $this->warn('❌ Aucun état de backfill trouvé à réinitialiser');
                return 1;
            }

            if ($this->confirm('Êtes-vous sûr de vouloir réinitialiser l\'état du backfill ? Cela forcera un redémarrage complet.')) {
                $backfillState->reset();
                $this->info('✅ État du backfill réinitialisé - le prochain backfill recommencera à la page 1');
            } else {
                $this->info('🚫 Réinitialisation annulée');
            }
            return 0;
        }

        if (!$backfillState) {
            $this->info('📊 Aucun backfill de plantes n\'a encore été démarré');
            $this->line('💡 Lancez `php artisan plants:backfill` pour commencer');
            return 0;
        }

        $this->newLine();
        $this->info('📊 Statut du Backfill des Plantes');
        $this->line('═══════════════════════════════════════');

        // Statut général
        if ($backfillState->is_completed) {
            $this->info('✅ Statut: Terminé');
        } else {
            $this->warn('🔄 Statut: En cours (interrompu)');
        }

        // Informations de base
        $this->line("📄 Dernière page traitée: {$backfillState->last_page}");
        $this->line("📈 Éléments traités: {$backfillState->processed_items}");
        
        if ($backfillState->started_at) {
            $this->line("🚀 Démarré: {$backfillState->started_at->format('d/m/Y H:i:s')}");
        }
        
        if ($backfillState->last_checkpoint_at) {
            $this->line("💾 Dernier checkpoint: {$backfillState->last_checkpoint_at->format('d/m/Y H:i:s')}");
            $this->line("⏱️  Il y a: {$backfillState->last_checkpoint_at->diffForHumans()}");
        }

        // Détails supplémentaires
        if ($this->option('details') && $backfillState->metadata) {
            $this->newLine();
            $this->info('🔍 Détails supplémentaires:');
            foreach ($backfillState->metadata as $key => $value) {
                $this->line("   {$key}: {$value}");
            }
        }

        // Recommandations
        $this->newLine();
        if ($backfillState->is_completed) {
            $this->info('💡 Le backfill est terminé. Un nouveau backfill redémarrera automatiquement.');
        } else {
            $this->warn('💡 Le backfill reprendra à la page ' . ($backfillState->last_page + 1) . ' lors du prochain lancement.');
            $this->line('🔧 Pour recommencer depuis le début: php artisan plants:backfill-status --reset');
        }

        return 0;
    }
}
