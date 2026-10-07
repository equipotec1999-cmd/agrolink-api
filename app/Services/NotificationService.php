<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Offer;
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
}
