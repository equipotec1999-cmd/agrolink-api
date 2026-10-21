<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Refresca la constelación cada madrugada si hay planificador corriendo.
// Sin planificador (Render free) no pasa nada: /market/* la refresca solo cuando hace falta.
Schedule::command('agrolink:cargar-constelacion')->dailyAt('03:00')->onOneServer();
