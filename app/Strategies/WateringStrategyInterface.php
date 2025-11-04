<?php

namespace App\Strategies;

interface WateringStrategyInterface
{
    /**
     * Calcule le nombre de jours avant le prochain arrosage
     */
    public function calculateDaysUntilNextWatering(array $plantData, array $weatherData, ?string $lastWateringDate = null): array;
}
