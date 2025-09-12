<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\UserPlantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route pour l'inscription d'un utilisateur
Route::post('/register', [AuthController::class, 'register']);

// Route pour l'authentification d'un utilisateur
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Route pour la gestion des plantes
Route::get('/plant', [PlantController::class, 'index']);
Route::post('/plant', [PlantController::class, 'store']);
Route::get('/plant/{name}', [PlantController::class, 'show']);
Route::delete('/plant/{name}', [PlantController::class, 'destroy']);

// Route pour la gestion des plantes d'un utilisateur
Route::post('/user/plant', [UserPlantController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/user/plants', [UserPlantController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/user/plant/{id}', [UserPlantController::class, 'destroy'])->middleware('auth:sanctum');