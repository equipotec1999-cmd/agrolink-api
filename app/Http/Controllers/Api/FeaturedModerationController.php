<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FeaturedRequest;
use App\Models\Listing;
use App\Models\User;
use App\Services\FeaturedService;
use App\Services\NotificationService;
use App\Support\FeaturedPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Moderación de destacados: destacar/quitar publicaciones y perfiles y atender solicitudes pagadas. */
class FeaturedModerationController extends Controller
{
    private function days(Request $request): int
    {
        return (int) $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:'.FeaturedPricing::MAX_DAYS],
        ])['days'];
    }

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

    public function featureListing(Request $request, Listing $listing)
    {
        abort_unless($listing->estatus === 'publicada', 422, 'Solo se pueden destacar publicaciones activas.');
        $days = $this->days($request);
        $until = FeaturedService::extend($listing, 'destacada_hasta', $days);
        $this->audit($request, 'listing.featured', 'listing', $listing->id, ['days' => $days, 'until' => $until->toIso8601String()]);
        NotificationService::featuredActivated($listing->usuario_id, 'listing', $listing->titulo, $until);

        return response()->json(['data' => ['featured_until' => $until]]);
    }

    public function unfeatureListing(Request $request, Listing $listing)
    {
        FeaturedService::clear($listing, 'destacada_hasta');
        $this->audit($request, 'listing.unfeatured', 'listing', $listing->id);

        return response()->json(['data' => ['featured_until' => null]]);
    }

    public function featureUser(Request $request, User $user)
    {
        abort_if($user->isBanned(), 422, 'Esta cuenta fue eliminada.');
        $days = $this->days($request);
        $until = FeaturedService::extend($user, 'destacado_hasta', $days);
        $this->audit($request, 'user.featured', 'user', $user->id, ['days' => $days, 'until' => $until->toIso8601String()]);
        NotificationService::featuredActivated($user->id, 'profile', null, $until);

        return response()->json(['data' => ['featured_until' => $until]]);
    }

    public function unfeatureUser(Request $request, User $user)
    {
        FeaturedService::clear($user, 'destacado_hasta');
        $this->audit($request, 'user.unfeatured', 'user', $user->id);

        return response()->json(['data' => ['featured_until' => null]]);
    }

    /** Solicitudes pagadas pendientes (y las últimas atendidas). */
    public function requests(Request $request)
    {
        $status = $request->string('status', 'pendiente_pago')->toString();
        $items = FeaturedRequest::query()->with(['listing', 'user'])
            ->when($status !== 'todas', fn ($q) => $q->where('estatus', $status))
            ->orderBy('id')->limit(100)->get();

        return response()->json(['data' => $items->map->toApi()->values()]);
    }

    /** Pago confirmado: se activa el destacado por los días pagados. */
    public function activate(Request $request, FeaturedRequest $featuredRequest)
    {
        abort_unless($featuredRequest->estatus === 'pendiente_pago', 422, 'Esta solicitud ya fue atendida.');

        $until = DB::transaction(function () use ($request, $featuredRequest) {
            if ($featuredRequest->tipo === 'perfil') {
                $target = $featuredRequest->user;
                $until = FeaturedService::extend($target, 'destacado_hasta', $featuredRequest->dias);
            } else {
                $target = $featuredRequest->listing;
                abort_if($target === null || $target->estatus !== 'publicada', 422, 'La publicación ya no está activa.');
                $until = FeaturedService::extend($target, 'destacada_hasta', $featuredRequest->dias);
            }
            $featuredRequest->update([
                'estatus' => 'activa',
                'revisado_por' => $request->user()->id,
                'activada_en' => now(),
            ]);

            return $until;
        });

        $this->audit($request, 'featured_request.activated', 'featured_request', $featuredRequest->id, ['amount' => (float) $featuredRequest->monto]);
        NotificationService::featuredActivated(
            $featuredRequest->usuario_id,
            $featuredRequest->tipo === 'perfil' ? 'profile' : 'listing',
            $featuredRequest->listing?->titulo,
            $until,
        );

        return response()->json(['data' => $featuredRequest->fresh(['listing', 'user'])->toApi()]);
    }

    public function reject(Request $request, FeaturedRequest $featuredRequest)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        abort_unless($featuredRequest->estatus === 'pendiente_pago', 422, 'Esta solicitud ya fue atendida.');
        $featuredRequest->update([
            'estatus' => 'rechazada',
            'revisado_por' => $request->user()->id,
            'nota' => $data['reason'],
        ]);
        $this->audit($request, 'featured_request.rejected', 'featured_request', $featuredRequest->id, ['reason' => $data['reason']]);
        NotificationService::featuredRejected($featuredRequest->usuario_id, $data['reason']);

        return response()->json(['data' => $featuredRequest->fresh(['listing', 'user'])->toApi()]);
    }
}
