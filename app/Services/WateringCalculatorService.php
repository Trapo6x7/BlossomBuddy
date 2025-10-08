<?php

namespace App\Services;

use Carbon\Carbon;

class WateringCalculatorService implements WateringCalculatorServiceInterface
{
    public function calculateNextWateringTime(array $plantData, array $weatherData, ?string $lastWateringDate = null): array
    {
        // 1. Récupérer les données de base d'arrosage de la plante
        $wateringBenchmark = $this->getWateringBenchmark($plantData);
        $baseDays = $this->extractDaysFromBenchmark($wateringBenchmark);
        
        // 2. Appliquer les modifications météo
        $adjustedDays = $this->adjustForWeather($baseDays, $weatherData);
        
        // 3. Calculer la prochaine date d'arrosage
        $lastWatered = $lastWateringDate ? Carbon::parse($lastWateringDate) : Carbon::now();
        $nextWatering = $lastWatered->addDays($adjustedDays);
        
        // 4. Calculer le temps restant
        $hoursUntilWatering = Carbon::now()->diffInHours($nextWatering, false);
        
        return [
            'next_watering_date' => $nextWatering->toDateTimeString(),
            'hours_until_watering' => $hoursUntilWatering,
            'days_until_watering' => round($hoursUntilWatering / 24, 1),
            'watering_frequency_days' => $adjustedDays,
            'weather_adjustment' => $adjustedDays - $baseDays,
            'recommendation' => $this->getRecommendation($hoursUntilWatering, $weatherData)
        ];
    }

    private function getWateringBenchmark(array $plantData): array
    {
        $benchmark = $plantData['watering_general_benchmark'] ?? [];
        
        // Si c'est un string JSON (cas des anciennes données), le décoder
        if (is_string($benchmark)) {
            $decoded = json_decode($benchmark, true);
            // Si le décodage échoue ou si on a encore un string (double encodage), essayer à nouveau
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
            return 7; // Valeur par défaut : 7 jours
        }

        $value = $benchmark['value'];
        
        // Si la valeur est encore un string JSON encodé, la décoder
        if (is_string($value) && (strpos($value, '"') === 0 || strpos($value, '\\"') !== false)) {
            $value = json_decode($value) ?? $value;
            // Double décodage si nécessaire
            if (is_string($value) && (strpos($value, '"') === 0 || strpos($value, '\\"') !== false)) {
                $value = json_decode($value) ?? $value;
            }
        }
        
        // Gestion des plages comme "6-12" ou des valeurs simples comme "7"
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

        // Ajustement basé sur l'humidité
        if ($humidity > 70) {
            $adjustment += 1; // Plus humide = arroser moins souvent
        } elseif ($humidity < 30) {
            $adjustment -= 1; // Plus sec = arroser plus souvent
        }

        // Ajustement basé sur la température
        if ($tempC > 25) {
            $adjustment -= 1; // Plus chaud = arroser plus souvent
        } elseif ($tempC < 15) {
            $adjustment += 1; // Plus froid = arroser moins souvent
        }

        // Ajustement basé sur les conditions
        if (strpos($condition, 'rain') !== false || strpos($condition, 'drizzle') !== false) {
            $adjustment += 2; // Pluie = arroser beaucoup moins souvent
        } elseif (strpos($condition, 'sunny') !== false || strpos($condition, 'clear') !== false) {
            $adjustment -= 1; // Ensoleillé = arroser plus souvent
        }

        // S'assurer que le résultat reste dans une plage raisonnable
        $adjustedDays = $baseDays + $adjustment;
        return max(1, min($adjustedDays, $baseDays * 2)); // Entre 1 jour et 2x la fréquence de base
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