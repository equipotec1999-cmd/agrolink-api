<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Listing;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Datos de la propia cuenta: nombre/teléfono y contraseña. */
class AccountController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $user->update(array_filter([
            'nombre' => $data['name'] ?? null,
        ], fn ($v) => $v !== null) + (array_key_exists('phone', $data) ? ['telefono' => $data['phone']] : []));

        return new UserResource($user->load(['profile', 'sellerProfile']));
    }

    /** Cambia la contraseña y cierra las demás sesiones (el resto de los celulares). */
    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->contrasena)) {
            throw ValidationException::withMessages(['current_password' => ['La contraseña actual no es correcta.']]);
        }

        $user->update(['contrasena' => $data['password']]);
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Contraseña actualizada.']);
    }

    /** Cifras reales del perfil: publicaciones activas, ventas, compras y reputación. */
    public function stats(Request $request)
    {
        $user = $request->user();
        $profile = $user->sellerProfile;

        // Reputación = promedio de las tres calificaciones; sin operaciones completadas no hay dato.
        $rating = null;
        if ($profile && (int) $profile->operaciones_completadas > 0) {
            $rating = round(((float) $profile->calificacion_exactitud + (float) $profile->calificacion_cumplimiento + (float) $profile->calificacion_comunicacion) / 3, 1);
        }

        return response()->json(['data' => [
            'listings' => Listing::where('usuario_id', $user->id)->where('estatus', 'publicada')->count(),
            'sales' => Operation::where('vendedor_id', $user->id)->where('estatus', '!=', 'cancelado')->count(),
            'purchases' => Operation::where('comprador_id', $user->id)->where('estatus', '!=', 'cancelado')->count(),
            'rating' => $rating,
        ]]);
    }
}
