<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversacion_id,
            'sender_id' => $this->remitente_id,
            // `amount` es el precio POR UNIDAD; `total` = amount × quantity.
            'amount' => (float) $this->monto,
            'quantity' => (float) $this->cantidad,
            'total' => round((float) $this->monto * (float) $this->cantidad, 2),
            'status' => $this->estatus,
            'expires_at' => $this->vence_en,
            'operation_id' => $this->whenLoaded('operation', fn () => $this->operation?->id),
            'created_at' => $this->creado_en,
        ];
    }
}
