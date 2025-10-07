<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlantApiServiceInterface;

class BackfillPlants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plants:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill des données des plantes depuis Perenual API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(PlantApiServiceInterface $plantApiService)
    {
        $this->info('Début du backfill des plantes...');
        try {
            $plantApiService->updatePlantsFromApi();
            $this->info('Backfill des données des plantes terminé.');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Erreur lors du backfill : ' . $e->getMessage());
            return 1;
        }
    }
}
