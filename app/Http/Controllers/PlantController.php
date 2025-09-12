<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    // Liste toutes les plantes
    public function index()
    {
        return Plant::all();
    }

    // Ajoute une nouvelle plante
    public function store(Request $request)
    {
        $plant = Plant::create($request->all());
        return response()->json($plant, 201);
    }

    // Affiche une plante par son nom
    public function show($name)
    {
        return Plant::where('common_name', $name)->firstOrFail();
    }

    // Supprime une plante par son nom
    public function destroy($name)
    {
        $plant = Plant::where('id', $name)->firstOrFail();
        $plant->delete();
        return response()->json(null, 204);
    }
}
