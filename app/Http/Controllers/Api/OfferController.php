<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreOfferRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\OfferResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Offer;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    private function participant(Request $request, Conversation $conversation): void
    {
        $me = $request->user()->id;
        abort_unless($conversation->comprador_id === $me || $conversation->vendedor_id === $me, 404);
    }

    /**
     * Envía una oferta (mensaje tipo oferta). Reglas:
     * - Solo una oferta abierta por conversación.
     * - Si la abierta es de la otra persona, la nueva es una CONTRAOFERTA y la anterior pasa a `countered`.
     * - Si la abierta es mía, hay que cancelarla antes de mandar otra.
     */
    public function store(StoreOfferRequest $request, Conversation $conversation)
    {
        $this->participant($request, $conversation);
        $me = $request->user()->id;
        $listing = $conversation->listing;

        abort_unless($listing->estatus === 'published', 422, 'Esta publicación ya no está disponible.');
        abort_if(
            (float) $request->validated('quantity') > (float) $listing->cantidad,
            422,
            "La cantidad ofertada supera la disponible ({$listing->cantidad} {$listing->unidad})."
        );

        $message = DB::transaction(function () use ($request, $conversation, $me) {
            // Serializa ofertas simultáneas de una misma conversación.
            Conversation::whereKey($conversation->id)->lockForUpdate()->first();
            Offer::expireStale($conversation->id);

            $open = Offer::where('conversacion_id', $conversation->id)->where('estatus', 'sent')->first();
            if ($open) {
                abort_if($open->remitente_id === $me, 422, 'Ya tienes una oferta abierta. Cancélala antes de enviar otra.');
                $open->update(['estatus' => 'countered']);
            }

            $offer = Offer::create([
                'conversacion_id' => $conversation->id,
                'remitente_id' => $me,
                'monto' => $request->validated('amount'),
                'cantidad' => $request->validated('quantity'),
                'estatus' => 'sent',
                'vence_en' => now()->addHours(Offer::VIGENCIA_HORAS),
            ]);

            $message = Message::create([
                'conversacion_id' => $conversation->id,
                'remitente_id' => $me,
                'oferta_id' => $offer->id,
            ]);

            $conversation->update(['ultimo_mensaje_en' => $message->creado_en]);

            return $message->load('offer');
        });

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    /** Carga la oferta bloqueada y exige que el usuario pertenezca a su conversación. */
    private function lockedOffer(Request $request, Offer $offer): Offer
    {
        $locked = Offer::with('conversation')->whereKey($offer->id)->lockForUpdate()->firstOrFail();
        $this->participant($request, $locked->conversation);

        // Vence aquí mismo si ya pasó su plazo.
        if ($locked->estatus === 'sent' && $locked->vence_en?->isPast()) {
            $locked->update(['estatus' => 'expired']);
        }

        abort_unless($locked->estatus === 'sent', 422, 'Esta oferta ya no está disponible.');

        return $locked;
    }

    /** Solo la otra persona (no quien la envió) puede aceptar; crea la operación. */
    public function accept(Request $request, Offer $offer)
    {
        $result = DB::transaction(function () use ($request, $offer) {
            $locked = $this->lockedOffer($request, $offer);
            abort_if($locked->remitente_id === $request->user()->id, 403, 'No puedes aceptar tu propia oferta.');

            $conversation = $locked->conversation;
            $listing = $conversation->listing;
            abort_unless($listing->estatus === 'published', 422, 'Esta publicación ya no está disponible.');

            $locked->update(['estatus' => 'accepted']);

            $operation = Operation::create([
                'oferta_id' => $locked->id,
                'publicacion_id' => $listing->id,
                'comprador_id' => $conversation->comprador_id,
                'vendedor_id' => $conversation->vendedor_id,
                // La operación guarda el TOTAL; la oferta guarda el precio por unidad.
                'monto' => round((float) $locked->monto * (float) $locked->cantidad, 2),
                'cantidad' => $locked->cantidad,
                'estatus' => 'oferta_aceptada',
            ]);

            $operation->events()->create([
                'estatus_anterior' => null,
                'estatus_nuevo' => 'oferta_aceptada',
                'actor_id' => $request->user()->id,
                'nota' => 'Oferta aceptada',
            ]);

            $conversation->update(['ultimo_mensaje_en' => now()]);

            return $locked->setRelation('operation', $operation);
        });

        return new OfferResource($result);
    }

    /** Solo la otra persona puede rechazar. */
    public function reject(Request $request, Offer $offer)
    {
        $result = DB::transaction(function () use ($request, $offer) {
            $locked = $this->lockedOffer($request, $offer);
            abort_if($locked->remitente_id === $request->user()->id, 403, 'No puedes rechazar tu propia oferta; cancélala.');
            $locked->update(['estatus' => 'rejected']);

            return $locked->load('operation');
        });

        return new OfferResource($result);
    }

    /** Solo quien la envió puede cancelarla. */
    public function cancel(Request $request, Offer $offer)
    {
        $result = DB::transaction(function () use ($request, $offer) {
            $locked = $this->lockedOffer($request, $offer);
            abort_unless($locked->remitente_id === $request->user()->id, 403, 'Solo quien envió la oferta puede cancelarla.');
            $locked->update(['estatus' => 'cancelled']);

            return $locked->load('operation');
        });

        return new OfferResource($result);
    }
}
