<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\WateringReminder;
use App\Services\WeatherApiServiceInterface;
use App\Services\WateringCalculatorServiceInterface;
use Carbon\Carbon;

class SendWateringReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'watering:send-reminders {--days=1 : Nombre de jours d\'avance pour les rappels} {--test-user= : ID utilisateur pour test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie les rappels d\'arrosage aux utilisateurs selon leur programme';

    protected $weatherService;
    protected $wateringCalculator;

    public function __construct(WeatherApiServiceInterface $weatherService, WateringCalculatorServiceInterface $wateringCalculator)
    {
        parent::__construct();
        $this->weatherService = $weatherService;
        $this->wateringCalculator = $wateringCalculator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysAhead = (int) $this->option('days');
        $testUserId = $this->option('test-user');
        
        $this->info(" Envoi des rappels d'arrosage (dans {$daysAhead} jour(s))...");
        
        // Si mode test, ne traiter qu'un utilisateur
        $users = $testUserId 
            ? User::where('id', $testUserId)->get()
            : User::has('plants')->get();
            
        if ($users->isEmpty()) {
            $this->warn('Aucun utilisateur avec des plantes trouvé.');
            return;
        }

        $totalNotifications = 0;
        $totalUsers = 0;

        foreach ($users as $user) {
            $userNotifications = $this->processUserNotifications($user, $daysAhead);
            
            if ($userNotifications > 0) {
                $totalUsers++;
                $totalNotifications += $userNotifications;
                $this->info(" {$userNotifications} notification(s) envoyée(s) à {$user->prenom} {$user->nom}");
            }
        }

        $this->info("\n Résumé :");
        $this->info(" {$totalNotifications} notification(s) envoyée(s)");
        $this->info(" {$totalUsers} utilisateur(s) notifié(s)");
        
        return Command::SUCCESS;
    }

    /**
     * Process notifications for a specific user
     */
    private function processUserNotifications(User $user, int $daysAhead): int
    {
        $userPlants = $user->plants()->get();
        $sentNotifications = 0;

        foreach ($userPlants as $plant) {
            $pivot = $plant->pivot;
            $city = $pivot->city;

            try {
                // Récupérer la météo
                $weatherData = $this->weatherService->getWeather($city);
                
                // Calculer le programme d'arrosage
                $lastWateringDate = $pivot->last_watered_at ?: Carbon::now();
                $wateringSchedule = $this->wateringCalculator->calculateNextWateringTime(
                    $plant->toArray(),
                    $weatherData,
                    $lastWateringDate
                );

                // Déterminer si on doit envoyer une notification
                $hoursUntilWatering = $wateringSchedule['hours_until_watering'];
                $shouldSendNotification = $this->shouldSendNotification($hoursUntilWatering, $daysAhead);

                if ($shouldSendNotification) {
                    // Préparer les données pour la notification
                    $plantData = [
                        'id' => $plant->id,
                        'common_name' => $plant->common_name,
                        'scientific_name' => $plant->scientific_name,
                        'watering' => $plant->watering,
                        'watering_general_benchmark' => $plant->watering_general_benchmark,
                    ];

                    $userPlantInfo = [
                        'city' => $pivot->city,
                        'last_watered_at' => $pivot->last_watered_at,
                        'watering_preferences' => $pivot->watering_preferences,
                    ];

                    // Envoyer la notification
                    $user->notify(new WateringReminder($plantData, $userPlantInfo, $wateringSchedule, $weatherData));
                    $sentNotifications++;

                    $this->line("  Notification envoyée pour {$plant->common_name} ({$city})");
                }

            } catch (\Exception $e) {
                $this->error("❌ Erreur pour {$plant->common_name} de {$user->prenom} : " . $e->getMessage());
            }
        }

        return $sentNotifications;
    }

    /**
     * Determine if a notification should be sent based on watering schedule
     */
    private function shouldSendNotification(int $hoursUntilWatering, int $daysAhead): bool
    {
        $maxHours = $daysAhead * 24;
        
        // Envoyer si :
        // 1. Arrosage urgent (maintenant ou en retard)
        // 2. Arrosage dans la fenêtre de rappel (ex: dans 1 jour)
        return $hoursUntilWatering <= $maxHours;
    }
}
