<?php

namespace App\Strategies;

use Carbon\Carbon;

class SensitivePlantWateringStrategy implements WateringStrategyInterface
{
    public function calculateDaysUntilNextWatering(array $plantData, array $weatherData, ?string $lastWateringDate = null): array
    {
        // Utilise la logique par défaut
        $benchmark = $plantData['watering_general_benchmark'] ?? [];
        $baseDays = 7;
        if (!empty($benchmark['value'])) {
            $value = $benchmark['value'];
            if (strpos($value, '-') !== false) {
                $range = explode('-', $value);
                $baseDays = (int) round((intval($range[0]) + intval($range[1])) / 2);
            } else {
                $baseDays = (int) intval($value);
            }
        }
        // Si la plante est "Frequent" ou "Minimum", on arrose plus souvent
        $watering = strtolower($plantData['watering'] ?? '');
        if ($watering === 'frequent' || $watering === 'minimum') {
            $baseDays = max(1, $baseDays - 2);
        }
        $lastWatered = $lastWateringDate ? Carbon::parse($lastWateringDate) : Carbon::now();
        $nextWatering = $lastWatered->addDays($baseDays);
        $hoursUntilWatering = Carbon::now()->diffInHours($nextWatering, false);
        return [
            'next_watering_date' => $nextWatering->toDateTimeString(),
            'hours_until_watering' => $hoursUntilWatering,
            'days_until_watering' => round($hoursUntilWatering / 24, 1),
            'watering_frequency_days' => $baseDays,
            'strategy' => 'SensitivePlantWateringStrategy',
        ];
    }
}
