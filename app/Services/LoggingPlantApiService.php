<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class LoggingPlantApiService implements PlantApiServiceInterface
{
    private PlantApiServiceInterface $inner;
    private LoggerInterface $logger;

    public function __construct(PlantApiServiceInterface $inner, LoggerInterface $logger)
    {
        $this->inner = $inner;
        $this->logger = $logger;
    }

    public function updatePlantsFromApi(): void
    {
        $this->logger->info('updatePlantsFromApi called');
        $this->inner->updatePlantsFromApi();
    }

    public function getPlantData(string $commonName): ?array
    {
        $this->logger->info("getPlantData called for name: $commonName");
        return $this->inner->getPlantData($commonName);
    }

    public function getAllPlants(): array
    {
        $this->logger->info('getAllPlants called');
        return $this->inner->getAllPlants();
    }
}
