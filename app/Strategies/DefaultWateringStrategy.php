<?php

namespace App\Strategies;

use Carbon\Carbon;

class DefaultWateringStrategy implements WateringStrategyInterface
{
    public function calculateDaysUntilNextWatering(array $plantData, array $weatherData, ?string $lastWateringDate = null): array
    {
        $wateringBenchmark = $this->getWateringBenchmark($plantData);
        $baseDays = $this->extractDaysFromBenchmark($wateringBenchmark);
        $adjustedDays = $this->adjustForWeather($baseDays, $weatherData);

        $lastWatered = $lastWateringDate ? Carbon::parse($lastWateringDate) : Carbon::now();
        $nextWatering = $lastWatered->copy()->addDays($adjustedDays);
        $hoursUntilWatering = Carbon::now()->diffInHours($nextWatering, false);

        return [
            'next_watering_date' => $nextWatering->toDateTimeString(),
            'hours_until_watering' => $hoursUntilWatering,
            'days_until_watering' => round($hoursUntilWatering / 24, 1),
            'watering_frequency_days' => $adjustedDays,
            'strategy' => 'DefaultWateringStrategy',
            'recommendation' => $this->getRecommendation($hoursUntilWatering, $weatherData),
        ];
    }

    private function getWateringBenchmark(array $plantData): array
    {
        $benchmark = $plantData['watering_general_benchmark'] ?? [];
        if (is_string($benchmark)) {
            $decoded = json_decode($benchmark, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return $decoded ?? [];
        }
        return $benchmark;
    }

    private function extractDaysFromBenchmark(array $benchmark): int
    {
        if (empty($benchmark['value'])) {
            return 7;
        }
        $value = $benchmark['value'];
        if (is_string($value) && (strpos($value, '"') === 0 || strpos($value, '\\"') !== false)) {
            $value = json_decode($value) ?? $value;
            if (is_string($value) && (strpos($value, '"') === 0 || strpos($value, '\\"') !== false)) {
                $value = json_decode($value) ?? $value;
            }
        }
        if (strpos($value, '-') !== false) {
            $range = explode('-', $value);
            return (int) round((intval($range[0]) + intval($range[1])) / 2);
        }
        return (int) intval($value);
    }

    private function adjustForWeather(int $baseDays, array $weatherData): int
    {
        $current = $weatherData['current'] ?? [];
        $humidity = $current['humidity'] ?? 50;
        $tempC = $current['temp_c'] ?? 20;
        $condition = strtolower($current['condition']['text'] ?? '');

        $adjustment = 0;
        if ($humidity > 70) {
            $adjustment += 1;
        } elseif ($humidity < 30) {
            $adjustment -= 1;
        }
        if ($tempC > 25) {
            $adjustment -= 1;
        } elseif ($tempC < 15) {
            $adjustment += 1;
        }
        if (strpos($condition, 'rain') !== false || strpos($condition, 'drizzle') !== false) {
            $adjustment += 2;
        } elseif (strpos($condition, 'sunny') !== false || strpos($condition, 'clear') !== false) {
            $adjustment -= 1;
        }
        $adjustedDays = $baseDays + $adjustment;
        return max(1, min($adjustedDays, $baseDays * 2));
    }

    private function getRecommendation(int $hoursUntilWatering, array $weatherData): string
    {
        if ($hoursUntilWatering <= 0) {
            return "Il est temps d'arroser votre plante !";
        } elseif ($hoursUntilWatering <= 24) {
            return "Préparez-vous à arroser votre plante dans les prochaines 24h.";
        } elseif ($hoursUntilWatering <= 48) {
            return "Votre plante aura besoin d'eau dans 2 jours.";
        } else {
            $days = round($hoursUntilWatering / 24);
            return "Votre plante aura besoin d'eau dans {$days} jours.";
        }
    }
}
