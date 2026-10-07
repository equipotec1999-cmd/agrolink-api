<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /** Motivos permitidos = valores del enum de la tabla `reportes`. */
    public const REASONS = [
        'fraude', 'informacion_falsa', 'producto_inexistente', 'documentacion_sospechosa',
        'publicacion_duplicada', 'conducta_inapropiada', 'producto_no_permitido', 'otro',
    ];

    /** Reportar una publicación. Uno abierto por persona y publicación; no se puede reportar la propia. */
    public function store(Request $request, Listing $listing)
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(self::REASONS)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $me = $request->user()->id;
        abort_unless($listing->estatus === 'publicada', 404);
        abort_if($listing->usuario_id === $me, 422, 'No puedes reportar tu propia publicación.');

        $alreadyOpen = Report::query()
            ->where('reportante_id', $me)
            ->where('reportable_tipo', 'listing')
            ->where('reportable_id', $listing->id)
            ->whereIn('estatus', ['abierto', 'investigating'])
            ->exists();
        abort_if($alreadyOpen, 422, 'Ya reportaste esta publicación; un moderador la está revisando.');

        $report = Report::create([
            'reportante_id' => $me,
            'reportable_tipo' => 'listing',
            'reportable_id' => $listing->id,
            'motivo' => $data['reason'],
            'descripcion' => $data['description'] ?? null,
            'estatus' => 'abierto',
        ]);

        return response()->json(['data' => ['id' => $report->id]], 201);
    }

    /** Reportar a un usuario. Uno abierto por persona y usuario; no se puede reportar a uno mismo. */
    public function storeUser(Request $request, User $user)
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(self::REASONS)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $me = $request->user()->id;
        abort_if($user->id === $me, 422, 'No puedes reportarte a ti mismo.');

        $alreadyOpen = Report::query()
            ->where('reportante_id', $me)
            ->where('reportable_tipo', 'user')
            ->where('reportable_id', $user->id)
            ->whereIn('estatus', ['abierto', 'investigating'])
            ->exists();
        abort_if($alreadyOpen, 422, 'Ya reportaste a este usuario; un moderador lo está revisando.');

        $report = Report::create([
            'reportante_id' => $me,
            'reportable_tipo' => 'user',
            'reportable_id' => $user->id,
            'motivo' => $data['reason'],
            'descripcion' => $data['description'] ?? null,
            'estatus' => 'abierto',
        ]);

        return response()->json(['data' => ['id' => $report->id]], 201);
    }
}
