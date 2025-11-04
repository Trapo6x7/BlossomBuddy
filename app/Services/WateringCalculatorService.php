<?php

namespace App\Services;

use App\Strategies\WateringStrategyInterface;
use App\Strategies\DefaultWateringStrategy;

class WateringCalculatorService implements WateringCalculatorServiceInterface
{
    private WateringStrategyInterface $strategy;

    public function __construct(?WateringStrategyInterface $strategy = null)
    {
        $this->strategy = $strategy ?? new DefaultWateringStrategy();
    }

    public function calculateNextWateringTime(array $plantData, array $weatherData, ?string $lastWateringDate = null): array
    {
        return $this->strategy->calculateDaysUntilNextWatering($plantData, $weatherData, $lastWateringDate);
    }
}
