<?php

namespace App\Services;

interface WeatherApiServiceInterface
{
    /**
     * Récupère les données météo pour une localisation donnée par nom de ville.
     * Peut retourner les données du cache ou appeler l'API externe.
     *
     * @param string $location
     * @return array
     */
    public function getWeather(string $location): array;

    /**
     * Récupère les données météo pour des coordonnées GPS spécifiques.
     * Plus précis que la recherche par nom de ville.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function getWeatherByCoordinates(float $latitude, float $longitude): array;

    /**
     * Récupère les données météo avec support automatique ville ou coordonnées.
     *
     * @param string|null $location Nom de la ville (optionnel)
     * @param float|null $latitude Latitude GPS (optionnel)
     * @param float|null $longitude Longitude GPS (optionnel)
     * @return array
     */
    public function getWeatherData(?string $location = null, ?float $latitude = null, ?float $longitude = null): array;

    /**
     * Extrait les coordonnées GPS d'une réponse météo.
     *
     * @param array $weatherData
     * @return array|null [latitude => float, longitude => float] ou null si non disponible
     */
    public function extractCoordinates(array $weatherData): ?array;

    /**
     * Extrait le nom de localisation d'une réponse météo.
     *
     * @param array $weatherData
     * @return string|null Nom formaté de la localisation ou null si non disponible
     */
    public function extractLocationName(array $weatherData): ?string;
}
