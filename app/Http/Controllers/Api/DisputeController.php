<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OperationResource;
use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\Message;
use App\Models\Operation;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Disputas de una operación: cuando comprador y vendedor no se ponen de acuerdo.
 *
 *   1. Cualquiera de los dos la abre con motivo y descripción → la operación se congela.
 *   2. La otra parte da su versión (una vez).
 *   3. Moderación revisa ambas versiones y el chat, y decide:
 *        favor_comprador → se cancela   ·   favor_vendedor → se completa   ·   reanudar → sigue normal.
 *   Las dos partes reciben la decisión con la nota de moderación.
 */
class DisputeController extends Controller
{
    /** Abrir una disputa (comprador o vendedor). */
    public function store(Request $request, Operation $operation)
    {
        $me = $request->user();
        abort_unless($operation->roleOf($me->id) !== null, 404);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Dispute::MOTIVOS))],
            'description' => ['required', 'string', 'min:15', 'max:1500'],
        ], [
            'description.required' => 'Cuenta qué pasó: moderación lo necesita para decidir.',
            'description.min' => 'Cuenta un poco más de lo que pasó (mínimo 15 caracteres).',
        ]);

        $dispute = DB::transaction(function () use ($operation, $me, $data) {
            $op = Operation::whereKey($operation->id)->lockForUpdate()->firstOrFail();

            abort_if(Dispute::where('operacion_id', $op->id)->exists(), 422,
                'Esta operación ya tuvo una disputa. Si hay otro problema, reporta la cuenta.');
            abort_unless(in_array($op->estatus, Dispute::ESTADOS_OPERACION, true), 422,
                $op->estatus === 'cancelado'
                    ? 'La operación está cancelada.'
                    : 'Todavía no hay pago ni entrega en juego: si no se ponen de acuerdo, cancela la operación.');
            if ($op->estatus === 'completado') {
                $since = $op->entregado_en ?? $op->actualizado_en;
                abort_if($since && $since->lt(now()->subDays(Dispute::DIAS_TRAS_COMPLETAR)), 422,
                    'Pasaron más de '.Dispute::DIAS_TRAS_COMPLETAR.' días desde la entrega: ya no se puede abrir una disputa.');
            }

            $dispute = Dispute::create([
                'operacion_id' => $op->id,
                'abierta_por_id' => $me->id,
                'motivo' => $data['reason'],
                'descripcion' => trim($data['description']),
                'estatus_operacion' => $op->estatus,
                'estatus' => 'abierta',
            ]);

            // Queda en la línea de tiempo sin cambiar el estado.
            $op->events()->create([
                'estatus_anterior' => $op->estatus,
                'estatus_nuevo' => $op->estatus,
                'actor_id' => $me->id,
                'nota' => 'Disputa abierta: '.Dispute::MOTIVOS[$data['reason']].'.',
            ]);

            return $dispute;
        });

        NotificationService::disputeOpened($operation, $me, Dispute::MOTIVOS[$dispute->motivo]);

        return $this->fresh($me->id, $operation->id);
    }

    /** La otra parte da su versión (una sola vez, mientras siga abierta). */
    public function reply(Request $request, Operation $operation)
    {
        $me = $request->user();
        abort_unless($operation->roleOf($me->id) !== null, 404);

        $data = $request->validate([
            'text' => ['required', 'string', 'min:10', 'max:1500'],
        ], [
            'text.required' => 'Escribe tu versión de lo que pasó.',
            'text.min' => 'Cuenta un poco más (mínimo 10 caracteres).',
        ]);

        $dispute = Dispute::where('operacion_id', $operation->id)->first();
        abort_unless($dispute && $dispute->isOpen(), 422, 'No hay una disputa abierta en esta operación.');
        abort_if($dispute->abierta_por_id === $me->id, 422, 'Tú abriste la disputa: espera la respuesta de la otra parte.');
        abort_if($dispute->respuesta !== null, 422, 'Ya diste tu versión.');

        $dispute->update(['respuesta' => trim($data['text']), 'respondida_en' => now()]);
        NotificationService::disputeReplied($operation, $me);

        return $this->fresh($me->id, $operation->id);
    }

    /** Moderación: disputas abiertas (las más viejas primero) o resueltas con `?status=resuelta`. */
    public function index(Request $request)
    {
        $request->validate(['status' => ['nullable', Rule::in(['abierta', 'resuelta'])]]);
        $status = $request->input('status', 'abierta');

        $disputes = Dispute::query()
            ->with([
                'opener:id,nombre',
                'operation' => fn ($q) => $q->with([
                    'listing' => fn ($l) => $l->withTrashed()->select(['id', 'titulo', 'unidad']),
                    'buyer:id,nombre',
                    'seller:id,nombre',
                    'offer:id,conversacion_id,monto',
                    'events',
                ]),
            ])
            ->where('estatus', $status)
            ->orderBy($status === 'abierta' ? 'creado_en' : 'resuelta_en', $status === 'abierta' ? 'asc' : 'desc')
            ->paginate(30);

        // Chat de cada operación: la evidencia principal (mismo criterio que en los reportes).
        $convIds = $disputes->getCollection()->map(fn (Dispute $d) => $d->operation?->offer?->conversacion_id)->filter()->unique();
        $messages = $convIds->isEmpty() ? collect() : Message::query()
            ->whereIn('conversacion_id', $convIds)
            ->orderBy('id')
            ->get(['id', 'conversacion_id', 'remitente_id', 'cuerpo', 'oferta_id', 'creado_en'])
            ->groupBy('conversacion_id');

        return response()->json([
            'data' => $disputes->getCollection()->map(fn (Dispute $d) => $this->forModeration($d, $messages))->values(),
            'meta' => ['current_page' => $disputes->currentPage(), 'last_page' => $disputes->lastPage(), 'total' => $disputes->total()],
        ]);
    }

    /** Moderación decide. La nota les llega a las dos partes. */
    public function resolve(Request $request, Dispute $dispute)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(Dispute::DECISIONES)],
            'note' => ['required', 'string', 'min:10', 'max:800'],
        ], [
            'note.required' => 'Explica la decisión: se les enviará a las dos partes.',
            'note.min' => 'Explica la decisión (mínimo 10 caracteres).',
        ]);
        $mod = $request->user();
        $note = trim($data['note']);
        $decision = $data['decision'];

        $op = DB::transaction(function () use ($dispute, $mod, $note, $decision) {
            $locked = Dispute::whereKey($dispute->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isOpen(), 422, 'Esta disputa ya fue resuelta.');

            $op = Operation::whereKey($locked->operacion_id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($mod->id, [$op->comprador_id, $op->vendedor_id], true), 403,
                'No puedes resolver una disputa en la que participas.');

            $target = match ($decision) {
                'favor_comprador' => 'cancelado',
                'favor_vendedor' => 'completado',
                default => null,
            };
            if ($target && $op->estatus !== $target) {
                $op->moveTo($mod->id, $target, 'Decisión de moderación: '.$note);
            } else {
                $op->events()->create([
                    'estatus_anterior' => $op->estatus,
                    'estatus_nuevo' => $op->estatus,
                    'actor_id' => $mod->id,
                    'nota' => 'Disputa cerrada por moderación: '.$note,
                ]);
            }

            $locked->update([
                'estatus' => 'resuelta',
                'decision' => $decision,
                'nota_resolucion' => $note,
                'resuelta_por_id' => $mod->id,
                'resuelta_en' => now(),
            ]);

            return $op;
        });

        AuditLog::create([
            'usuario_id' => $mod->id,
            'accion' => 'dispute.resolved',
            'auditable_tipo' => 'dispute',
            'auditable_id' => $dispute->id,
            'cambios' => ['decision' => $decision, 'note' => $note, 'operation_id' => $op->id],
            'direccion_ip' => $request->ip(),
            'agente_usuario' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        NotificationService::disputeResolved($op, $decision, $note);

        return response()->json(['data' => $this->forModeration($dispute->fresh(['opener', 'operation.listing', 'operation.buyer', 'operation.seller', 'operation.offer', 'operation.events']), collect())]);
    }

    private function forModeration(Dispute $d, $messagesByConv): array
    {
        $op = $d->operation;
        $convId = $op?->offer?->conversacion_id;

        return [
            'id' => $d->id,
            'status' => $d->estatus,
            'reason' => $d->motivo,
            'reason_label' => Dispute::MOTIVOS[$d->motivo] ?? $d->motivo,
            'description' => $d->descripcion,
            'response' => $d->respuesta,
            'opened_by' => ['id' => $d->abierta_por_id, 'name' => $d->opener?->nombre, 'role' => $op?->roleOf($d->abierta_por_id)],
            'operation_status_at_open' => $d->estatus_operacion,
            'decision' => $d->decision,
            'resolution_note' => $d->nota_resolucion,
            'resolved_at' => $d->resuelta_en,
            'created_at' => $d->creado_en,
            'operation' => $op ? [
                'id' => $op->id,
                'status' => $op->estatus,
                'total' => (float) $op->monto,
                'quantity' => (float) $op->cantidad,
                'unit' => $op->listing?->unidad,
                'listing_title' => $op->listing?->titulo,
                'buyer' => ['id' => $op->buyer?->id, 'name' => $op->buyer?->nombre],
                'seller' => ['id' => $op->seller?->id, 'name' => $op->seller?->nombre],
                'events' => $op->events->map(fn ($e) => [
                    'status' => $e->estatus_nuevo,
                    'note' => $e->nota,
                    'created_at' => $e->creado_en,
                ])->values(),
            ] : null,
            'messages' => collect($convId ? ($messagesByConv[$convId] ?? []) : [])->map(fn (Message $m) => [
                'id' => $m->id,
                'sender_id' => $m->remitente_id,
                'body' => $m->cuerpo,
                'is_offer' => $m->oferta_id !== null,
                'created_at' => $m->creado_en,
            ])->values(),
        ];
    }

    private function fresh(int $me, int $id): OperationResource
    {
        $op = Operation::query()
            ->with(['offer:id,conversacion_id,monto', 'listing:id,titulo,unidad,tipo_precio', 'listing.media',
                'buyer:id,nombre', 'seller:id,nombre', 'dispute', 'events'])
            ->withExists(['reviews as reviewed' => fn ($q) => $q->where('autor_id', $me)])
            ->findOrFail($id);

        return new OperationResource($op);
    }
}
