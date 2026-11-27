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
use App\Services\NotificationService;
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
     * Envía una oferta (mensaje tipo oferta). Reglas (una oferta por turno):
     * - Si hay una oferta abierta, nadie manda otra: quien la recibió la acepta o la rechaza,
     *   y quien la mandó espera respuesta (o la cancela).
     * - Mientras haya una operación en curso en el chat no se hacen ofertas nuevas.
     * - La primera oferta después de rechazar la de la otra persona cuenta como contraoferta (aviso).
     */
    public function store(StoreOfferRequest $request, Conversation $conversation)
    {
        $this->participant($request, $conversation);
        $me = $request->user()->id;
        $listing = $conversation->listing;

        abort_unless($listing->estatus === 'publicada', 422, 'Esta publicación ya no está disponible.');
        abort_if(
            (float) $request->validated('quantity') > (float) $listing->cantidad,
            422,
            "La cantidad ofertada supera la disponible ({$listing->cantidad} {$listing->unidad})."
        );

        $isCounter = false;
        $message = DB::transaction(function () use ($request, $conversation, $me, &$isCounter) {
            // Serializa ofertas simultáneas de una misma conversación.
            Conversation::whereKey($conversation->id)->lockForUpdate()->first();
            Offer::expireStale($conversation->id);

            $open = Offer::where('conversacion_id', $conversation->id)->where('estatus', 'enviada')->first();
            if ($open) {
                abort_if($open->remitente_id === $me, 422,
                    'Ya hiciste una oferta. Espera a que la acepten o la rechacen (o cancélala) para hacer otra.');
                abort(422, 'Tienes una oferta por responder. Acéptala o recházala antes de hacer otra.');
            }

            $active = Operation::where('publicacion_id', $conversation->publicacion_id)
                ->where('comprador_id', $conversation->comprador_id)
                ->whereNotIn('estatus', ['completado', 'cancelado'])
                ->exists();
            abort_if($active, 422, 'Ya hay una operación en curso en este chat. Ábrela desde el botón «Ver operación».');

            $last = Offer::where('conversacion_id', $conversation->id)->latest('id')->first();
            $isCounter = $last && $last->remitente_id !== $me && $last->estatus === 'rechazada';

            $offer = Offer::create([
                'conversacion_id' => $conversation->id,
                'remitente_id' => $me,
                'monto' => $request->validated('amount'),
                'cantidad' => $request->validated('quantity'),
                'estatus' => 'enviada',
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

        NotificationService::offerSent($conversation, $request->user(), $message->offer, $isCounter);

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    /** Carga la oferta bloqueada y exige que el usuario pertenezca a su conversación. */
    private function lockedOffer(Request $request, Offer $offer): Offer
    {
        $locked = Offer::with('conversation')->whereKey($offer->id)->lockForUpdate()->firstOrFail();
        $this->participant($request, $locked->conversation);

        // Vence aquí mismo si ya pasó su plazo.
        if ($locked->estatus === 'enviada' && $locked->vence_en?->isPast()) {
            $locked->update(['estatus' => 'vencida']);
        }

        abort_unless($locked->estatus === 'enviada', 422, 'Esta oferta ya no está disponible.');

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
            abort_unless($listing->estatus === 'publicada', 422, 'Esta publicación ya no está disponible.');

            $locked->update(['estatus' => 'aceptada']);

            $operation = Operation::create([
                'oferta_id' => $locked->id,
                'publicacion_id' => $listing->id,
                'comprador_id' => $conversation->comprador_id,
                'vendedor_id' => $conversation->vendedor_id,
                // La operación guarda el TOTAL; la oferta guarda el precio por unidad.
                'monto' => round((float) $locked->monto * (float) $locked->cantidad, 2),
                'cantidad' => $locked->cantidad,
                'estatus' => 'oferta_aceptada',
                // PIN de entrega: lo ve el comprador y el vendedor lo escribe al entregar.
                'pin_entrega' => str_pad((string) random_int(0, 9999), Operation::PIN_LENGTH, '0', STR_PAD_LEFT),
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

        NotificationService::offerAnswered('offer_accepted', $result->conversation, $request->user(), $result, $result->operation->id);

        return new OfferResource($result);
    }

    /** Solo la otra persona puede rechazar. */
    public function reject(Request $request, Offer $offer)
    {
        $result = DB::transaction(function () use ($request, $offer) {
            $locked = $this->lockedOffer($request, $offer);
            abort_if($locked->remitente_id === $request->user()->id, 403, 'No puedes rechazar tu propia oferta; cancélala.');
            $locked->update(['estatus' => 'rechazada']);

            return $locked->load('operation');
        });

        NotificationService::offerAnswered('offer_rejected', $result->conversation, $request->user(), $result);

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

        NotificationService::offerAnswered('offer_cancelled', $result->conversation, $request->user(), $result);

        return new OfferResource($result);
    }
}
