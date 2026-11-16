<?php

namespace App\Support;

/** Texto (en español) de cada tipo de notificación; lo usan la API y el push. */
class NotificationText
{
    /** @return array{0: string, 1: string} Aviso del nuevo estado de una operación. */
    private static function operation(string $actor, string $title, string $status): array
    {
        return match ($status) {
            'pendiente_pago' => ['Pago por confirmar', "$actor avisó que pagó «{$title}». Confírmalo cuando lo recibas."],
            'pagado' => ['Pago confirmado', "$actor confirmó tu pago de «{$title}»."],
            'preparando_entrega' => ['Preparando tu pedido', "$actor está preparando «{$title}»."],
            'en_transito' => ['Tu pedido va en camino', "$actor marcó «{$title}» como en camino."],
            'entregado' => ['Pedido entregado', "$actor marcó «{$title}» como entregado. Confirma que lo recibiste."],
            'completado' => ['Operación completada', "$actor confirmó la entrega de «{$title}». Ya puedes calificar."],
            'cancelado' => ['Operación cancelada', "$actor canceló la operación de «{$title}»."],
            default => ['Operación actualizada', "$actor actualizó la operación de «{$title}»."],
        };
    }

    /** @return array{0: string, 1: string} [título, cuerpo] */
    public static function render(string $type, array $d): array
    {
        $actor = $d['actor_name'] ?? 'Alguien';
        $title = $d['listing_title'] ?? 'una publicación';
        $offer = isset($d['amount'])
            ? '$'.number_format((float) $d['amount'], 2).' × '.rtrim(rtrim(number_format((float) ($d['quantity'] ?? 1), 2, '.', ''), '0'), '.')
            : '';

        return match ($type) {
            'new_message' => ["Mensaje de $actor", $d['preview'] ?? ''],
            'offer_received' => ["Nueva oferta de $actor", "$offer por «{$title}»"],
            'offer_countered' => ["Contraoferta de $actor", "$offer por «{$title}»"],
            'offer_accepted' => ['Oferta aceptada', "$actor aceptó tu oferta por «{$title}»".(isset($d['operation_id']) ? ". Operación #{$d['operation_id']}." : '.')],
            'offer_rejected' => ['Oferta rechazada', "$actor rechazó tu oferta por «{$title}»."],
            'offer_cancelled' => ['Oferta cancelada', "$actor canceló su oferta por «{$title}»."],
            'listing_approved' => ['Publicación aprobada', "«{$title}» ya es visible para todos."],
            'listing_rejected' => ['Publicación rechazada', "Moderación rechazó «{$title}». Motivo: ".($d['reason'] ?? 'sin especificar')],
            'listing_suspended' => ['Publicación suspendida', "«{$title}» fue suspendida tras un reporte.".(! empty($d['reason']) ? " Nota: {$d['reason']}" : '')],
            'operation_updated' => self::operation($actor, $title, $d['status'] ?? ''),
            'review_received' => ['Nueva calificación', "$actor calificó la operación de «{$title}»."],
            'verification_approved' => ['Vendedor verificado', 'Revisamos tu documentación: ya eres vendedor verificado.'],
            'verification_rejected' => ['Verificación rechazada', 'No pudimos verificar tu cuenta. Motivo: '.($d['reason'] ?? 'sin especificar')],
            default => ['Notificación', ''],
        };
    }
}
