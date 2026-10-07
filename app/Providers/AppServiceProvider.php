<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Un id no numérico (p. ej. "l3" de datos de prueba viejos en la app) debe dar
        // 404, no un 500 por "invalid input syntax for type bigint".
        Route::pattern('listing', '[0-9]+');
        Route::pattern('conversation', '[0-9]+');
        Route::pattern('offer', '[0-9]+');
        Route::pattern('operation', '[0-9]+');
        Route::pattern('report', '[0-9]+');
        Route::pattern('rule', '[0-9]+');
        Route::pattern('verification', '[0-9]+');
        Route::pattern('document', '[0-9]+');
        Route::pattern('search', '[0-9]+');

        // Tope general de la API (contador propio, separado de los límites estrictos de cada ruta).
        RateLimiter::for('general', fn (Request $r) => Limit::perMinute(120)->by('general|'.($r->user()?->getAuthIdentifier() ?? $r->ip())));
    }
}
