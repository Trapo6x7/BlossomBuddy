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
            $table->string('watering')->nullable();
            $table->string('image_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('medium_url')->nullable();
            $table->string('regular_url')->nullable();
            $table->integer('license')->nullable();
            $table->string('license_name')->nullable();
            $table->string('license_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant', function (Blueprint $table) {
            $table->dropColumn([
                'watering',
                'image_url',
                'thumbnail_url',
                'medium_url',
                'regular_url',
                'license',
                'license_name',
                'license_url'
            ]);
        });
    }
};
