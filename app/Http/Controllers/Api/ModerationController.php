<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\AuditLog;
use App\Models\Listing;
use App\Models\Report;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Moderación (Fase 6). Se publica primero y se revisa después (Fase 1 §5): la cola trae las
 * publicaciones visibles aún sin revisar. Los permisos se comprueban por PERMISO en las rutas
 * (`can:moderate listings`, `can:resolve reports`), nunca por nombre de rol.
 */
class ModerationController extends Controller
{
    private function audit(Request $request, string $action, string $type, int $id, array $changes = []): void
    {
        AuditLog::create([
            'usuario_id' => $request->user()->id,
            'accion' => $action,
            'auditable_tipo' => $type,
            'auditable_id' => $id,
            'cambios' => $changes,
            'direccion_ip' => $request->ip(),
            'agente_usuario' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
    }

    /** Cola de revisión: publicadas y pendientes de revisar, las más antiguas primero. */
    public function listings(Request $request)
    {
        $listings = Listing::query()
            ->with([
                'productType', 'user.sellerProfile', 'media',
                'location' => fn ($q) => $q->select(['publicacion_id', 'estado', 'municipio', 'codigo_postal'])->addSelect([
                    DB::raw('ST_Y(ubicacion_aproximada::geometry) as approx_lat'),
                    DB::raw('ST_X(ubicacion_aproximada::geometry) as approx_lng'),
                ]),
            ])
            ->addSelect(['open_reports' => Report::query()
                ->selectRaw('count(*)')
                ->whereColumn('reportable_id', 'publicaciones.id')
                ->where('reportable_tipo', 'listing')
                ->whereIn('estatus', ['open', 'investigating'])])
            ->addSelect('publicaciones.*')
            // Publicadas sin revisar + reenviadas por su dueño tras un rechazo/suspensión
            // (estas últimas siguen ocultas hasta que se aprueben).
            ->where(fn ($q) => $q
                ->where(fn ($p) => $p->where('estatus', 'published')->where('estatus_moderacion', 'pending'))
                ->orWhere('estatus', 'pending_review'))
            ->orderBy('actualizado_en')
            ->limit(50)
            ->get();

        return response()->json(['data' => $listings->map(
            fn ($l) => (new ListingResource($l))->resolve($request) + ['open_reports' => (int) $l->open_reports]
        )->values()]);
    }

    public function approve(Request $request, Listing $listing)
    {
        $before = $listing->estatus_moderacion;
        $update = ['estatus_moderacion' => 'approved', 'motivo_moderacion' => null];
        // Una reenviada tras rechazo/suspensión vuelve a ser visible al aprobarse.
        if ($listing->estatus === 'pending_review') {
            $update += [
                'estatus' => 'published',
                'publicado_en' => now(),
                'vence_en' => now()->addDays($listing->productType->dias_vigencia_predeterminados),
            ];
        }
        $listing->update($update);
        $this->audit($request, 'listing.approved', 'listing', $listing->id, ['antes' => $before]);

        return response()->noContent();
    }

    /** Rechazar: la publicación deja de ser visible y se avisa al dueño con el motivo. */
    public function reject(Request $request, Listing $listing)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:300']]);

        DB::transaction(function () use ($request, $listing, $data) {
            $listing->update(['estatus_moderacion' => 'rejected', 'estatus' => 'rejected', 'motivo_moderacion' => $data['reason']]);
            $this->audit($request, 'listing.rejected', 'listing', $listing->id, ['motivo' => $data['reason']]);
        });
        NotificationService::listingModerated('listing_rejected', $listing, $data['reason']);

        return response()->noContent();
    }

    /** Reportes abiertos, los más antiguos primero. */
    public function reports(Request $request)
    {
        $reports = Report::query()
            ->with('reporter:id,nombre')
            ->where('reportable_tipo', 'listing')
            ->whereIn('estatus', ['open', 'investigating'])
            ->orderBy('id')
            ->limit(50)
            ->get();

        $listings = Listing::withTrashed()
            ->with('user:id,nombre')
            ->whereIn('id', $reports->pluck('reportable_id'))
            ->get()
            ->keyBy('id');

        return response()->json(['data' => $reports->map(function (Report $r) use ($listings) {
            $l = $listings->get($r->reportable_id);

            return [
                'id' => $r->id,
                'reason' => $r->motivo,
                'description' => $r->descripcion,
                'status' => $r->estatus,
                'reporter_name' => $r->reporter?->nombre,
                'created_at' => $r->creado_en,
                'listing' => $l ? [
                    'id' => $l->id,
                    'title' => $l->titulo,
                    'status' => $l->estatus,
                    'moderation_status' => $l->estatus_moderacion,
                    'seller_name' => $l->user?->nombre,
                ] : null,
            ];
        })->values()]);
    }

    /**
     * Resolver un reporte. action: dismiss (sin fundamento) | resolve (atendido, sin
     * ocultar) | hide_listing (suspende la publicación y cierra TODOS sus reportes abiertos).
     */
    public function resolveReport(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['dismiss', 'resolve', 'hide_listing'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless(in_array($report->estatus, ['open', 'investigating'], true), 422, 'Este reporte ya fue atendido.');

        $listing = $report->reportable_tipo === 'listing' ? Listing::find($report->reportable_id) : null;
        abort_if($data['action'] === 'hide_listing' && ! $listing, 422, 'La publicación reportada ya no existe.');

        DB::transaction(function () use ($request, $report, $data, $listing) {
            $status = $data['action'] === 'dismiss' ? 'dismissed' : 'resolved';
            $close = ['estatus' => $status, 'resuelto_por' => $request->user()->id, 'resuelto_en' => now(), 'nota_resolucion' => $data['note'] ?? null];

            if ($data['action'] === 'hide_listing') {
                $listing->update(['estatus' => 'suspended', 'motivo_moderacion' => $data['note'] ?? 'Suspendida tras un reporte.']);
                Report::where('reportable_tipo', 'listing')->where('reportable_id', $listing->id)
                    ->whereIn('estatus', ['open', 'investigating'])->update($close);
            } else {
                $report->update($close);
            }

            $this->audit($request, 'report.'.$data['action'], 'report', $report->id, [
                'nota' => $data['note'] ?? null,
                'publicacion_id' => $listing?->id,
            ]);
        });

        if ($data['action'] === 'hide_listing') {
            NotificationService::listingModerated('listing_suspended', $listing, $data['note'] ?? null);
        }

        return response()->noContent();
    }
}
