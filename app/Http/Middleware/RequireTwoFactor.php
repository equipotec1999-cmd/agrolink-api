<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Las cuentas con permisos administrativos DEBEN tener la verificación en dos pasos activa
 * (Fase 1 §21) para usar las rutas protegidas con este middleware.
 */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->needsTwoFactor() && ! $user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Tu cuenta tiene permisos administrativos: activa la verificación en dos pasos (Perfil → Seguridad) para continuar.',
                'code' => 'two_factor_setup_required',
            ], 403);
        }

        return $next($request);
    }
}
