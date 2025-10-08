<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plant;
use Illuminate\Support\Facades\Validator;
use App\Services\WeatherApiServiceInterface;
use App\Services\GeocodingService;
use App\Services\PlantSearchService;

class UserPlantController extends Controller
{
	// Lister les plantes de l'utilisateur
		/**
		 * @OA\Get(
		 *     path="/user/plants",
		 *     summary="Liste les plantes de l'utilisateur connecté",
		 *     tags={"UserPlant"},
		 *     security={{"sanctum":{}}},
		 *     @OA\Response(response=200, description="Liste des plantes de l'utilisateur")
		 * )
		 */
	public function index(Request $request)
	{
		$user = $request->user();
	
		return response()->json($user->plants);
	}

	// Ajouter une plante à l'utilisateur
	/**
	 * Ajoute une plante à l'utilisateur en fonction du nom et de la ville
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
		/**
		 * @OA\Post(
		 *     path="/user/plant",
		 *     summary="Ajoute une plante à l'utilisateur",
		 *     tags={"UserPlant"},
		 *     security={{"sanctum":{}}},
		 *     @OA\RequestBody(
		 *         required=true,
		 *         @OA\JsonContent(
		 *             required={"plant_name","city"},
		 *             @OA\Property(property="plant_name", type="string", example="Test"),
		 *             @OA\Property(property="city", type="string", example="Lyon")
		 *         )
		 *     ),
		 *     @OA\Response(response=200, description="Plante ajoutée à l'utilisateur")
		 * )
		 */
	public function store(Request $request, WeatherApiServiceInterface $weatherService, GeocodingService $geocodingService, PlantSearchService $plantSearchService)
	{
		$user = $request->user();

		// Validation des champs avec support GPS
		$validator = Validator::make($request->all(), [
			'plant_name' => 'required|string',
			'city' => 'nullable|string',
			'latitude' => 'nullable|numeric|between:-90,90',
			'longitude' => 'nullable|numeric|between:-180,180',
			'location_name' => 'nullable|string|max:255',
		]);
		
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		// Au moins une localisation doit être fournie
		if (!$request->city && (!$request->latitude || !$request->longitude)) {
			return response()->json([
				'error' => 'Au moins une localisation doit être fournie (ville ou coordonnées GPS)'
			], 422);
		}

		// Recherche de la plante par son nom (français ou anglais)
		$plant = $plantSearchService->findPlantByName($request->plant_name);
		if (!$plant) {
			return response()->json([
				'error' => 'Plante non trouvée. Noms supportés: français et anglais'
			], 404);
		}

		// Préparer les données de localisation pour la table pivot
		$pivotData = [
			'city' => $request->city,
			'latitude' => $request->latitude,
			'longitude' => $request->longitude,
			'location_name' => $request->location_name,
		];

		// Si pas de coordonnées mais une ville fournie, essayer de géocoder automatiquement
		if (!$request->latitude && !$request->longitude && $request->city) {
			$geocodedData = $geocodingService->getCoordinatesFromCity($request->city);
			if ($geocodedData) {
				$pivotData['latitude'] = $geocodedData['latitude'];
				$pivotData['longitude'] = $geocodedData['longitude'];
				if (!$request->location_name) {
					$pivotData['location_name'] = $geocodedData['formatted_address'];
				}
			}
		}

		// Récupérer les données météo avec priorité GPS
		$weatherData = null;
		$locationInfo = [];
		
		try {
			// Utiliser les coordonnées (fournies ou géocodées) ou la ville
			$weatherData = $weatherService->getWeatherData(
				$request->city,
				$pivotData['latitude'],  // Peut venir du request ou du géocodage
				$pivotData['longitude']  // Peut venir du request ou du géocodage
			);

			// Extraire les informations de localisation de la réponse météo
			if ($weatherData) {
				$coordinates = $weatherService->extractCoordinates($weatherData);
				$locationName = $weatherService->extractLocationName($weatherData);
				
				$locationInfo = [
					'coordinates' => $coordinates,
					'location_name' => $locationName,
					'query_type' => $weatherData['_query_type'] ?? 'unknown'
				];

				// Si on a utilisé une ville et qu'on obtient des coordonnées, les sauvegarder
				if (!$request->latitude && !$request->longitude && $coordinates) {
					$pivotData['latitude'] = $coordinates['latitude'];
					$pivotData['longitude'] = $coordinates['longitude'];
				}

				// Si pas de nom de lieu fourni, utiliser celui de l'API météo
				if (!$request->location_name && $locationName) {
					$pivotData['location_name'] = $locationName;
				}
			}
		} catch (\Exception $e) {
			// En cas d'erreur météo, continuer sans les données météo
			$weatherData = ['error' => $e->getMessage()];
		}

		// Calculer la prochaine date d'arrosage (maintenant + fréquence de la plante)
		$now = new \DateTime();
		$nextWateringDate = $plant->calculateNextWateringDate($now);
		$pivotData['next_watering_at'] = $nextWateringDate->format('Y-m-d H:i:s');

		// Ajout dans la table pivot avec toutes les données de localisation
		$user->plants()->attach($plant->id, $pivotData);

		return response()->json([
			'message' => 'Plante ajoutée à l\'utilisateur',
			'plant' => $plant,
			'location' => $locationInfo,
			'weather_data' => $weatherData
		]);
	}

	// Afficher une plante précise de l'utilisateur
		/**
		 * @OA\Get(
		 *     path="/user/plant/{id}",
		 *     summary="Affiche une plante précise de l'utilisateur",
		 *     tags={"UserPlant"},
		 *     security={{"sanctum":{}}},
		 *     @OA\Parameter(
		 *         name="id",
		 *         in="path",
		 *         required=true,
		 *         @OA\Schema(type="integer")
		 *     ),
		 *     @OA\Response(response=200, description="Détails de la plante de l'utilisateur")
		 * )
		 */
	public function show(Request $request, $id)
	{
		$user = $request->user();
		$plant = $user->plants()->find($id);
		if (!$plant) {
			return response()->json(['error' => 'Plante non trouvée'], 404);
		}
		return response()->json($plant);
	}

	// Supprimer une plante de l'utilisateur
		/**
		 * @OA\Delete(
		 *     path="/user/plant/{id}",
		 *     summary="Supprime une plante de l'utilisateur",
		 *     tags={"UserPlant"},
		 *     security={{"sanctum":{}}},
		 *     @OA\Parameter(
		 *         name="id",
		 *         in="path",
		 *         required=true,
		 *         @OA\Schema(type="integer")
		 *     ),
		 *     @OA\Response(response=200, description="Plante supprimée de l'utilisateur")
		 * )
		 */
	public function destroy(Request $request, $id = null)
	{
		$user = $request->user();
		if ($id) {
			$user->plants()->detach($id);
			return response()->json(['message' => 'Plante supprimée de l’utilisateur']);
		}
		// Si pas d'id, on peut supprimer via plant_id dans le body
		$plantId = $request->input('plant_id');
		if ($plantId) {
			$user->plants()->detach($plantId);
			return response()->json(['message' => 'Plante supprimée de l’utilisateur']);
		}
		return response()->json(['error' => 'Aucune plante spécifiée'], 400);
	}

	// Arroser une plante de l'utilisateur
	/**
	 * @OA\Post(
	 *     path="/user/plant/{id}/water",
	 *     summary="Enregistre l'arrosage d'une plante de l'utilisateur",
	 *     tags={"UserPlant"},
	 *     security={{"sanctum":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         @OA\Schema(type="integer")
	 *     ),
	 *     @OA\RequestBody(
	 *         required=false,
	 *         @OA\JsonContent(
	 *             @OA\Property(property="watered_at", type="string", format="date-time", example="2025-10-08 14:30:00")
	 *         )
	 *     ),
	 *     @OA\Response(response=200, description="Arrosage enregistré avec succès")
	 * )
	 */
	public function water(Request $request, $id)
	{
		$user = $request->user();
		
		// Vérifier que l'utilisateur a cette plante
		$plant = $user->plants()->find($id);
		if (!$plant) {
			return response()->json(['error' => 'Plante non trouvée'], 404);
		}

		// Date d'arrosage (maintenant ou date fournie)
		$wateredAt = $request->input('watered_at') ? 
			new \DateTime($request->input('watered_at')) : 
			new \DateTime();

		// Calculer la prochaine date d'arrosage
		$nextWateringDate = $plant->calculateNextWateringDate($wateredAt);

		// Mettre à jour les données d'arrosage dans la table pivot
		$user->plants()->updateExistingPivot($id, [
			'last_watered_at' => $wateredAt->format('Y-m-d H:i:s'),
			'next_watering_at' => $nextWateringDate->format('Y-m-d H:i:s'),
		]);

		// Récupérer les données mises à jour
		$updatedPlant = $user->plants()->find($id);

		return response()->json([
			'message' => 'Arrosage enregistré avec succès',
			'plant' => $updatedPlant,
			'last_watered_at' => $wateredAt->format('Y-m-d H:i:s'),
			'next_watering_at' => $nextWateringDate->format('Y-m-d H:i:s'),
		]);
	}
}
