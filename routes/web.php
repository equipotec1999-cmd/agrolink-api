<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Textos legales públicos (liga para Google Play y para compartir).
Route::get('/privacidad', [\App\Http\Controllers\Api\LegalController::class, 'page'])->defaults('doc', 'privacidad');
Route::get('/terminos', [\App\Http\Controllers\Api\LegalController::class, 'page'])->defaults('doc', 'terminos');
