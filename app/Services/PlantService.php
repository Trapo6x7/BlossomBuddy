<?php

namespace App\Services;

use App\Services\PlantApiServiceInterface;
use App\Repositories\PlantRepositoryInterface;

class PlantService implements PlantServiceInterface
{
    private PlantApiServiceInterface $apiService;
    private PlantRepositoryInterface $repository;

    public function __construct(
        PlantApiServiceInterface $apiService,
        PlantRepositoryInterface $repository
    ) {
        $this->apiService = $apiService;
        $this->repository = $repository;
    }

    public function fetchAndStorePlants(): void
    {
        // 1. Récupérer les plantes depuis l'API
        $plants = $this->apiService->getAllPlants();

        // 2. Stocker chaque plante en base
        foreach ($plants as $plant) {
            $this->repository->store($plant);
        }
    }
}