<?php

namespace App\Http\Controllers;

use App\Services\PlantSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Plant Search",
 *     description="Recherche intelligente de plantes en français"
 * )
 */
class PlantSearchController extends Controller
{
    public function __construct(
        private PlantSearchService $plantSearchService
    ) {}

    /**
     * @OA\Get(
     *     path="/plants/search",
     *     summary="Recherche de plantes par nom français ou anglais",
     *     tags={"Plant Search"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Nom de la plante à rechercher",
     *         @OA\Schema(type="string", example="monstera")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Nombre maximum de résultats",
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Résultats de la recherche",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="plants",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="common_name", type="string", example="Monstera deliciosa"),
     *                     @OA\Property(property="french_name", type="string", example="Monstera"),
     *                     @OA\Property(property="alternative_names", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="watering_general_benchmark", type="object")
     *                 )
     *             ),
     *             @OA\Property(property="count", type="integer", example=1),
     *             @OA\Property(property="query", type="string", example="monstera")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètre de recherche manquant"
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'limit' => 'integer|min:1|max:50'
        ]);

        $query = $request->get('q');
        $limit = $request->get('limit', 10);

        $plants = $this->plantSearchService->searchPlants($query, $limit);

        return response()->json([
            'plants' => $plants,
            'count' => $plants->count(),
            'query' => $query
        ]);
    }

    /**
     * @OA\Get(
     *     path="/plants/autocomplete",
     *     summary="Autocomplétion pour les noms de plantes",
     *     tags={"Plant Search"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Début du nom de plante à compléter",
     *         @OA\Schema(type="string", example="mon")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Nombre maximum de suggestions",
     *         @OA\Schema(type="integer", default=5, minimum=1, maximum=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suggestions d'autocomplétion",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="suggestions",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1, description="ID de la plante (si trouvée en base)"),
     *                     @OA\Property(property="text", type="string", example="Monstera"),
     *                     @OA\Property(property="secondary", type="string", example="Monstera deliciosa"),
     *                     @OA\Property(property="source", type="string", enum={"database", "dictionary"})
     *                 )
     *             ),
     *             @OA\Property(property="count", type="integer", example=3),
     *             @OA\Property(property="query", type="string", example="mon")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètre de recherche invalide"
     *     )
     * )
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'integer|min:1|max:20'
        ]);

        $query = $request->get('q');
        $limit = $request->get('limit', 5);

        $suggestions = $this->plantSearchService->getAutocompleteSuggestions($query, $limit);

        return response()->json([
            'suggestions' => $suggestions,
            'count' => count($suggestions),
            'query' => $query
        ]);
    }

    /**
     * @OA\Post(
     *     path="/plants/find-or-suggest",
     *     summary="Trouve une plante ou suggère des alternatives",
     *     tags={"Plant Search"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="french_name", type="string", example="monstera", description="Nom français de la plante")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plante trouvée ou suggestions",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="found", type="boolean", example=true),
     *                     @OA\Property(property="plant", type="object"),
     *                     @OA\Property(property="confidence", type="string", example="high")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="found", type="boolean", example=false),
     *                     @OA\Property(property="suggestions", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="message", type="string")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Nom de plante manquant"
     *     )
     * )
     */
    public function findOrSuggest(Request $request): JsonResponse
    {
        $request->validate([
            'french_name' => 'required|string|min:1|max:100'
        ]);

        $frenchName = $request->get('french_name');
        $result = $this->plantSearchService->findOrSuggestPlant($frenchName);

        return response()->json($result);
    }

    /**
     * @OA\Put(
     *     path="/plants/{id}/french-names",
     *     summary="Met à jour les noms français d'une plante",
     *     tags={"Plant Search"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la plante",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="french_name", type="string", example="Monstera", description="Nom français principal"),
     *             @OA\Property(
     *                 property="alternative_names", 
     *                 type="array", 
     *                 @OA\Items(type="string"),
     *                 example={"Faux philodendron", "Plante gruyère"},
     *                 description="Noms alternatifs et synonymes"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Noms français mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Noms français mis à jour avec succès"),
     *             @OA\Property(property="plant", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plante non trouvée"
     *     )
     * )
     */
    public function updateFrenchNames(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'french_name' => 'required|string|min:1|max:255',
            'alternative_names' => 'array',
            'alternative_names.*' => 'string|max:255'
        ]);

        $updated = $this->plantSearchService->updatePlantFrenchNames(
            $id,
            $request->get('french_name'),
            $request->get('alternative_names', [])
        );

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Plante non trouvée'
            ], 404);
        }

        // Récupérer la plante mise à jour
        $plant = \App\Models\Plant::find($id);

        return response()->json([
            'success' => true,
            'message' => 'Noms français mis à jour avec succès',
            'plant' => $plant
        ]);
    }
}