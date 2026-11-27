<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OperationResource;
use App\Models\Operation;
use App\Models\SellerProfile;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationController extends Controller
{
    private function query(int $me)
    {
        return Operation::query()
            ->with([
                'offer:id,conversacion_id,monto',
                'listing:id,titulo,unidad,tipo_precio',
                'listing.media',
                'buyer:id,nombre',
                'seller:id,nombre',
            ])
            // `reviewed`: si YO ya califiqué esta operación (decide si la app ofrece calificar).
            ->withExists(['reviews as reviewed' => fn ($q) => $q->where('autor_id', $me)]);
    }

    /** Mis operaciones. `?role=buyer` (mis compras) o `?role=seller` (mis ventas); sin role, ambas. */
    public function index(Request $request)
    {
        $request->validate(['role' => ['nullable', 'in:buyer,seller']]);
        $me = $request->user()->id;
        $role = $request->input('role');

        $operations = $this->query($me)
            ->where(function ($q) use ($me, $role) {
                if ($role !== 'seller') {
                    $q->orWhere('comprador_id', $me);
                }
                if ($role !== 'buyer') {
                    $q->orWhere('vendedor_id', $me);
                }
            })
            ->orderByDesc('id')
            ->paginate(50);

        return OperationResource::collection($operations);
    }

    /** Detalle con su línea de tiempo. Solo comprador o vendedor; para otros no existe (404). */
    public function show(Request $request, Operation $operation)
    {
        $me = $request->user()->id;
        abort_unless($operation->roleOf($me) !== null, 404);

        return $this->fresh($me, $operation->id);
    }

    /**
     * Avanza la operación (pago manual, preparación, «ya lo recibí» y cancelación). Solo comprador o
     * vendedor, y solo a los estados que su rol permite desde el actual (ver Operation::FLUJO).
     * Cancelar exige motivo. La entrega del vendedor va por `deliver` (con PIN), no por aquí.
     */
    public function transition(Request $request, Operation $operation)
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
            'reason' => ['required_if:status,cancelado', 'nullable', 'string', 'min:3', 'max:255'],
        ], [
            'reason.required_if' => 'Escribe el motivo de la cancelación.',
            'reason.min' => 'Escribe el motivo de la cancelación.',
        ]);
        $me = $request->user();
        abort_unless($operation->roleOf($me->id) !== null, 404);

        $new = $data['status'];
        $changed = DB::transaction(function () use ($operation, $me, $new, $data) {
            // Bloqueada: dos taps seguidos o las dos partes a la vez no se pisan.
            $locked = Operation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            $role = $locked->roleOf($me->id);

            // Reintento idempotente: si ya está en ese estado no es un error ni duplica el evento.
            if ($locked->estatus === $new) {
                return false;
            }

            abort_unless(
                in_array($new, Operation::transicionesPara($locked->estatus, $role), true),
                422,
                $new === 'cancelado'
                    ? 'Esta operación ya no se puede cancelar. Si hay un problema, repórtalo.'
                    : 'No puedes cambiar la operación de «'.$locked->estatus.'» a «'.$new.'».'
            );

            $note = $new === 'cancelado' ? trim($data['reason']) : ($data['note'] ?? null);
            if ($new === 'completado' && ! $note) {
                $note = 'El comprador confirmó que recibió el pedido.';
            }
            $this->move($locked, $me->id, $new, $note);

            return $locked;
        });

        if ($changed) {
            NotificationService::operationUpdated($changed, $me, $new, $new === 'cancelado' ? $changed->motivo_cancelacion : null);
        }

        return $this->fresh($me->id, $operation->id);
    }

    /**
     * El vendedor confirma la entrega escribiendo el PIN que le dicta el comprador (como en DiDi).
     * Con el PIN correcto la operación se cierra (entregado → completado). Tras 5 fallos se bloquea
     * hasta que el comprador genere un PIN nuevo.
     */
    public function deliver(Request $request, Operation $operation)
    {
        $data = $request->validate(['pin' => ['required', 'string', 'regex:/^[0-9]{4}$/']], [
            'pin.regex' => 'El PIN tiene 4 dígitos.',
        ]);
        $me = $request->user();
        abort_unless($operation->roleOf($me->id) !== null, 404);
        abort_unless($operation->roleOf($me->id) === 'seller', 403, 'Solo el vendedor escribe el PIN.');

        $result = DB::transaction(function () use ($operation, $me, $data) {
            $locked = Operation::whereKey($operation->id)->lockForUpdate()->firstOrFail();

            if ($locked->estatus === 'completado') {
                return 'already';
            }
            abort_unless(in_array($locked->estatus, Operation::PIN_ESTADOS, true), 422,
                'Esta operación ya no admite entrega.');
            $locked->ensurePin();
            abort_if($locked->pin_intentos >= Operation::PIN_MAX_ATTEMPTS, 423,
                'Demasiados intentos. Pide al comprador que genere un PIN nuevo.');

            if (! hash_equals((string) $locked->pin_entrega, $data['pin'])) {
                $locked->increment('pin_intentos');
                $left = Operation::PIN_MAX_ATTEMPTS - $locked->pin_intentos;

                return $left > 0 ? "wrong:$left" : 'locked';
            }

            if ($locked->estatus !== 'entregado') {
                $this->move($locked, $me->id, 'entregado', 'Entrega confirmada con el PIN del comprador.');
            }
            $this->move($locked, $me->id, 'completado', null);

            return $locked;
        });

        if ($result === 'locked') {
            abort(423, 'PIN incorrecto. Se bloqueó: pide al comprador que genere un PIN nuevo.');
        }
        if (is_string($result) && str_starts_with($result, 'wrong:')) {
            abort(422, 'PIN incorrecto. Te quedan '.substr($result, 6).' intentos.');
        }
        if ($result instanceof Operation) {
            NotificationService::operationUpdated($result, $me, 'completado');
        }

        return $this->fresh($me->id, $operation->id);
    }

    /** El comprador genera un PIN nuevo (si lo compartió por error o el vendedor lo bloqueó). */
    public function regeneratePin(Request $request, Operation $operation)
    {
        $me = $request->user();
        abort_unless($operation->roleOf($me->id) !== null, 404);
        abort_unless($operation->roleOf($me->id) === 'buyer', 403, 'Solo el comprador puede cambiar el PIN.');
        abort_unless(in_array($operation->estatus, Operation::PIN_ESTADOS, true), 422,
            'Esta operación ya no usa PIN.');

        $operation->regeneratePin();

        return $this->fresh($me->id, $operation->id);
    }

    /** Cambia el estatus, deja el evento y aplica los efectos (contador del vendedor, motivo). */
    private function move(Operation $op, int $actorId, string $new, ?string $note): void
    {
        $before = $op->estatus;
        $attrs = ['estatus' => $new];
        if ($new === 'entregado') {
            $attrs['entregado_en'] = now();
        }
        if ($new === 'completado' && ! $op->entregado_en) {
            $attrs['entregado_en'] = now();
        }
        if ($new === 'cancelado') {
            $attrs['motivo_cancelacion'] = $note;
        }
        $op->update($attrs);
        $op->events()->create([
            'estatus_anterior' => $before,
            'estatus_nuevo' => $new,
            'actor_id' => $actorId,
            'nota' => $note,
        ]);

        if ($new === 'completado') {
            $perfil = SellerProfile::firstOrCreate(['usuario_id' => $op->vendedor_id]);
            $perfil->increment('operaciones_completadas');
        }
    }

    private function fresh(int $me, int $id): OperationResource
    {
        $op = $this->query($me)->with('events')->findOrFail($id);
        if ($op->roleOf($me) === 'buyer') {
            $op->ensurePin();
        }

        return new OperationResource($op);
    }
}
