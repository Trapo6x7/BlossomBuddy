<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\UserPlant;
use App\Services\PlantApiServiceInterface;
use App\Services\WeatherApiServiceInterface;
use App\Services\WateringCalculatorServiceInterface;
use App\Exceptions\ApiRateLimitException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PlantController extends Controller
{
    // Liste toutes les plantes
    /**
     * @OA\Get(
     *     path="/plant",
     *     summary="Liste toutes les plantes",
     *     tags={"Plant"},
     *     @OA\Response(response=200, description="Liste des plantes")
     * )
     */
    public function index()
    {
        return Plant::all();
    }

    // Ajoute une nouvelle plante
    /**
     * @OA\Post(
     *     path="/plant",
     *     summary="Ajoute une nouvelle plante",
     *     tags={"Plant"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="common_name", type="string", example="Test"),
     *             @OA\Property(property="ville", type="string", example="Paris"),
     *             @OA\Property(property="watering", type="string", example="medium"),
     *             @OA\Property(property="watering_general_benchmark", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Plante créée et météo du lieu",
     *         @OA\JsonContent(
     *             @OA\Property(property="plant", type="object"),
     *             @OA\Property(property="weather", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request, WeatherApiServiceInterface $weatherApiService, PlantApiServiceInterface $plantApiService)
    {
        
        // Récupère les données de la plante depuis l'API externe
        $plantData = $plantApiService->getPlantData($request->input('common_name'));
        $image = $plantData['default_image'] ?? [];

        $data = $request->all();
        $data['image_url']     = $image['original_url'] ?? null;
        $data['thumbnail_url'] = $image['thumbnail'] ?? null;
        $data['medium_url']    = $image['medium_url'] ?? null;
        $data['regular_url']   = $image['regular_url'] ?? null;
        $data['license']       = $image['license'] ?? null;
        $data['license_name']  = $image['license_name'] ?? null;
        $data['license_url']   = $image['license_url'] ?? null;

        $plant = Plant::create($data);

        // Traduction automatique de la nouvelle plante
        $this->attemptAutoTranslation($plant);

        $city = $request->input('ville');
        $weather = $city ? $weatherApiService->getWeather($city) : null;

        return response()->json([
            'plant' => $plant->fresh(), // Recharger pour inclure la traduction
            'weather' => $weather
        ], 201);
    }

    // Affiche une plante par son nom
    /**
     * @OA\Get(
     *     path="/plant/{name}",
     *     summary="Affiche une plante par son nom",
     *     tags={"Plant"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Détails de la plante")
     * )
     */
    public function show($name)
    {
        return Plant::where('common_name', $name)->firstOrFail();
    }

    // Supprime une plante par son nom
    /**
     * @OA\Delete(
     *     path="/plant/{name}",
     *     summary="Supprime une plante par son nom",
     *     tags={"Plant"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=204, description="Plante supprimée")
     * )
     */
    public function destroy($name)
    {
        $plant = Plant::where('id', $name)->firstOrFail();
        $plant->delete();
        return response()->json(null, 204);
    }

    /**
 * @OA\Post(
 *     path="/plant/update",
 *     summary="Met à jour la base de données des plantes depuis l'API externe",
 *     tags={"Plant"},
 *     @OA\Response(
 *         response=200,
 *         description="Plantes mises à jour",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Plantes mises à jour")
 *         )
 *     )
 * )
 */
    /**
     * @OA\Post(
     *     path="/plant/watering-schedule",
     *     summary="Calcule le temps avant le prochain arrosage pour une plante",
     *     tags={"Plant"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="plant_common_name", type="string", example="Rose"),
     *             @OA\Property(property="city", type="string", example="Paris"),
     *             @OA\Property(property="last_watered_at", type="string", format="date-time", example="2025-10-06T10:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Calcul du prochain arrosage",
     *         @OA\JsonContent(
     *             @OA\Property(property="user_plant", type="object"),
     *             @OA\Property(property="watering_schedule", type="object"),
     *             @OA\Property(property="weather_data", type="object")
     *         )
     *     )
     * )
     */
    public function calculateWateringSchedule(
        Request $request, 
        WeatherApiServiceInterface $weatherService,
        WateringCalculatorServiceInterface $wateringCalculator
    ) {
        // 1. Récupérer l'utilisateur connecté
        $user = $request->user();
        
        // 2. Récupérer les données de la plante depuis la BDD
        $plant = Plant::where('common_name', $request->input('plant_common_name'))->first();
        
        if (!$plant) {
            return response()->json(['error' => 'Plante non trouvée dans la base de données'], 404);
        }

        // 3. Récupérer la météo de la ville
        $city = $request->input('city');
        $weatherData = $weatherService->getWeather($city);

        // 4. Créer ou mettre à jour la relation UserPlant
        $lastWateredAt = $request->input('last_watered_at');
        
        // Vérifier si la relation existe déjà
        $existingUserPlant = UserPlant::where([
            'user_id' => $user->id,
            'plant_id' => $plant->id,
            'city' => $city
        ])->first();
        
        $isNewRelation = !$existingUserPlant;
        
        // Logique pour la date d'arrosage
        if ($lastWateredAt) {
            // L'utilisateur a fourni une date d'arrosage
            $finalLastWateredAt = Carbon::parse($lastWateredAt);
        } elseif ($isNewRelation) {
            // Nouvelle relation sans date d'arrosage : 
            // On considère qu'il vient de l'arroser (approche optimiste)
            $finalLastWateredAt = Carbon::now();
        } else {
            // Relation existante : garder la valeur existante
            $finalLastWateredAt = $existingUserPlant->last_watered_at;
        }
        
        $userPlant = UserPlant::updateOrCreate(
            [
                'user_id' => $user->id,
                'plant_id' => $plant->id,
                'city' => $city
            ],
            [
                'last_watered_at' => $finalLastWateredAt,
                'watering_preferences' => $request->input('watering_preferences', [])
            ]
        );

        // 5. Calculer le prochain arrosage
        $plantData = $plant->toArray();
        
        // Si toujours pas de date d'arrosage (cas edge), utiliser une estimation
        $lastWateringDate = $userPlant->last_watered_at;
        if (!$lastWateringDate) {
            // Fallback : estimer qu'elle a été arrosée selon son cycle normal
            $benchmark = $plant->watering_general_benchmark ?? [];
            $defaultDays = 7; // Par défaut
            if (!empty($benchmark['value'])) {
                $value = $benchmark['value'];
                if (strpos($value, '-') !== false) {
                    $range = explode('-', $value);
                    $defaultDays = (int) round((intval($range[0]) + intval($range[1])) / 2);
                } else {
                    $defaultDays = (int) intval($value);
                }
            }
            // Supposer que la plante a été arrosée il y a la moitié de son cycle
            $lastWateringDate = Carbon::now()->subDays(round($defaultDays / 2));
        }
        
        $wateringSchedule = $wateringCalculator->calculateNextWateringTime(
            $plantData, 
            $weatherData, 
            $lastWateringDate
        );

        // 6. Mettre à jour la date du prochain arrosage
        $userPlant->update([
            'next_watering_at' => $wateringSchedule['next_watering_date']
        ]);

        return response()->json([
            'user_plant' => $userPlant->load('plant'),
            'watering_schedule' => $wateringSchedule,
            'weather_data' => $weatherData
        ]);
    }

    /**
     * @OA\Post(
     *     path="/plant/water",
     *     summary="Enregistrer un arrosage de plante",
     *     description="Met à jour la date du dernier arrosage pour une plante de l'utilisateur",
     *     operationId="waterPlant",
     *     tags={"Plants"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plant_id", "city"},
     *             @OA\Property(property="plant_id", type="integer", description="ID de la plante"),
     *             @OA\Property(property="city", type="string", description="Ville où se trouve la plante"),
     *             @OA\Property(property="watered_at", type="string", format="date-time", description="Date et heure d'arrosage (optionnel, par défaut maintenant)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Arrosage enregistré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user_plant", type="object"),
     *             @OA\Property(property="next_watering_date", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Relation plante-utilisateur non trouvée"
     *     )
     * )
     */
    public function recordWatering(Request $request, WeatherApiServiceInterface $weatherService, WateringCalculatorServiceInterface $wateringCalculator)
    {
        $user = $request->user();
        
        $request->validate([
            'plant_id' => 'required|integer|exists:plant,id',
            'city' => 'required|string|max:255',
            'watered_at' => 'nullable|date'
        ]);
        
        // Trouver la relation UserPlant
        $userPlant = UserPlant::where([
            'user_id' => $user->id,
            'plant_id' => $request->input('plant_id'),
            'city' => $request->input('city')
        ])->first();
        
        if (!$userPlant) {
            return response()->json(['error' => 'Cette plante n\'est pas associée à votre compte pour cette ville'], 404);
        }
        
        // Mettre à jour la date d'arrosage
        $wateredAt = $request->input('watered_at', Carbon::now());
        $userPlant->update([
            'last_watered_at' => Carbon::parse($wateredAt)
        ]);
        
        // Calculer le prochain arrosage
        $plant = $userPlant->plant;
        $weatherData = $weatherService->getWeather($request->input('city'));
        $wateringSchedule = $wateringCalculator->calculateNextWateringTime(
            $plant->toArray(), 
            $weatherData, 
            $userPlant->last_watered_at
        );
        
        return response()->json([
            'message' => 'Arrosage enregistré avec succès',
            'user_plant' => $userPlant->load('plant'),
            'next_watering_date' => $wateringSchedule['next_watering_date'],
            'watering_schedule' => $wateringSchedule
        ]);
    }

    /**
     * @OA\Get(
     *     path="/user/plants-with-schedule",
     *     summary="Récupérer les plantes de l'utilisateur avec leur programme d'arrosage",
     *     description="Retourne toutes les plantes de l'utilisateur avec leur prochain arrosage calculé",
     *     operationId="getUserPlantsWithSchedule",
     *     tags={"Plants"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="Filtrer par ville (optionnel)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des plantes avec programme d'arrosage",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="plant", type="object"),
     *                 @OA\Property(property="watering_schedule", type="object"),
     *                 @OA\Property(property="weather_data", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function getUserPlantsWithSchedule(Request $request, WeatherApiServiceInterface $weatherService, WateringCalculatorServiceInterface $wateringCalculator)
    {
        $user = $request->user();
        $cityFilter = $request->query('city');
        
        // Récupérer les plantes de l'utilisateur
        $query = $user->plants()->with('pivot');
        
        if ($cityFilter) {
            $query->wherePivot('city', $cityFilter);
        }
        
        $userPlants = $query->get();
        $result = [];
        
        foreach ($userPlants as $plant) {
            $pivot = $plant->pivot;
            $city = $pivot->city;
            
            // Récupérer la météo pour cette ville
            $weatherData = $weatherService->getWeather($city);
            
            // Calculer le programme d'arrosage
            $lastWateringDate = $pivot->last_watered_at ?: Carbon::now();
            $wateringSchedule = $wateringCalculator->calculateNextWateringTime(
                $plant->toArray(),
                $weatherData,
                $lastWateringDate
            );
            
            $result[] = [
                'plant' => $plant,
                'user_plant_info' => $pivot,
                'watering_schedule' => $wateringSchedule,
                'weather_data' => $weatherData
            ];
        }
        
        // Trier par urgence (plantes à arroser en premier)
        usort($result, function ($a, $b) {
            return $a['watering_schedule']['hours_until_watering'] <=> $b['watering_schedule']['hours_until_watering'];
        });
        
        return response()->json($result);
    }

    public function update(PlantApiServiceInterface $plantApiService)
    {
        try {
            $plantApiService->updatePlantsFromApi();
            return response()->json(['message' => 'Plantes mises à jour']);
        } catch (ApiRateLimitException $e) {
            return response()->json([
                // 'error' => 'Quota API dépassé',
                'message' => $e->getMessage(),
                // 'rate_limit_info' => $e->getRateLimitInfo()
            ], 429);
        }
    }

    /**
     * Tente une traduction automatique pour une nouvelle plante
     */
    private function attemptAutoTranslation(Plant $plant): void
    {
        // Si déjà traduite, ne rien faire
        if ($plant->french_name) {
            return;
        }

        // Dictionnaire de traduction rapide (version allégée)
        $quickTranslations = [
            // Sapins spécifiques
            'european silver fir' => ['french_name' => 'Sapin pectiné', 'alternatives' => ['Sapin blanc européen', 'Abies alba']],
            'white fir' => ['french_name' => 'Sapin du Colorado', 'alternatives' => ['Sapin blanc', 'Abies concolor']],
            'fraser fir' => ['french_name' => 'Sapin de Fraser', 'alternatives' => ['Abies fraseri']],
            'noble fir' => ['french_name' => 'Sapin noble', 'alternatives' => ['Abies procera']],
            'alpine fir' => ['french_name' => 'Sapin subalpin', 'alternatives' => ['Abies lasiocarpa']],
            
            // Érables spécifiques
            'japanese maple' => ['french_name' => 'Érable du Japon', 'alternatives' => ['Acer palmatum']],
            'amur maple' => ['french_name' => 'Érable de l\'Amour', 'alternatives' => ['Acer ginnala']],
            'paperbark maple' => ['french_name' => 'Érable à écorce de papier', 'alternatives' => ['Acer griseum']],
            'big leaf maple' => ['french_name' => 'Érable à grandes feuilles', 'alternatives' => ['Acer macrophyllum']],
            
            // Plantes d'intérieur courantes
            'monstera deliciosa' => ['french_name' => 'Monstera', 'alternatives' => ['Faux philodendron', 'Plante gruyère']],
            'snake plant' => ['french_name' => 'Sansevieria', 'alternatives' => ['Langue de belle-mère', 'Plante serpent']],
            'rubber plant' => ['french_name' => 'Caoutchouc', 'alternatives' => ['Ficus elastica', 'Hévéa']],
            'spider plant' => ['french_name' => 'Plante araignée', 'alternatives' => ['Chlorophytum', 'Phalangère']],
            'aloe vera' => ['french_name' => 'Aloès', 'alternatives' => ['Aloe vera']],
            'zz plant' => ['french_name' => 'Zamioculcas', 'alternatives' => ['Plante ZZ', 'Zamioculcas zamiifolia']],
            
            // Plantes d'extérieur
            'lavender' => ['french_name' => 'Lavande', 'alternatives' => ['Lavandula']],
            'rosemary' => ['french_name' => 'Romarin', 'alternatives' => ['Rosmarinus']],
            'basil' => ['french_name' => 'Basilic', 'alternatives' => ['Ocimum basilicum']],
            'mint' => ['french_name' => 'Menthe', 'alternatives' => ['Mentha']],
            'rose' => ['french_name' => 'Rose', 'alternatives' => ['Rosier']],
        ];

        $commonName = strtolower(trim($plant->common_name));
        
        // Rechercher une traduction exacte ou partielle
        foreach ($quickTranslations as $english => $translation) {
            if ($commonName === $english || 
                str_contains($commonName, $english) || 
                str_contains($english, $commonName)) {
                
                $plant->update([
                    'french_name' => $translation['french_name'],
                    'alternative_names' => $translation['alternatives']
                ]);
                
                Log::info("Auto-translated plant: {$plant->common_name} → {$translation['french_name']}");
                return;
            }
        }

        // Si aucune traduction automatique trouvée, logger pour suivi
        Log::info("No auto-translation found for plant: {$plant->common_name}");
    }
}
