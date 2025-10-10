<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backfill_state', function (Blueprint $table) {
            $table->id();
            $table->string('process_name')->unique(); // ex: 'plants_backfill'
            $table->integer('last_page')->default(0); // Dernière page traitée
            $table->integer('last_plant_id')->nullable(); // Dernier ID de plante traité
            $table->integer('total_pages')->nullable(); // Total de pages (si connu)
            $table->integer('processed_items')->default(0); // Nombre d'éléments traités
            $table->boolean('is_completed')->default(false); // Backfill terminé
            $table->timestamp('started_at')->nullable(); // Début du backfill
            $table->timestamp('last_checkpoint_at')->nullable(); // Dernier checkpoint
            $table->json('metadata')->nullable(); // Données supplémentaires
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backfill_state');
    }
};
