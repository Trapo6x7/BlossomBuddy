<?php

namespace App\Strategies;

use Carbon\Carbon;

class FallbackWateringStrategy implements WateringStrategyInterface
{
    public function calculateDaysUntilNextWatering(array $plantData, array $weatherData, ?string $lastWateringDate = null): array
    {
        // Si pas de benchmark, on utilise une valeur par défaut
        $baseDays = 7;
        $benchmark = $plantData['watering_general_benchmark'] ?? [];
        if (!empty($benchmark['value'])) {
            $value = $benchmark['value'];
            if (strpos($value, '-') !== false) {
                $range = explode('-', $value);
                $baseDays = (int) round((intval($range[0]) + intval($range[1])) / 2);
            } else {
                $baseDays = (int) intval($value);
            }
        }
        // Si toujours rien, on regarde la famille
        if ($baseDays === 7 && !empty($plantData['family_french'])) {
            $famille = strtolower($plantData['family_french']);
            if (strpos($famille, 'cactus') !== false) {
                $baseDays = 14;
            } elseif (strpos($famille, 'fougère') !== false) {
                $baseDays = 4;
            }
        }
        $lastWatered = $lastWateringDate ? Carbon::parse($lastWateringDate) : Carbon::now();
        $nextWatering = $lastWatered->addDays($baseDays);
        $hoursUntilWatering = Carbon::now()->diffInHours($nextWatering, false);
        return [
            'next_watering_date' => $nextWatering->toDateTimeString(),
            'hours_until_watering' => $hoursUntilWatering,
            'days_until_watering' => round($hoursUntilWatering / 24, 1),
            'watering_frequency_days' => $baseDays,
            'strategy' => 'FallbackWateringStrategy',
        ];
    }
}
