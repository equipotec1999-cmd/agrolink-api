<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversacion_id,
            'sender_id' => $this->remitente_id,
            'body' => $this->cuerpo,
            // Un mensaje es texto (`body`) o una oferta (`offer`).
            'offer_id' => $this->oferta_id,
            'offer' => $this->whenLoaded('offer', fn () => $this->offer ? new OfferResource($this->offer) : null),
            'read_at' => $this->leido_en,
            'created_at' => $this->creado_en,
        ];
    }
}
