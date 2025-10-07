<?php

namespace App\Services;

interface WeatherApiServiceInterface
{
    /**
     * Récupère les données météo pour une localisation donnée.
     * Peut retourner les données du cache ou appeler l'API externe.
     *
     * @param string $location
     * @return array
     */
    public function getWeather(string $location): array;
}
