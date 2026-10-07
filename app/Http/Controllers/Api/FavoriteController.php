<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * Publicaciones guardadas del usuario (más recientes primero). Solo las que
     * siguen publicadas: si el vendedor archiva/borra una, deja de aparecer aquí
     * pero la fila de favorito se conserva por si se vuelve a publicar.
     */
    public function index(Request $request)
    {
        $listings = Listing::query()
            ->with([
                'productType', 'user.sellerProfile', 'media',
                'location' => fn ($q) => $q->addSelect([
                    DB::raw('ST_Y(approx_location::geometry) as approx_lat'),
                    DB::raw('ST_X(approx_location::geometry) as approx_lng'),
                ]),
            ])
            ->where('listings.status', 'published')
            ->join('favorites', 'favorites.listing_id', '=', 'listings.id')
            ->where('favorites.user_id', $request->user()->id)
            ->orderByDesc('favorites.created_at')
            ->select('listings.*')
            ->paginate(50);

        return ListingResource::collection($listings);
    }

    /** Solo los ids: la app los usa para pintar el corazón sin bajar cada listing. */
    public function ids(Request $request)
    {
        $ids = $request->user()->favorites()
            ->whereHas('listing', fn ($q) => $q->where('status', 'published'))
            ->pluck('listing_id');

        return response()->json(['data' => $ids]);
    }

    /** Idempotente: guardar dos veces el mismo listing no falla ni duplica. */
    public function store(Request $request, Listing $listing)
    {
        abort_unless($listing->status === 'published', 404);

        $request->user()->favorites()->firstOrCreate(['listing_id' => $listing->id]);

        return response()->noContent();
    }

    /** Idempotente: quitar algo que no estaba guardado tampoco falla. */
    public function destroy(Request $request, Listing $listing)
    {
        $request->user()->favorites()->where('listing_id', $listing->id)->delete();

        return response()->noContent();
    }
}
