<?php

namespace App\Support;

/** Texto (en español) de cada tipo de notificación; lo usan la API y el push. */
class NotificationText
{
    /** @return array{0: string, 1: string} Aviso del nuevo estado de una operación. */
    private static function operation(string $actor, string $title, string $status, ?string $reason = null): array
    {
        return match ($status) {
            'pendiente_pago' => ['Pago por confirmar', "$actor avisó que pagó «{$title}». Confírmalo cuando lo recibas."],
            'pagado' => ['Pago confirmado', "$actor confirmó tu pago de «{$title}»."],
            'preparando_entrega' => ['Preparando tu pedido', "$actor está preparando «{$title}»."],
            'en_transito' => ['Tu pedido va en camino', "$actor marcó «{$title}» como en camino."],
            'entregado' => ['Pedido entregado', "$actor marcó «{$title}» como entregado. Confirma que lo recibiste."],
            'completado' => ['Entrega confirmada', "$actor confirmó la entrega de «{$title}». Ya puedes calificar."],
            'cancelado' => ['Compra cancelada', "$actor canceló la operación de «{$title}».".($reason ? " Motivo: {$reason}" : '')],
            default => ['Operación actualizada', "$actor actualizó la operación de «{$title}»."],
        };
    }

    /** Lo que se le dice a quien reportó, según la medida tomada. */
    private static function reportOutcome(array $d): string
    {
        $who = $d['target_name'] ?? 'la cuenta reportada';

        return match ($d['action'] ?? '') {
            'advertir' => "Revisamos tu reporte y enviamos una advertencia a {$who}.",
            'sancionar' => "Revisamos tu reporte y sancionamos temporalmente a {$who}.",
            'eliminar_cuenta' => "Revisamos tu reporte y eliminamos la cuenta de {$who}.",
            'ocultar_publicacion' => 'Revisamos tu reporte y ocultamos la publicación «'.($d['listing_title'] ?? '').'».',
            'descartar' => 'Revisamos tu reporte y no encontramos incumplimiento de las normas. Gracias por avisarnos.',
            default => 'Revisamos tu reporte y tomamos las medidas necesarias. Gracias por avisarnos.',
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
            'operation_updated' => self::operation($actor, $title, $d['status'] ?? '', $d['reason'] ?? null),
            'review_received' => ['Nueva calificación', "$actor calificó la operación de «{$title}»."],
            'account_warning' => ['Advertencia de moderación', 'Recibiste una advertencia por incumplir las normas de AgroLink. Motivo: '.($d['reason'] ?? 'sin especificar').'. Si se repite, tu cuenta puede ser sancionada.'],
            'account_suspended' => ['Cuenta sancionada', 'Tu cuenta quedó sancionada hasta el '.($d['until_text'] ?? '').'. Mientras tanto no puedes publicar, escribir ni ofertar y tus publicaciones están ocultas. Motivo: '.($d['reason'] ?? 'sin especificar')],
            'account_deleted' => ['Cuenta eliminada', 'Moderación eliminó tu cuenta por incumplir las normas de AgroLink. Motivo: '.($d['reason'] ?? 'sin especificar')],
            'report_resolved' => ['Atendimos tu reporte', self::reportOutcome($d)],
            'verification_approved' => ['Vendedor verificado', 'Revisamos tu documentación: ya eres vendedor verificado.'],
            'verification_rejected' => ['Verificación rechazada', 'No pudimos verificar tu cuenta. Motivo: '.($d['reason'] ?? 'sin especificar')],
            default => ['Notificación', ''],
        };
    }
}
