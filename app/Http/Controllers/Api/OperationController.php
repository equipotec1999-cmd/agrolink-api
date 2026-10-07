<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OperationResource;
use App\Models\Operation;
use Illuminate\Http\Request;

class OperationController extends Controller
{
    private function query()
    {
        return Operation::query()->with([
            'offer:id,conversacion_id,monto',
            'listing:id,titulo,unidad,tipo_precio',
            'listing.media',
            'buyer:id,nombre',
            'seller:id,nombre',
        ]);
    }

    /** Mis operaciones. `?role=buyer` (mis compras) o `?role=seller` (mis ventas); sin role, ambas. */
    public function index(Request $request)
    {
        $request->validate(['role' => ['nullable', 'in:buyer,seller']]);
        $me = $request->user()->id;
        $role = $request->input('role');

        $operations = $this->query()
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
        abort_unless($operation->comprador_id === $me || $operation->vendedor_id === $me, 404);

        return new OperationResource($this->query()->with('events')->findOrFail($operation->id));
    }
}
