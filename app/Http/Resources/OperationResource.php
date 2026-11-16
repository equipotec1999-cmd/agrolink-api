<?php

namespace App\Http\Resources;

use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user()->id;
        $isBuyer = $this->comprador_id === $me;
        $other = $isBuyer ? $this->seller : $this->buyer;
        $cover = $this->listing->media->first();
        $role = $isBuyer ? 'buyer' : 'seller';
        $reviewed = (bool) ($this->reviewed ?? false);

        return [
            'id' => $this->id,
            'status' => $this->estatus,
            'my_role' => $role,
            // `total` es lo acordado (precio por unidad × cantidad); `unit_price` sale de la oferta.
            'total' => (float) $this->monto,
            'quantity' => (float) $this->cantidad,
            'unit_price' => (float) $this->offer->monto,
            'conversation_id' => $this->offer->conversacion_id,
            // Estados a los que YO puedo pasarla ahora (la app arma los botones con esto, sin copiar las reglas).
            'actions' => Operation::transicionesPara($this->estatus, $role),
            // Una vez completada, cada parte puede calificar a la otra una sola vez.
            'can_review' => $this->estatus === 'completado' && ! $reviewed,
            'reviewed' => $reviewed,
            'listing' => [
                'id' => $this->listing->id,
                'title' => $this->listing->titulo,
                'unit' => $this->listing->unidad,
                'price_type' => $this->listing->tipo_precio,
                'cover_url' => $cover
                    ? Storage::disk(config('filesystems.default'))->url($cover->ruta_almacenamiento)
                    : null,
            ],
            'other_user' => ['id' => $other->id, 'name' => $other->nombre],
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($e) => [
                'status' => $e->estatus_nuevo,
                'note' => $e->nota,
                'created_at' => $e->creado_en,
            ])),
            'created_at' => $this->creado_en,
        ];
    }
}
