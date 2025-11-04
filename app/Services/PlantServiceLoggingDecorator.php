<?php

namespace App\Services;

class PlantServiceLoggingDecorator implements PlantServiceInterface
{
    private PlantServiceInterface $plantService;
    private LoggingServiceInterface $logger;

    public function __construct(PlantServiceInterface $plantService, LoggingServiceInterface $logger)
    {
        $this->plantService = $plantService;
        $this->logger = $logger;
    }

    public function fetchAndStorePlants(): void
    {
        $this->logger->log('Début du fetch des plantes');
        $this->plantService->fetchAndStorePlants();
        $this->logger->log('Fin du fetch des plantes');
    }
}