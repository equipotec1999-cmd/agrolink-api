<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /** Relaciones que necesita ConversationResource (evita N+1). */
    private function withRelations()
    {
        return Conversation::query()->with([
            'listing:id,titulo,estatus,precio,tipo_precio,tipo_producto_id',
            'listing.media',
            'buyer:id,nombre',
            'seller:id,nombre',
            'lastMessage',
        ]);
    }

    /** Solo participantes: para cualquier otro la conversación "no existe" (404, no 403). */
    private function mine(Request $request, Conversation $conversation): Conversation
    {
        $me = $request->user()->id;
        abort_unless($conversation->comprador_id === $me || $conversation->vendedor_id === $me, 404);

        return $conversation;
    }

    /** Bandeja: mis conversaciones (como comprador o vendedor), la más reciente primero. */
    public function index(Request $request)
    {
        $me = $request->user()->id;

        $conversations = $this->withRelations()
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('remitente_id', '!=', $me)
                ->whereNull('leido_en')])
            ->where(fn ($q) => $q->where('comprador_id', $me)->orWhere('vendedor_id', $me))
            ->orderByRaw('ultimo_mensaje_en DESC NULLS LAST')
            ->orderByDesc('id')
            ->paginate(50);

        return ConversationResource::collection($conversations);
    }

    /**
     * Abre (o recupera) mi conversación con el vendedor de esta publicación.
     * Idempotente: la restricción única (publicacion_id, comprador_id) evita duplicados
     * aun con dos taps simultáneos.
     */
    public function start(Request $request, Listing $listing)
    {
        $me = $request->user();

        abort_unless($listing->estatus === 'published', 404);
        abort_if($listing->usuario_id === $me->id, 422, 'No puedes escribirte a ti mismo en tu propia publicación.');

        $attributes = ['publicacion_id' => $listing->id, 'comprador_id' => $me->id];

        try {
            $conversation = Conversation::firstOrCreate($attributes, ['vendedor_id' => $listing->usuario_id]);
        } catch (UniqueConstraintViolationException) {
            $conversation = Conversation::where($attributes)->firstOrFail();
        }

        $conversation = $this->withRelations()->findOrFail($conversation->id);

        return (new ConversationResource($conversation))
            ->response()
            ->setStatusCode($conversation->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Mensajes de la conversación. Para polling: `?after_id=N` devuelve solo los nuevos.
     * Al leer, se marcan como leídos los mensajes de la otra persona.
     */
    public function messages(Request $request, Conversation $conversation)
    {
        $conversation = $this->mine($request, $conversation);
        $me = $request->user()->id;

        $request->validate(['after_id' => ['nullable', 'integer', 'min:0']]);

        Offer::expireStale($conversation->id);

        $query = Message::with('offer.operation')->where('conversacion_id', $conversation->id);

        if ($request->filled('after_id')) {
            $messages = $query->where('id', '>', (int) $request->input('after_id'))->orderBy('id')->limit(200)->get();
        } else {
            // Primera carga: los 100 más recientes, en orden cronológico.
            $messages = $query->orderByDesc('id')->limit(100)->get()->reverse()->values();
        }

        Message::where('conversacion_id', $conversation->id)
            ->where('remitente_id', '!=', $me)
            ->whereNull('leido_en')
            ->update(['leido_en' => now()]);

        // `offers`: estado actual de TODAS las ofertas de la conversación. El polling con
        // after_id solo trae mensajes nuevos, pero una oferta vieja puede haber sido
        // aceptada/rechazada después; con esto la app actualiza sus estados.
        $offers = Offer::with('operation')->where('conversacion_id', $conversation->id)->get();

        return MessageResource::collection($messages)->additional([
            'offers' => OfferResource::collection($offers)->resolve(),
        ]);
    }

    public function send(SendMessageRequest $request, Conversation $conversation)
    {
        $conversation = $this->mine($request, $conversation);

        $message = DB::transaction(function () use ($request, $conversation) {
            $message = Message::create([
                'conversacion_id' => $conversation->id,
                'remitente_id' => $request->user()->id,
                'cuerpo' => $request->validated('body'),
            ]);

            $conversation->update(['ultimo_mensaje_en' => $message->creado_en]);

            return $message;
        });

        return (new MessageResource($message))->response()->setStatusCode(201);
    }
}
