<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    /** Un documento de la publicación; con $withUrl incluye el enlace firmado para abrirlo. */
    public static function documentArray(\App\Models\ListingDocument $d, bool $withUrl): array
    {
        return [
            'id' => $d->id,
            'name' => $d->nombre,
            'status' => $d->estatus,
            'note' => $d->nota,
            'url' => $withUrl
                ? url(\Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'listing-documents.file', now()->addMinutes(30), ['document' => $d->id], absolute: false,
                ))
                : null,
        ];
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->titulo,
            'slug' => $this->slug,
            'description' => $this->descripcion,
            'price' => $this->precio,
            'price_type' => $this->tipo_precio,
            'currency' => $this->moneda,
            'quantity' => $this->cantidad,
            'unit' => $this->unidad,
            'sale_mode' => $this->modalidad_venta,
            'negotiable' => $this->negociable,
            'status' => $this->estatus,
            'moderation_status' => $this->estatus_moderacion,
            'published_at' => $this->publicado_en,
            'expires_at' => $this->vence_en,
            // Solo el dueño ve el motivo del rechazo/suspensión.
            // (`user('sanctum')`: en las rutas públicas el guard por defecto no resuelve el token.)
            'moderation_note' => $this->when(
                $request->user('sanctum') !== null && $request->user('sanctum')->id === $this->usuario_id,
                $this->motivo_moderacion,
            ),
            'product_type' => [
                'id' => $this->productType->id,
                'name' => $this->productType->nombre,
                'category_id' => $this->productType->categoria_id,
            ],
            'seller' => [
                'id' => $this->user->id,
                'name' => $this->user->nombre,
                'is_verified' => (bool) $this->user->sellerProfile?->verificado,
                'member_since' => $this->user->creado_en?->year,
                // Sin seller_profile todavía (no ha completado el alta de vendedor):
                // 0 operaciones/calificación es el estado real de "nuevo", no un
                // valor inventado.
                'completed_operations' => $this->user->sellerProfile?->operaciones_completadas ?? 0,
                'rating_accuracy' => $this->user->sellerProfile?->calificacion_exactitud ?? 0,
                'rating_fulfillment' => $this->user->sellerProfile?->calificacion_cumplimiento ?? 0,
                'rating_communication' => $this->user->sellerProfile?->calificacion_comunicacion ?? 0,
            ],
            // Solo la ubicación APROXIMADA sale por la API pública; el punto exacto jamás
            // se serializa aquí (Fase 1 §6, "nunca exponer la ubicación exacta").
            'location' => $this->whenLoaded('location', fn () => [
                'state' => $this->location->estado,
                'municipality' => $this->location->municipio,
                'approx_lat' => $this->location->approx_lat !== null ? (float) $this->location->approx_lat : null,
                'approx_lng' => $this->location->approx_lng !== null ? (float) $this->location->approx_lng : null,
            ]),
            'attributes' => $this->atributos_cache,
            'media' => ListingMediaResource::collection($this->whenLoaded('media')),
            'documents' => $this->whenLoaded('documents', function () use ($request) {
                // El archivo solo lo abren el dueño y moderación (enlace firmado de 30 min).
                $u = $request->user('sanctum');
                $canOpen = $u && ($u->id === $this->usuario_id || $u->can('moderate listings') || $u->can('moderate documents'));

                return $this->documents->map(fn ($d) => self::documentArray($d, (bool) $canOpen))->values();
            }),
            'created_at' => $this->creado_en,
        ];
    }
}
