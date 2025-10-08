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
            $table->string('french_name')->nullable()->after('common_name');
            $table->json('alternative_names')->nullable()->after('french_name'); // Pour synonymes et variantes
            $table->string('family_french')->nullable()->after('alternative_names'); // Famille en français
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant', function (Blueprint $table) {
            $table->dropColumn(['french_name', 'alternative_names', 'family_french']);
        });
    }
};
