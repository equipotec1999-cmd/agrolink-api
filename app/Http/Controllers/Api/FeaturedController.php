<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeaturedRequest;
use App\Models\Listing;
use App\Support\FeaturedPricing;
use Illuminate\Http\Request;

/**
 * Destacados pagados desde el lado del usuario: cotizar, pedir y ver sus solicitudes.
 * Mientras FEATURED_SALES_ENABLED esté apagado solo se puede cotizar (y la app no lo muestra).
 */
class FeaturedController extends Controller
{
    private function ensureEnabled(): void
    {
        abort_unless(config('agrolink.featured_sales'), 403, 'Destacar publicaciones todavía no está disponible.');
    }

    /** GET /featured/pricing?days=N — precio y si la venta de destacados ya está abierta. */
    public function pricing(Request $request)
    {
        return response()->json(['data' => FeaturedPricing::quote($request->integer('days', 1)) + [
            'enabled' => (bool) config('agrolink.featured_sales'),
            'first_day' => FeaturedPricing::FIRST_DAY,
            'min_day' => FeaturedPricing::MIN_DAY,
        ]]);
    }

    /** POST /featured/requests — {type: listing|profile, listing_id?, days} */
    public function store(Request $request)
    {
        $this->ensureEnabled();
        $data = $request->validate([
            'type' => ['required', 'in:listing,profile'],
            'listing_id' => ['required_if:type,listing', 'nullable', 'integer'],
            'days' => ['required', 'integer', 'min:1', 'max:'.FeaturedPricing::MAX_DAYS],
        ]);
        $user = $request->user();

        $listingId = null;
        if ($data['type'] === 'listing') {
            $listing = Listing::query()->findOrFail($data['listing_id']);
            abort_unless($listing->usuario_id === $user->id, 403, 'Solo puedes destacar tus propias publicaciones.');
            abort_if(in_array($listing->estatus, ['borrador', 'rechazada', 'suspendida'], true), 422, 'Esta publicación no se puede destacar en su estado actual.');
            $listingId = $listing->id;
        }

        $pending = FeaturedRequest::query()
            ->where('usuario_id', $user->id)
            ->where('estatus', 'pendiente_pago')
            ->where('tipo', $data['type'] === 'profile' ? 'perfil' : 'publicacion')
            ->when($listingId, fn ($q) => $q->where('publicacion_id', $listingId))
            ->exists();
        abort_if($pending, 422, 'Ya tienes una solicitud pendiente de pago para esto.');

        $req = FeaturedRequest::create([
            'usuario_id' => $user->id,
            'tipo' => $data['type'] === 'profile' ? 'perfil' : 'publicacion',
            'publicacion_id' => $listingId,
            'dias' => $data['days'],
            'monto' => FeaturedPricing::total($data['days']),
            'moneda' => 'MXN',
            'estatus' => 'pendiente_pago',
        ]);

        return response()->json(['data' => $req->load('listing', 'user')->toApi() + [
            'payment_instructions' => config('agrolink.featured_payment_instructions'),
        ]], 201);
    }

    /** GET /my/featured-requests */
    public function mine(Request $request)
    {
        $items = FeaturedRequest::query()->with(['listing', 'user'])
            ->where('usuario_id', $request->user()->id)
            ->orderByDesc('id')->limit(50)->get();

        return response()->json(['data' => $items->map->toApi()->values()]);
    }

    /** POST /featured/requests/{id}/cancel — solo mientras esté pendiente de pago. */
    public function cancel(Request $request, FeaturedRequest $featuredRequest)
    {
        abort_unless($featuredRequest->usuario_id === $request->user()->id, 404);
        abort_unless($featuredRequest->estatus === 'pendiente_pago', 422, 'Esta solicitud ya no se puede cancelar.');
        $featuredRequest->update(['estatus' => 'cancelada']);

        return response()->json(['data' => $featuredRequest->toApi()]);
    }
}
