<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\GeocodingService;
use App\Models\Ad;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Géocodage inverse (coordonnées -> adresse) - Rate limited
Route::middleware('throttle:30,1,api.reverse-geocode:')->get('/reverse-geocode', function (Request $request) {
    $lat = $request->query('lat');
    $lng = $request->query('lng');
    
    if (!$lat || !$lng) {
        return response()->json(['error' => 'Latitude et longitude requises'], 400);
    }
    
    $geocodingService = new GeocodingService();
    $result = $geocodingService->reverseGeocode((float) $lat, (float) $lng);
    
    return response()->json($result);
});

// Géocodage (adresse -> coordonnées) - Rate limited
Route::middleware('throttle:30,1,api.geocode:')->get('/geocode', function (Request $request) {
    $address = $request->query('address');
    
    if (!$address) {
        return response()->json(['error' => 'Adresse requise'], 400);
    }
    
    $geocodingService = new GeocodingService();
    $result = $geocodingService->geocode($address);
    
    return response()->json($result);
});

// Incrémenter le compteur de partages - Rate limited & auth-aware
Route::middleware('throttle:20,1,api.ads.share:')->post('/ads/{id}/share', function ($id) {
    $ad = Ad::find($id);
    
    if (!$ad) {
        return response()->json(['error' => 'Annonce non trouvée'], 404);
    }
    
    // Prevent share count inflation: max 1 share per session per ad
    $shareKey = 'shared_ad_' . $id;
    if (session()->has($shareKey)) {
        return response()->json([
            'success' => true,
            'shares_count' => $ad->shares_count,
            'already_shared' => true
        ]);
    }
    session()->put($shareKey, true);
    
    $ad->increment('shares_count');
    
    return response()->json([
        'success' => true,
        'shares_count' => $ad->shares_count
    ]);
});
