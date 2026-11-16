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

        return new OperationResource($this->query($me)->with('events')->findOrFail($operation->id));
    }

    /**
     * Avanza la operación (pago manual y entrega). Solo comprador o vendedor, y solo a los estados que su
     * rol permite desde el actual (ver Operation::FLUJO). Deja un evento en la línea de tiempo y avisa a
     * la otra persona. Al completarse suma una operación completada al perfil del vendedor.
     */
    public function transition(Request $request, Operation $operation)
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
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
                'No puedes cambiar la operación de «'.$locked->estatus.'» a «'.$new.'».'
            );

            $before = $locked->estatus;
            $locked->update(['estatus' => $new]);
            $locked->events()->create([
                'estatus_anterior' => $before,
                'estatus_nuevo' => $new,
                'actor_id' => $me->id,
                'nota' => $data['note'] ?? null,
            ]);

            if ($new === 'completado') {
                $perfil = SellerProfile::firstOrCreate(['usuario_id' => $locked->vendedor_id]);
                $perfil->increment('operaciones_completadas');
            }

            return $locked;
        });

        if ($changed) {
            NotificationService::operationUpdated($changed, $me, $new);
        }

        return new OperationResource($this->query($me->id)->with('events')->findOrFail($operation->id));
    }
}
