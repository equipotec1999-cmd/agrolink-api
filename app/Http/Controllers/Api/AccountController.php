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
            'lastname' => ['sometimes', 'nullable', 'string', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:280'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'municipality' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        // Campos propios del usuario (identidad y teléfono).
        $userFields = [];
        if (array_key_exists('name', $data)) {
            $userFields['nombre'] = $data['name'];
        }
        if (array_key_exists('lastname', $data)) {
            $userFields['apellidos'] = $data['lastname'];
        }
        if (array_key_exists('phone', $data)) {
            $userFields['telefono'] = $data['phone'];
        }
        if ($userFields) {
            $user->update($userFields);
        }

        // Campos del perfil (bio, lugar). Se crea el perfil si todavía no existe.
        $profileFields = [];
        if (array_key_exists('bio', $data)) {
            $profileFields['biografia'] = $data['bio'];
        }
        if (array_key_exists('state', $data)) {
            $profileFields['estado'] = $data['state'];
        }
        if (array_key_exists('municipality', $data)) {
            $profileFields['municipio'] = $data['municipality'];
        }
        if ($profileFields) {
            \App\Models\Profile::updateOrCreate(['usuario_id' => $user->id], $profileFields);
        }

        return new UserResource($user->fresh()->load(['profile', 'sellerProfile']));
    }

    /** Sube o reemplaza la foto de perfil. Devuelve el usuario con el avatar actualizado. */
    public function avatar(Request $request)
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();
        $disk = config('filesystems.default');

        // El perfil puede no existir aún (cuentas creadas antes de la migración de perfiles).
        $profile = \App\Models\Profile::firstOrCreate(['usuario_id' => $user->id]);

        // Borra el avatar anterior para no acumular archivos huérfanos.
        if ($profile->ruta_avatar) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($profile->ruta_avatar);
        }

        $path = $data['avatar']->storeAs(
            "avatares/{$user->id}",
            \Illuminate\Support\Str::random(32).'.'.$data['avatar']->getClientOriginalExtension(),
            ['disk' => $disk]
        );
        $profile->update(['ruta_avatar' => $path]);

        return new UserResource($user->fresh()->load(['profile', 'sellerProfile']));
    }

    /** Elimina la foto de perfil. */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        if ($profile && $profile->ruta_avatar) {
            \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->delete($profile->ruta_avatar);
            $profile->update(['ruta_avatar' => null]);
        }

        return new UserResource($user->fresh()->load(['profile', 'sellerProfile']));
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
