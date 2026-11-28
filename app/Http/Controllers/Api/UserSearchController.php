<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Buscar perfiles por nombre (pestaña «Perfiles» del buscador). Primero los destacados,
 * luego los verificados y los que más publicaciones activas tienen. Sin correo ni teléfono.
 */
class UserSearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = min(max($request->integer('limit', 20), 1), 40);

        $activeListings = DB::table('publicaciones')->selectRaw('count(*)')
            ->whereColumn('publicaciones.usuario_id', 'usuarios.id')
            ->where('publicaciones.estatus', 'publicada')
            ->whereNull('publicaciones.eliminado_en');

        $users = User::query()
            ->with(['profile', 'sellerProfile'])
            ->select('usuarios.*')
            ->selectSub($activeListings, 'publicaciones_activas')
            ->whereNull('eliminado_en')
            ->where(fn ($w) => $w->whereNull('suspendido_hasta')->orWhere('suspendido_hasta', '<=', now()))
            ->when($q !== '', function ($query) use ($q) {
                foreach (preg_split('/\s+/', mb_substr($q, 0, 60)) as $word) {
                    $query->whereRaw(
                        "immutable_unaccent(lower(coalesce(nombre, '') || ' ' || coalesce(apellidos, ''))) LIKE immutable_unaccent(lower(?))",
                        ['%'.addcslashes($word, '%_\\').'%']
                    );
                }
            })
            // Sin texto: solo cuentas con algo publicado (sugerencias), destacadas primero.
            ->when($q === '', fn ($query) => $query->whereExists(fn ($e) => $e->selectRaw('1')->from('publicaciones')
                ->whereColumn('publicaciones.usuario_id', 'usuarios.id')
                ->where('publicaciones.estatus', 'publicada')
                ->whereNull('publicaciones.eliminado_en')))
            ->orderByRaw('CASE WHEN destacado_hasta > NOW() THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM perfiles_vendedor pv WHERE pv.usuario_id = usuarios.id AND pv.verificado) THEN 0 ELSE 1 END')
            ->orderByDesc('publicaciones_activas')
            ->orderBy('usuarios.id')
            ->limit($limit)
            ->get();

        $disk = Storage::disk(config('filesystems.default'));

        return response()->json(['data' => $users->map(fn (User $u) => [
            'id' => $u->id,
            'name' => trim(($u->nombre ?? '').' '.($u->apellidos ?? '')) ?: 'Usuario',
            'avatar_url' => $u->profile?->ruta_avatar ? $disk->url($u->profile->ruta_avatar) : null,
            'state' => $u->profile?->estado,
            'municipality' => $u->profile?->municipio,
            'is_verified' => (bool) $u->sellerProfile?->verificado,
            'is_featured' => $u->destacado_hasta !== null && now()->lessThan($u->destacado_hasta),
            'listings_count' => (int) $u->publicaciones_activas,
        ])->values()]);
    }
}
