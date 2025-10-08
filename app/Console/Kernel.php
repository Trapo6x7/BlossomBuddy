<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Planification de la commande backfill chaque jour à 2h du matin
        $schedule->command('plants:backfill')->dailyAt('02:00');
        
        // Envoi des rappels d'arrosage quotidiens à 8h du matin
        $schedule->command('watering:send-reminders --days=1')->dailyAt('08:00')
            ->description('Envoie les rappels d\'arrosage quotidiens');
            
        // Envoi des rappels urgents (arrosage le jour même) à 18h
        $schedule->command('watering:send-reminders --days=0')->dailyAt('18:00')
            ->description('Envoie les rappels d\'arrosage urgents');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}