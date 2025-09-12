<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plant;
use Illuminate\Support\Facades\Validator;

class UserPlantController extends Controller
{
	// Lister les plantes de l'utilisateur
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
	public function store(Request $request)
	{
		$user = $request->user();

		// Validation des champs
		$validator = Validator::make($request->all(), [
			'plant_name' => 'required|string',
			'city' => 'required|string',
		]);
		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 422);
		}

		// Recherche de la plante par son nom
		$plant = Plant::where('common_name', $request->plant_name)->first();
		if (!$plant) {
			return response()->json(['error' => 'Plante non trouvée'], 404);
		}

		// Ajout dans la table pivot avec la ville
		$user->plants()->attach($plant->id, ['city' => $request->city]);

		return response()->json(['message' => 'Plante ajoutée à l’utilisateur']);
	}

	// Afficher une plante précise de l'utilisateur
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
}
