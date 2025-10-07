<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillPlantsTest extends TestCase
{
    public function test_backfill_plants_command_runs_successfully()
    {
        $this->artisan('plants:backfill')
            ->expectsOutput('Début du backfill des plantes...')
            ->expectsOutput('Backfill des données des plantes terminé.')
            ->assertExitCode(0);
    }
}
