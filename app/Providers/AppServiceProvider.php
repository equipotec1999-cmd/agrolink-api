<?php

namespace App\Providers;

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
    }
}
