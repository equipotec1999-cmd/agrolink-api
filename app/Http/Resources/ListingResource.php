<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'price_type' => $this->price_type,
            'currency' => $this->currency,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'sale_mode' => $this->sale_mode,
            'negotiable' => $this->negotiable,
            'status' => $this->status,
            'moderation_status' => $this->moderation_status,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'product_type' => [
                'id' => $this->productType->id,
                'name' => $this->productType->name,
                'category_id' => $this->productType->category_id,
            ],
            'seller' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'is_verified' => (bool) $this->user->sellerProfile?->is_verified,
                'member_since' => $this->user->created_at?->year,
                // Sin seller_profile todavía (no ha completado el alta de vendedor):
                // 0 operaciones/calificación es el estado real de "nuevo", no un
                // valor inventado.
                'completed_operations' => $this->user->sellerProfile?->completed_operations ?? 0,
                'rating_accuracy' => $this->user->sellerProfile?->rating_accuracy ?? 0,
                'rating_fulfillment' => $this->user->sellerProfile?->rating_fulfillment ?? 0,
                'rating_communication' => $this->user->sellerProfile?->rating_communication ?? 0,
            ],
            // Solo la ubicación APROXIMADA sale por la API pública; el punto exacto jamás
            // se serializa aquí (Fase 1 §6, "nunca exponer la ubicación exacta").
            'location' => $this->whenLoaded('location', fn () => [
                'state' => $this->location->state,
                'municipality' => $this->location->municipality,
                'approx_lat' => $this->location->approx_lat,
                'approx_lng' => $this->location->approx_lng,
            ]),
            'attributes' => $this->attributes_cache,
            'media' => ListingMediaResource::collection($this->whenLoaded('media')),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'name' => $d->name,
                'status' => $d->status,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
