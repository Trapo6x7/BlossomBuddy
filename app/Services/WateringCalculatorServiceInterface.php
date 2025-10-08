<?php

namespace App\Services;

interface WateringCalculatorServiceInterface
{
    /**
     * Calcule le temps avant le prochain arrosage basé sur les données de la plante et la météo
     */
    public function calculateNextWateringTime(array $plantData, array $weatherData, ?string $lastWateringDate = null): array;
}