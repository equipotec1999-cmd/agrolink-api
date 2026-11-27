<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operation;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Califica a la otra parte de una operación COMPLETADA (una sola vez por persona).
     * Comprador → vendedor: accuracy, fulfillment, communication.
     * Vendedor → comprador: payment, reception, communication.
     */
    public function store(Request $request, Operation $operation)
    {
        $me = $request->user();
        $role = $operation->roleOf($me->id);
        abort_unless($role !== null, 404);
        abort_unless($operation->estatus === 'completado', 422, 'Solo puedes calificar una operación completada.');

        $axes = $role === 'buyer' ? ['accuracy', 'fulfillment', 'communication'] : ['payment', 'reception', 'communication'];
        $rules = ['comment' => ['nullable', 'string', 'max:500']];
        foreach ($axes as $axis) {
            $rules[$axis] = ['required', 'integer', 'between:1,5'];
        }
        $data = $request->validate($rules);

        abort_if(
            Review::where('operacion_id', $operation->id)->where('autor_id', $me->id)->exists(),
            422,
            'Ya calificaste esta operación.'
        );

        $targetId = $role === 'buyer' ? $operation->vendedor_id : $operation->comprador_id;

        DB::transaction(function () use ($operation, $me, $role, $data, $targetId) {
            Review::create([
                'operacion_id' => $operation->id,
                'autor_id' => $me->id,
                'evaluado_id' => $targetId,
                'exactitud' => $data['accuracy'] ?? null,
                'cumplimiento' => $data['fulfillment'] ?? null,
                'comunicacion' => $data['communication'],
                'pago' => $data['payment'] ?? null,
                'recepcion' => $data['reception'] ?? null,
                'comentario' => $data['comment'] ?? null,
            ]);

            if ($role === 'buyer') {
                // Promedios cacheados del vendedor (lo que ven los compradores en la publicación).
                $avg = Review::where('evaluado_id', $targetId)->whereNotNull('exactitud')
                    ->selectRaw('ROUND(AVG(exactitud)::numeric, 2) a, ROUND(AVG(cumplimiento)::numeric, 2) f, ROUND(AVG(comunicacion)::numeric, 2) c')
                    ->first();
                $perfil = SellerProfile::firstOrCreate(['usuario_id' => $targetId]);
                $perfil->update([
                    'calificacion_exactitud' => $avg->a,
                    'calificacion_cumplimiento' => $avg->f,
                    'calificacion_comunicacion' => $avg->c,
                ]);
            }
        });

        NotificationService::reviewReceived($operation, $me);

        return response()->json(['data' => ['reviewed' => true]], 201);
    }

    /**
     * Perfil de reputación de un vendedor: resumen (promedios, distribución de estrellas,
     * operaciones, cancelaciones) y sus reseñas paginadas (15 por página, más recientes primero).
     * Solo cuentan las reseñas que dejaron compradores (las que tienen ejes de vendedor).
     */
    public function forSeller(Request $request, int $user)
    {
        $seller = \App\Models\User::with('sellerProfile')->findOrFail($user);
        abort_if($seller->isBanned(), 404);

        $base = Review::query()->where('evaluado_id', $seller->id)->whereNotNull('exactitud');

        $stats = (clone $base)->selectRaw(
            'COUNT(*) AS total,
             ROUND(AVG((exactitud + cumplimiento + comunicacion) / 3.0)::numeric, 2) AS promedio,
             ROUND(AVG(exactitud)::numeric, 2) AS exactitud,
             ROUND(AVG(cumplimiento)::numeric, 2) AS cumplimiento,
             ROUND(AVG(comunicacion)::numeric, 2) AS comunicacion'
        )->first();

        // Estrellas de cada reseña = promedio de sus tres ejes, redondeado.
        $dist = (clone $base)
            ->selectRaw('ROUND((exactitud + cumplimiento + comunicacion) / 3.0) AS estrellas, COUNT(*) AS n')
            ->groupByRaw('1')->pluck('n', 'estrellas');

        $cancellations = \App\Models\OperationEvent::query()
            ->where('estatus_nuevo', 'cancelado')
            ->where('actor_id', $seller->id)
            ->whereHas('operation', fn ($q) => $q->where('vendedor_id', $seller->id))
            ->count();

        $page = $base->with('reviewer:id,nombre')->orderByDesc('id')->paginate(15);

        return response()->json([
            'summary' => [
                'id' => $seller->id,
                'name' => $seller->nombre,
                'is_verified' => (bool) $seller->sellerProfile?->verificado,
                'member_since' => $seller->creado_en?->year,
                'completed_operations' => (int) ($seller->sellerProfile?->operaciones_completadas ?? 0),
                'cancellations' => $cancellations,
                'reviews_count' => (int) $stats->total,
                'average' => (float) ($stats->promedio ?? 0),
                'accuracy' => (float) ($stats->exactitud ?? 0),
                'fulfillment' => (float) ($stats->cumplimiento ?? 0),
                'communication' => (float) ($stats->comunicacion ?? 0),
                'distribution' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($n) => [$n => (int) ($dist[$n] ?? 0)]),
            ],
            'data' => collect($page->items())->map(fn ($r) => [
                'id' => $r->id,
                'author' => $r->reviewer?->nombre ?? 'Usuario',
                'stars' => round(($r->exactitud + $r->cumplimiento + $r->comunicacion) / 3, 1),
                'accuracy' => $r->exactitud,
                'fulfillment' => $r->cumplimiento,
                'communication' => $r->comunicacion,
                'comment' => $r->comentario,
                'created_at' => $r->creado_en,
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
        ]);
    }
}
