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

        $user = $request->user();
        if ($user?->isBanned()) {
            $user->tokens()->delete();
            abort(403, 'Esta cuenta fue eliminada por incumplir las normas de AgroLink.');
        }

        // Cuenta sancionada: puede entrar y leer (ver la notificación, sus operaciones), pero no
        // publicar, escribir, ofertar ni reportar hasta que termine la sanción.
        if ($user?->isSuspended() && ! $request->isMethod('GET') && ! $this->allowedWhileSuspended($request)) {
            abort(403, 'Tu cuenta está sancionada hasta el '.$user->suspendido_hasta->timezone('America/Merida')
                ->format('d/m/Y H:i').'. Motivo: '.($user->motivo_suspension ?: 'incumplir las normas').'.');
        }

        return $next($request);
    }

    /** Lo mínimo para seguir usando la cuenta durante una sanción (y cerrar operaciones ya abiertas). */
    private function allowedWhileSuspended(Request $request): bool
    {
        return $request->is(
            'api/logout', 'api/devices', 'api/devices/unregister', 'api/notifications/*',
            'api/me/password', 'api/me/accept-terms', 'api/me/delete', 'api/operations/*/transition', 'api/operations/*/deliver', 'api/operations/*/pin',
            'api/operations/*/dispute', 'api/operations/*/dispute/reply',
        );
    }
}
