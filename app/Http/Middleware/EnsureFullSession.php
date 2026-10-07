<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bloquea los tokens "a medias": tras la contraseña, una cuenta con verificación en dos pasos
 * recibe un token que SOLO sirve para /two-factor/challenge (habilidad `two-factor-pending`).
 * Un token completo tiene la habilidad `*`.
 */
class EnsureFullSession
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token && method_exists($token, 'can') && ! $token->can('*')) {
            abort(403, 'Completa la verificación en dos pasos para continuar.');
        }

        return $next($request);
    }
}
