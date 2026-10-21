<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConstelacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Estadísticas públicas de mercado, leídas de la constelación (no de las tablas
 * transaccionales). Solo agregados: nunca expone usuarios ni publicaciones sueltas.
 */
class MarketController extends Controller
{
    public function __construct(private readonly ConstelacionService $constelacion)
    {
    }

    /** Totales generales. Se cachea 5 min para que la pantalla de inicio sea barata. */
    public function summary()
    {
        $this->constelacion->refrescarSiHaceFalta();

        $data = Cache::remember('mercado.resumen', 300, function () {
            $pub = DB::table('hecho_publicaciones as h')
                ->join('dim_estatus as e', 'e.estatus_sk', '=', 'h.estatus_sk')
                ->where('h.eliminada', false)
                ->selectRaw("COUNT(*) AS total, COUNT(*) FILTER (WHERE e.codigo = 'publicada') AS activas")
                ->first();

            $ops = DB::table('hecho_operaciones as h')
                ->join('dim_estatus as e', 'e.estatus_sk', '=', 'h.estatus_sk')
                ->selectRaw("COUNT(*) AS total, COUNT(*) FILTER (WHERE e.codigo IN ('completado', 'confirmado')) AS completadas")
                ->first();

            return [
                'listings' => ['active' => (int) $pub->activas, 'total' => (int) $pub->total],
                'offers' => (int) DB::table('hecho_ofertas')->count(),
                'operations' => ['total' => (int) $ops->total, 'completed' => (int) $ops->completadas],
            ];
        });

        return response()->json(['data' => $data + ['updated_at' => $this->constelacion->ultimaCarga()]]);
    }

    /**
     * Precio unitario de referencia de lo que hoy está publicado, por tipo de producto y estado.
     * Filtros opcionales: product_type_id, state.
     */
    public function prices(Request $request)
    {
        $this->constelacion->refrescarSiHaceFalta();

        $request->validate([
            'product_type_id' => ['nullable', 'integer'],
            'state' => ['nullable', 'string', 'max:100'],
        ]);

        $q = DB::table('hecho_publicaciones as h')
            ->join('dim_estatus as e', 'e.estatus_sk', '=', 'h.estatus_sk')
            ->join('dim_producto as p', 'p.producto_sk', '=', 'h.producto_sk')
            ->join('dim_ubicacion as u', 'u.ubicacion_sk', '=', 'h.ubicacion_sk')
            ->where('e.codigo', 'publicada')
            ->where('h.eliminada', false)
            ->whereNotNull('h.precio_unitario');

        if ($request->filled('product_type_id')) {
            $q->where('p.tipo_producto_id', $request->integer('product_type_id'));
        }
        if ($request->filled('state')) {
            $q->where('u.estado', $request->string('state'));
        }

        $rows = $q->groupBy('p.tipo_producto_id', 'p.tipo_nombre', 'p.categoria_nombre', 'u.estado')
            ->orderBy('p.categoria_nombre')->orderBy('p.tipo_nombre')->orderBy('u.estado')
            ->limit(200)
            ->selectRaw('p.tipo_producto_id, p.tipo_nombre, p.categoria_nombre, u.estado,
                COUNT(*) AS n, ROUND(AVG(h.precio_unitario), 2) AS promedio,
                MIN(h.precio_unitario) AS minimo, MAX(h.precio_unitario) AS maximo')
            ->get()
            ->map(fn ($r) => [
                'product_type_id' => (int) $r->tipo_producto_id,
                'product_type' => $r->tipo_nombre,
                'category' => $r->categoria_nombre,
                'state' => $r->estado,
                'count' => (int) $r->n,
                'avg_unit_price' => (float) $r->promedio,
                'min_unit_price' => (float) $r->minimo,
                'max_unit_price' => (float) $r->maximo,
            ]);

        return response()->json(['data' => $rows, 'updated_at' => $this->constelacion->ultimaCarga()]);
    }
}
