<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Perfil público de un usuario: lo que cualquiera puede ver (nombre, foto, zona, reputación
 * y cuántas publicaciones activas tiene). Sin correo ni teléfono.
 */
class PublicProfileController extends Controller
{
    public function show(int $user)
    {
        $u = User::with(['profile', 'sellerProfile'])->findOrFail($user);
        abort_if($u->isBanned(), 404);
        // Cuenta sancionada: igual que en el feed, no se muestra mientras dure la sanción.
        abort_if($u->suspendido_hasta && now()->lessThan(\Carbon\Carbon::parse($u->suspendido_hasta)), 404);

        $stats = Review::query()->where('evaluado_id', $u->id)->whereNotNull('exactitud')
            ->selectRaw('COUNT(*) AS total, ROUND(AVG((exactitud + cumplimiento + comunicacion) / 3.0)::numeric, 2) AS promedio')
            ->first();

        $avatar = $u->profile?->ruta_avatar;

        return response()->json(['data' => [
            'id' => $u->id,
            'name' => trim(($u->nombre ?? '').' '.($u->apellidos ?? '')) ?: 'Usuario',
            'avatar_url' => $avatar ? Storage::disk(config('filesystems.default'))->url($avatar) : null,
            'bio' => $u->profile?->biografia,
            'state' => $u->profile?->estado,
            'municipality' => $u->profile?->municipio,
            'is_verified' => (bool) $u->sellerProfile?->verificado,
            'is_featured' => $u->destacado_hasta !== null && now()->lessThan($u->destacado_hasta),
            'featured_until' => $u->destacado_hasta,
            'member_since' => $u->creado_en?->year,
            'completed_operations' => (int) ($u->sellerProfile?->operaciones_completadas ?? 0),
            'reviews_count' => (int) ($stats->total ?? 0),
            'average' => (float) ($stats->promedio ?? 0),
            'listings_count' => Listing::query()->where('usuario_id', $u->id)->where('estatus', 'publicada')->count(),
        ]]);
    }
}
