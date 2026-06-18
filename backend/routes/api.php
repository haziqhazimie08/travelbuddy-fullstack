<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\ProfileController;

// ─── Public Routes ────────────────────────────────────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// Public shared itinerary view (via unique token)
Route::get('/shared/{token}', [ItineraryController::class, 'showByToken']);

// ─── Protected Routes ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Authenticated user
    Route::get('/user', fn(Request $request) => $request->user());

    // ── Profile ────────────────────────────────────────────────────────────────
    Route::get('/profile',                [ProfileController::class, 'show']);
    Route::put('/profile/user',           [ProfileController::class, 'updateUser']);
    Route::put('/profile',                [ProfileController::class, 'updateProfile']);
    Route::post('/profile/picture',       [ProfileController::class, 'uploadPicture']);

    // ── Preferences ─────────────────────────────────────────────────────────────
    Route::get('/preferences',            [PreferenceController::class, 'show']);
    Route::post('/preferences',           [PreferenceController::class, 'store']);
    Route::put('/preferences',            [PreferenceController::class, 'store']);

    // ── Itineraries ─────────────────────────────────────────────────────────────
    Route::post('/itineraries/generate',  [ItineraryController::class, 'generate']);
    Route::get('/itineraries',            [ItineraryController::class, 'index']);
    Route::get('/itineraries/{id}',       [ItineraryController::class, 'show']);
    Route::put('/itineraries/{id}',       [ItineraryController::class, 'update']);
    Route::delete('/itineraries/{id}',    [ItineraryController::class, 'destroy']);

    // Place management within a trip
    Route::post('/itineraries/{id}/places',              [ItineraryController::class, 'addPlace']);
    Route::delete('/itineraries/{id}/places/{placeId}',  [ItineraryController::class, 'deletePlace']);

    // Save / unsave itineraries
    Route::post('/itineraries/{id}/save',   [ItineraryController::class, 'save']);
    Route::delete('/itineraries/{id}/save', [ItineraryController::class, 'unsave']);

    // Share link generation & direct user sharing
    Route::get('/itineraries/{id}/share-link', [ItineraryController::class, 'getShareLink']);
    Route::post('/itineraries/{id}/share',     [ItineraryController::class, 'share']);
});
