<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Notifications\WateringReminder;
use App\Services\WeatherApiServiceInterface;
use App\Services\WateringCalculatorServiceInterface;
use Carbon\Carbon;

class NotificationController extends Controller
{
    protected $weatherService;
    protected $wateringCalculator;

    public function __construct(WeatherApiServiceInterface $weatherService, WateringCalculatorServiceInterface $wateringCalculator)
    {
        $this->weatherService = $weatherService;
        $this->wateringCalculator = $wateringCalculator;
    }

    /**
     * @OA\Post(
     *     path="/notifications/test-watering-reminder",
     *     summary="Tester l'envoi d'un rappel d'arrosage pour une plante spécifique",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plant_id","city"},
     *             @OA\Property(property="plant_id", type="integer", example=1),
     *             @OA\Property(property="city", type="string", example="Paris")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Notification de test envoyée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="notification_data", type="object")
     *         )
     *     )
     * )
     */
    public function testWateringReminder(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'plant_id' => 'required|integer|exists:plants,id',
            'city' => 'required|string',
        ]);

        // Vérifier que l'utilisateur a cette plante
        $userPlant = $user->plants()->where('plant_id', $request->plant_id)->wherePivot('city', $request->city)->first();
        if (!$userPlant) {
            return response()->json(['error' => 'Cette plante n\'est pas associée à votre compte pour cette ville'], 404);
        }

        $pivot = $userPlant->pivot;

        // Récupérer la météo
        $weatherData = $this->weatherService->getWeather($request->city);
        
        // Calculer le programme d'arrosage
        $lastWateringDate = $pivot->last_watered_at ?: Carbon::now();
        $wateringSchedule = $this->wateringCalculator->calculateNextWateringTime(
            $userPlant->toArray(),
            $weatherData,
            $lastWateringDate
        );

        // Préparer les données pour la notification
        $plantData = [
            'id' => $userPlant->id,
            'common_name' => $userPlant->common_name,
            'scientific_name' => $userPlant->scientific_name,
            'watering' => $userPlant->watering,
            'watering_general_benchmark' => $userPlant->watering_general_benchmark,
        ];

        $userPlantInfo = [
            'city' => $pivot->city,
            'last_watered_at' => $pivot->last_watered_at,
            'watering_preferences' => $pivot->watering_preferences,
        ];

        // Envoyer la notification de test
        $user->notify(new WateringReminder($plantData, $userPlantInfo, $wateringSchedule, $weatherData));

        return response()->json([
            'message' => 'Notification de test envoyée avec succès !',
            'notification_data' => [
                'plant' => $plantData,
                'watering_schedule' => $wateringSchedule,
                'weather_data' => $weatherData,
                'sent_to' => $user->email,
                'sent_at' => Carbon::now()->toISOString(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/notifications/send-all-reminders",
     *     summary="Déclencher l'envoi de tous les rappels d'arrosage (admin)",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="days_ahead", type="integer", example=1, description="Nombre de jours d'avance pour les rappels")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Rappels envoyés",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="command_output", type="string")
     *         )
     *     )
     * )
     */
    public function sendAllReminders(Request $request)
    {
        $daysAhead = $request->input('days_ahead', 1);
        
        // Exécuter la commande de rappels
        $exitCode = Artisan::call('watering:send-reminders', [
            '--days' => $daysAhead
        ]);
        
        $output = Artisan::output();

        return response()->json([
            'message' => 'Commande de rappels exécutée',
            'exit_code' => $exitCode,
            'command_output' => $output,
            'executed_at' => Carbon::now()->toISOString(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/notifications/my-reminders-preview",
     *     summary="Aperçu des prochains rappels pour l'utilisateur connecté",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200, 
     *         description="Liste des prochains rappels",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="plant", type="object"),
     *                 @OA\Property(property="reminder_date", type="string"),
     *                 @OA\Property(property="urgency", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function getMyRemindersPreview(Request $request)
    {
        $user = $request->user();
        $userPlants = $user->plants()->get();
        $upcomingReminders = [];

        foreach ($userPlants as $plant) {
            $pivot = $plant->pivot;
            $city = $pivot->city;

            try {
                // Récupérer la météo et calculer le programme
                $weatherData = $this->weatherService->getWeather($city);
                $lastWateringDate = $pivot->last_watered_at ?: Carbon::now();
                $wateringSchedule = $this->wateringCalculator->calculateNextWateringTime(
                    $plant->toArray(),
                    $weatherData,
                    $lastWateringDate
                );

                $hoursUntilWatering = $wateringSchedule['hours_until_watering'];
                
                // Déterminer l'urgence
                $urgency = 'normal';
                if ($hoursUntilWatering <= 0) {
                    $urgency = 'urgent';
                } elseif ($hoursUntilWatering <= 24) {
                    $urgency = 'today';
                } elseif ($hoursUntilWatering <= 72) {
                    $urgency = 'soon';
                }

                $upcomingReminders[] = [
                    'plant' => [
                        'id' => $plant->id,
                        'common_name' => $plant->common_name,
                        'city' => $city,
                    ],
                    'watering_schedule' => $wateringSchedule,
                    'reminder_date' => Carbon::now()->addHours($hoursUntilWatering)->toISOString(),
                    'urgency' => $urgency,
                    'will_send_reminder' => $hoursUntilWatering <= 24, // Envoi si dans 24h ou moins
                ];

            } catch (\Exception $e) {
                // En cas d'erreur, continuer avec la plante suivante
                continue;
            }
        }

        // Trier par urgence
        usort($upcomingReminders, function ($a, $b) {
            $urgencyOrder = ['urgent' => 0, 'today' => 1, 'soon' => 2, 'normal' => 3];
            return $urgencyOrder[$a['urgency']] <=> $urgencyOrder[$b['urgency']];
        });

        return response()->json([
            'upcoming_reminders' => $upcomingReminders,
            'summary' => [
                'total_plants' => count($upcomingReminders),
                'urgent' => count(array_filter($upcomingReminders, fn($r) => $r['urgency'] === 'urgent')),
                'today' => count(array_filter($upcomingReminders, fn($r) => $r['urgency'] === 'today')),
                'soon' => count(array_filter($upcomingReminders, fn($r) => $r['urgency'] === 'soon')),
            ]
        ]);
    }
}
