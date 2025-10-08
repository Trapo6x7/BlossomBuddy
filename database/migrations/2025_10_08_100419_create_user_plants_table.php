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
        Schema::create('user_plants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plant_id')->constrained()->onDelete('cascade');
            $table->string('city'); // Ville de l'utilisateur pour cette plante
            $table->timestamp('last_watered_at')->nullable(); // Dernière fois arrosée
            $table->timestamp('next_watering_at')->nullable(); // Prochain arrosage calculé
            $table->json('watering_preferences')->nullable(); // Préférences utilisateur
            $table->timestamps();
            
            // Index unique : un utilisateur ne peut avoir qu'une seule instance d'une plante dans une ville
            $table->unique(['user_id', 'plant_id', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plants');
    }
};
