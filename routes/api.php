<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ListingController;
use Illuminate\Support\Facades\Route;

// Auth (Fase 1 §21: rate limiting en login/registro contra fuerza bruta).
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Catálogo: lectura pública, no requiere sesión (se necesita para poder buscar sin cuenta).
Route::get('/categories', [CatalogController::class, 'categories']);
Route::get('/product-types/{productType}/attributes', [CatalogController::class, 'productTypeAttributes']);

// Listings: lectura pública; escritura requiere sesión.
Route::get('/listings', [ListingController::class, 'index']);
Route::get('/listings/{listing}', [ListingController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/listings', [ListingController::class, 'store']);
    Route::patch('/listings/{listing}', [ListingController::class, 'update']);
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy']);
    Route::post('/listings/{listing}/publish', [ListingController::class, 'publish']);
    Route::post('/listings/{listing}/archive', [ListingController::class, 'archive']);
    Route::post('/listings/{listing}/media', [ListingController::class, 'uploadMedia']);
    Route::delete('/listings/{listing}/media/{media}', [ListingController::class, 'destroyMedia']);

    // Favoritos (Fase 4). PUT/DELETE son idempotentes a propósito: la app los
    // reintenta sin riesgo si la red falla a medias.
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::get('/favorites/ids', [FavoriteController::class, 'ids']);
    Route::put('/favorites/{listing}', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{listing}', [FavoriteController::class, 'destroy']);
});
