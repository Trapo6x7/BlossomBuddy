<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\UserPlantController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlantSearchController;
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
Route::delete('/plant/{id}', [PlantController::class, 'destroy']);
Route::post('/plant/update', [PlantController::class, 'update']);

// Routes pour la recherche de plantes en français
Route::get('/plants/search', [PlantSearchController::class, 'search'])->middleware('auth:sanctum');
Route::get('/plants/autocomplete', [PlantSearchController::class, 'autocomplete'])->middleware('auth:sanctum');
Route::post('/plants/find-or-suggest', [PlantSearchController::class, 'findOrSuggest'])->middleware('auth:sanctum');
Route::put('/plants/{id}/french-names', [PlantSearchController::class, 'updateFrenchNames'])->middleware('auth:sanctum');

// Route pour l'arrosage
Route::post('/plant/water', [PlantController::class, 'recordWatering'])->middleware('auth:sanctum');
Route::post('/user/plant/water', [UserPlantController::class, 'recordWatering'])->middleware('auth:sanctum');

// Route pour la gestion des plantes d'un utilisateur (avec watering intégré)
Route::post('/user/plant', [UserPlantController::class, 'store'])->middleware('auth:sanctum');
Route::get('/user/plants', [UserPlantController::class, 'index'])->middleware('auth:sanctum');
Route::get('/user/plant/{id}', [UserPlantController::class, 'show'])->middleware('auth:sanctum');
Route::delete('/user/plant/{id}', [UserPlantController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/user/plant/{id}/water', [UserPlantController::class, 'water'])->middleware('auth:sanctum');

// Routes pour les notifications d'arrosage
Route::post('/notifications/test-watering-reminder', [NotificationController::class, 'testWateringReminder'])->middleware('auth:sanctum');
Route::post('/notifications/send-all-reminders', [NotificationController::class, 'sendAllReminders'])->middleware('auth:sanctum');
Route::get('/notifications/my-reminders-preview', [NotificationController::class, 'getMyRemindersPreview'])->middleware('auth:sanctum');