<?php

namespace App\Services;

interface PlantApiServiceInterface
{
    public function updatePlantsFromApi(): void;

    /**
     * Récupère les données d'une plante par son nom depuis l'API externe
     */
    public function getPlantData(string $commonName): ?array;
}