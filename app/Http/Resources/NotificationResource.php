<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $d = $this->datos ?? [];
        $actor = $d['actor_name'] ?? 'Alguien';
        $title = $d['listing_title'] ?? 'una publicación';
        $money = fn ($n) => '$'.number_format((float) $n, 2);
        $offer = isset($d['amount']) ? $money($d['amount']).' × '.rtrim(rtrim(number_format((float) ($d['quantity'] ?? 1), 2, '.', ''), '0'), '.') : '';

        [$heading, $body] = match ($this->tipo) {
            'new_message' => ["Mensaje de $actor", $d['preview'] ?? ''],
            'offer_received' => ["Nueva oferta de $actor", "$offer por «$title»"],
            'offer_countered' => ["Contraoferta de $actor", "$offer por «$title»"],
            'offer_accepted' => ['Oferta aceptada', "$actor aceptó tu oferta por «$title»".(isset($d['operation_id']) ? ". Operación #{$d['operation_id']}." : '.')],
            'offer_rejected' => ['Oferta rechazada', "$actor rechazó tu oferta por «$title»."],
            'offer_cancelled' => ['Oferta cancelada', "$actor canceló su oferta por «$title»."],
            default => ['Notificación', ''],
        };

        return [
            'id' => $this->id,
            'type' => $this->tipo,
            'title' => $heading,
            'body' => $body,
            'data' => $d,
            'read_at' => $this->leido_en,
            'created_at' => $this->creado_en,
        ];
    }
}
