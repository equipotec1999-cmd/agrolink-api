<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Operation;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\NotificationText;
use Illuminate\Support\Str;
use Throwable;

/**
 * Crea notificaciones en la app. Un fallo aquí NUNCA debe tumbar la acción de negocio
 * que la dispara (mandar un mensaje, aceptar una oferta): se reporta y se sigue.
 */
class NotificationService
{
    private static function put(int $userId, string $type, array $data, ?string $collapseConversationId = null): void
    {
        try {
            if ($collapseConversationId !== null) {
                // Varios mensajes seguidos de la misma conversación = una sola notificación sin leer.
                $existing = UserNotification::query()
                    ->where('notificable_tipo', User::class)
                    ->where('notificable_id', $userId)
                    ->where('tipo', $type)
                    ->whereNull('leido_en')
                    ->whereRaw("datos->>'conversation_id' = ?", [$collapseConversationId])
                    ->first();

                if ($existing) {
                    $existing->update(['datos' => $data, 'creado_en' => now()]);
                    self::push($userId, $type, $data);

                    return;
                }
            }

            UserNotification::create([
                'id' => (string) Str::uuid(),
                'tipo' => $type,
                'notificable_tipo' => User::class,
                'notificable_id' => $userId,
                'datos' => $data,
            ]);
            self::push($userId, $type, $data);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Push al celular, DESPUÉS de responder al usuario (no hace esperar la petición). */
    private static function push(int $userId, string $type, array $data): void
    {
        [$title, $body] = NotificationText::render($type, $data);
        app()->terminating(fn () => PushService::toUser($userId, $title, $body, [
            'type' => $type,
            'conversation_id' => $data['conversation_id'] ?? '',
            'operation_id' => isset($data['operation_id']) ? (string) $data['operation_id'] : '',
        ]));
    }

    private static function base(Conversation $c, User $actor): array
    {
        return [
            'conversation_id' => (string) $c->id,
            'listing_title' => $c->listing?->titulo,
            'actor_name' => $actor->nombre,
        ];
    }

    private static function other(Conversation $c, User $actor): int
    {
        return $c->comprador_id === $actor->id ? $c->vendedor_id : $c->comprador_id;
    }

    public static function messageSent(Conversation $c, User $actor, string $body): void
    {
        self::put(self::other($c, $actor), 'new_message', self::base($c, $actor) + [
            'preview' => Str::limit($body, 120),
        ], collapseConversationId: (string) $c->id);
    }

    public static function offerSent(Conversation $c, User $actor, Offer $offer, bool $isCounter): void
    {
        self::put(self::other($c, $actor), $isCounter ? 'offer_countered' : 'offer_received', self::base($c, $actor) + [
            'offer_id' => $offer->id,
            'amount' => (float) $offer->monto,
            'quantity' => (float) $offer->cantidad,
        ]);
    }

    /** $type: offer_accepted | offer_rejected | offer_cancelled. Avisa a la otra persona. */
    public static function offerAnswered(string $type, Conversation $c, User $actor, Offer $offer, ?int $operationId = null): void
    {
        self::put(self::other($c, $actor), $type, self::base($c, $actor) + [
            'offer_id' => $offer->id,
            'amount' => (float) $offer->monto,
            'quantity' => (float) $offer->cantidad,
            'operation_id' => $operationId,
        ]);
    }

    private static function operationBase(Operation $op, User $actor): array
    {
        $op->loadMissing('listing:id,titulo', 'offer:id,conversacion_id');

        return [
            'operation_id' => $op->id,
            'conversation_id' => (string) $op->offer->conversacion_id,
            'listing_title' => $op->listing->titulo,
            'actor_name' => $actor->nombre,
        ];
    }

    /** Aviso a la otra parte de la operación. */
    private static function otherInOperation(Operation $op, User $actor): int
    {
        return $op->comprador_id === $actor->id ? $op->vendedor_id : $op->comprador_id;
    }

    /** La operación cambió de estado (pago, entrega, cancelación...): avisa a la otra persona. */
    public static function operationUpdated(Operation $op, User $actor, string $status, ?string $reason = null): void
    {
        self::put(self::otherInOperation($op, $actor), 'operation_updated', self::operationBase($op, $actor) + [
            'status' => $status,
            'reason' => $reason,
        ]);
    }

    /** Alguien calificó a la otra parte de la operación. */
    public static function reviewReceived(Operation $op, User $actor): void
    {
        self::put(self::otherInOperation($op, $actor), 'review_received', self::operationBase($op, $actor));
    }

    /** Aviso al dueño de una publicación: listing_approved | listing_rejected | listing_suspended. */
    public static function listingModerated(string $type, Listing $listing, ?string $reason): void
    {
        self::put($listing->usuario_id, $type, [
            'listing_id' => (string) $listing->id,
            'listing_title' => $listing->titulo,
            'reason' => $reason,
        ]);
    }

    /** Aviso al solicitante: verification_approved | verification_rejected. */
    public static function verificationReviewed(string $type, int $userId, ?string $reason): void
    {
        self::put($userId, $type, ['reason' => $reason]);
    }
}
