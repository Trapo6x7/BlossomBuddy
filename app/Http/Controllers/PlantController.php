<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Services\PlantApiServiceInterface;
use App\Services\WeatherApiServiceInterface;
use Illuminate\Http\Request;

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

        $city = $request->input('ville');
        $weather = $city ? $weatherApiService->getWeather($city) : null;

        return response()->json([
            'plant' => $plant,
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
    public function update(PlantApiServiceInterface $plantApiService)
    {
        $plantApiService->updatePlantsFromApi();
        return response()->json(['message' => 'Plantes mises à jour']);
    }
}
