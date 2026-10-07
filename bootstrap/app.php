<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Detrás del balanceador de Render/Fly el TLS termina antes de llegar a PHP;
        // sin esto Laravel genera URLs http:// y cree que la petición no es segura.
        $middleware->trustProxies(at: '*');

        // Necesario para que Flutter Web use cookies de sesión de Sanctum (SPA);
        // Android/iOS usan tokens Bearer normales y no pasan por aquí.
        $middleware->alias([
            'full.session' => \App\Http\Middleware\EnsureFullSession::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'two-factor' => \App\Http\Middleware\RequireTwoFactor::class,
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
