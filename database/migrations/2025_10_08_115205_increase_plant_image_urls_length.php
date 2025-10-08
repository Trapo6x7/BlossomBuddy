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
        Schema::table('plant', function (Blueprint $table) {
            // Augmenter la taille des colonnes d'URLs d'images
            $table->text('image_url')->nullable()->change();
            $table->text('thumbnail_url')->nullable()->change();
            $table->text('medium_url')->nullable()->change();
            $table->text('regular_url')->nullable()->change();
            $table->text('license_url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant', function (Blueprint $table) {
            // Revenir aux colonnes string
            $table->string('image_url')->nullable()->change();
            $table->string('thumbnail_url')->nullable()->change();
            $table->string('medium_url')->nullable()->change();
            $table->string('regular_url')->nullable()->change();
            $table->string('license_url')->nullable()->change();
        });
    }
};
