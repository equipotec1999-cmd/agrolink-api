<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user()->id;
        $other = $this->comprador_id === $me ? $this->seller : $this->buyer;
        $cover = $this->listing?->media->first();
        $last = $this->lastMessage;

        return [
            'id' => $this->id,
            'listing' => [
                'id' => $this->listing->id,
                'title' => $this->listing->titulo,
                'status' => $this->listing->estatus,
                'price' => $this->listing->precio,
                'price_type' => $this->listing->tipo_precio,
                'product_type_id' => $this->listing->tipo_producto_id,
                'cover_url' => $cover
                    ? Storage::disk(config('filesystems.default'))->url($cover->ruta_almacenamiento)
                    : null,
            ],
            'other_user' => [
                'id' => $other->id,
                'name' => $other->nombre,
            ],
            'my_role' => $this->comprador_id === $me ? 'buyer' : 'seller',
            'last_message' => $last ? [
                'body' => $last->cuerpo,
                'has_offer' => $last->oferta_id !== null,
                'sender_id' => $last->remitente_id,
                'created_at' => $last->creado_en,
            ] : null,
            // Lo calcula el controlador con withCount; 0 si no se pidió.
            'unread_count' => (int) ($this->unread_count ?? 0),
            'last_message_at' => $this->ultimo_mensaje_en,
            'created_at' => $this->creado_en,
        ];
    }
}
