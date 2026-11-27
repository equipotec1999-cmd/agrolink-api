<?php

namespace App\Http\Resources;

use App\Models\Dispute;
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
        $dispute = $this->relationLoaded('dispute') ? $this->dispute : null;
        $disputeOpen = $dispute?->isOpen() ?? false;
        $since = $this->entregado_en ?? $this->actualizado_en;
        $canDispute = ! $dispute
            && in_array($this->estatus, Dispute::ESTADOS_OPERACION, true)
            && ($this->estatus !== 'completado' || ($since && $since->gte(now()->subDays(Dispute::DIAS_TRAS_COMPLETAR))));

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
            // Con una disputa abierta la operación se congela: sin botones hasta que moderación decida.
            'actions' => $disputeOpen ? [] : Operation::transicionesPara($this->estatus, $role),
            // Una vez completada, cada parte puede calificar a la otra una sola vez.
            // Entrega con PIN (estilo DiDi): el comprador lo ve; el vendedor lo escribe para cerrar.
            'delivery_pin' => $isBuyer && in_array($this->estatus, Operation::PIN_ESTADOS, true)
                ? $this->pin_entrega
                : null,
            'can_deliver' => ! $isBuyer && ! $disputeOpen && in_array($this->estatus, Operation::PIN_ESTADOS, true),
            'pin_locked' => (int) $this->pin_intentos >= Operation::PIN_MAX_ATTEMPTS,
            'can_cancel' => ! $disputeOpen && in_array('cancelado', Operation::transicionesPara($this->estatus, $role), true),
            'can_dispute' => $canDispute,
            'dispute' => $dispute ? [
                'id' => $dispute->id,
                'status' => $dispute->estatus,
                'reason' => $dispute->motivo,
                'reason_label' => Dispute::MOTIVOS[$dispute->motivo] ?? $dispute->motivo,
                'description' => $dispute->descripcion,
                'response' => $dispute->respuesta,
                'opened_by_me' => $dispute->abierta_por_id === $me,
                'can_reply' => $dispute->isOpen() && $dispute->abierta_por_id !== $me && $dispute->respuesta === null,
                'decision' => $dispute->decision,
                'resolution_note' => $dispute->nota_resolucion,
                'resolved_at' => $dispute->resuelta_en,
                'created_at' => $dispute->creado_en,
            ] : null,
            'cancel_reason' => $this->motivo_cancelacion,
            'delivered_at' => $this->entregado_en,
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
