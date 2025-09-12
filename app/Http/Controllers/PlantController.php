<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    // Liste toutes les plantes
        /**
         * @OA\Get(
         *     path="/api/plant",
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
         *     path="/api/plant",
         *     summary="Ajoute une nouvelle plante",
         *     tags={"Plant"},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             @OA\Property(property="common_name", type="string", example="Test"),
         *             @OA\Property(property="watering_general_benchmark", type="object")
         *         )
         *     ),
         *     @OA\Response(response=201, description="Plante créée")
         * )
         */
    public function store(Request $request)
    {
        $plant = Plant::create($request->all());
        return response()->json($plant, 201);
    }

    // Affiche une plante par son nom
        /**
         * @OA\Get(
         *     path="/api/plant/{name}",
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
         *     path="/api/plant/{name}",
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
}
