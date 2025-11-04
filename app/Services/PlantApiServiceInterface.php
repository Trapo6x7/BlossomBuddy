<?php

namespace App\Services;

interface PlantApiServiceInterface
{
    public function updatePlantsFromApi(): void;

    /**
     * Récupère les données d'une plante par son nom depuis l'API externe
     */
    public function getPlantData(string $commonName): ?array;

        /**
     * Récupère toutes les plantes depuis l'API
     * @return array
     */
    public function getAllPlants(): array;
}