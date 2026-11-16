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

    /** Reseñas recibidas por un vendedor (las últimas 20, con comentario primero no: simplemente recientes). */
    public function forSeller(Request $request, int $user)
    {
        $reviews = Review::with('reviewer:id,nombre')
            ->where('evaluado_id', $user)->whereNotNull('exactitud')
            ->orderByDesc('id')->limit(20)->get();

        return response()->json(['data' => $reviews->map(fn ($r) => [
            'author' => $r->reviewer->nombre,
            'accuracy' => $r->exactitud,
            'fulfillment' => $r->cumplimiento,
            'communication' => $r->comunicacion,
            'comment' => $r->comentario,
            'created_at' => $r->creado_en,
        ])]);
    }
}
