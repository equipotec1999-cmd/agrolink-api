<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use App\Services\ContactVerificationService;
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
                ->whereIn('estatus', ['abierto', 'investigating'])])
            ->addSelect('publicaciones.*')
            // Publicadas sin revisar + reenviadas por su dueño tras un rechazo/suspensión
            // (estas últimas siguen ocultas hasta que se aprueben).
            ->where(fn ($q) => $q
                ->where(fn ($p) => $p->where('estatus', 'publicada')->where('estatus_moderacion', 'pendiente'))
                ->orWhere('estatus', 'en_revision'))
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
        $update = ['estatus_moderacion' => 'aprobada', 'motivo_moderacion' => null];
        // Una reenviada tras rechazo/suspensión vuelve a ser visible al aprobarse.
        if ($listing->estatus === 'en_revision') {
            $update += [
                'estatus' => 'publicada',
                'publicado_en' => now(),
                'vence_en' => now()->addDays($listing->productType->dias_vigencia_predeterminados),
            ];
        }
        $wasInReview = $listing->estatus === 'en_revision';
        $listing->update($update);
        $this->audit($request, 'listing.approved', 'listing', $listing->id, ['antes' => $before]);
        if ($wasInReview) {
            NotificationService::listingModerated('listing_approved', $listing, null);
        }

        return response()->noContent();
    }

    /** Suspender una publicación ya publicada (por queja ajena o moderación directa). */
    public function suspend(Request $request, Listing $listing)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:300']]);
        abort_unless(in_array($listing->estatus, ['publicada', 'en_revision', 'rechazada'], true), 422, 'Esta publicación no puede suspenderse.');
        $listing->update(['estatus' => 'suspended', 'motivo_moderacion' => $data['reason']]);
        $this->audit($request, 'listing.suspended', 'listing', $listing->id, ['motivo' => $data['reason']]);
        NotificationService::listingModerated('listing_suspended', $listing, $data['reason']);
        return response()->noContent();
    }

    /** Eliminar una publicación (moderación). Soft delete: ya no aparece en ninguna parte. */
    public function destroy(Request $request, Listing $listing)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:300']]);

        // Idempotente: si ya estaba eliminada (otro mod, o reintento de red) no es un error.
        if ($listing->trashed()) {
            return response()->noContent();
        }

        $this->audit($request, 'listing.deleted', 'listing', $listing->id, ['motivo' => $data['reason'], 'titulo' => $listing->titulo]);
        NotificationService::listingModerated('listing_suspended', $listing, 'Eliminada por moderación: '.$data['reason']);
        $listing->delete();
        return response()->noContent();
    }

    /** Rechazar: la publicación deja de ser visible y se avisa al dueño con el motivo. */
    public function reject(Request $request, Listing $listing)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:300']]);

        DB::transaction(function () use ($request, $listing, $data) {
            $listing->update(['estatus_moderacion' => 'rechazada', 'estatus' => 'rechazada', 'motivo_moderacion' => $data['reason']]);
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
            ->whereIn('reportable_tipo', ['listing', 'user'])
            ->whereIn('estatus', ['abierto', 'investigating'])
            ->orderBy('id')
            ->limit(50)
            ->get();

        $listings = Listing::withTrashed()
            ->with('user:id,nombre,apellidos,advertencias,suspendido_hasta,eliminado_en')
            ->whereIn('id', $reports->where('reportable_tipo', 'listing')->pluck('reportable_id'))
            ->get()
            ->keyBy('id');
        $users = \App\Models\User::query()
            ->whereIn('id', $reports->where('reportable_tipo', 'user')->pluck('reportable_id'))
            ->get(['id', 'nombre', 'apellidos', 'advertencias', 'suspendido_hasta', 'eliminado_en'])
            ->keyBy('id');
        // Historial de la cuenta para decidir la medida (advertencias previas, sanción vigente).
        $account = fn (?User $u) => $u ? [
            'id' => $u->id,
            'name' => trim($u->nombre.' '.$u->apellidos),
            'warnings' => (int) $u->advertencias,
            'suspended_until' => $u->isSuspended() ? $u->suspendido_hasta : null,
            'deleted' => $u->isBanned(),
        ] : null;

        return response()->json(['data' => $reports->map(function (Report $r) use ($listings, $users, $account) {
            $l = $r->reportable_tipo === 'listing' ? $listings->get($r->reportable_id) : null;
            $u = $r->reportable_tipo === 'user' ? $users->get($r->reportable_id) : null;

            return [
                'id' => $r->id,
                'reason' => $r->motivo,
                'description' => $r->descripcion,
                'status' => $r->estatus,
                'reporter_name' => $r->reporter?->nombre,
                'created_at' => $r->creado_en,
                'target_type' => $r->reportable_tipo,
                'user' => $u ? ['id' => $u->id, 'name' => trim($u->nombre.' '.$u->apellidos)] : null,
                // Cuenta sobre la que caería una advertencia, sanción o eliminación.
                'account' => $account($u ?? $l?->user),
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
    /**
     * Hilo del chat para valorar un reporte: para un reporte de publicación, la
     * conversación del reportante con el vendedor; para uno de usuario, todas
     * las conversaciones entre reportante y reportado. Devuelve mensajes en orden.
     */
    public function reportThread(Request $request, Report $report)
    {
        $reporter = $report->reportante_id;

        $query = Conversation::query();
        if ($report->reportable_tipo === 'listing') {
            $query->where('publicacion_id', $report->reportable_id)
                ->where(fn ($q) => $q->where('comprador_id', $reporter)->orWhere('vendedor_id', $reporter));
        } else {
            // Usuarios: ambas direcciones, cualquier publicación.
            $other = $report->reportable_id;
            $query->where(function ($q) use ($reporter, $other) {
                $q->where(fn ($p) => $p->where('comprador_id', $reporter)->where('vendedor_id', $other))
                  ->orWhere(fn ($p) => $p->where('comprador_id', $other)->where('vendedor_id', $reporter));
            });
        }

        $conversations = $query->with(['buyer:id,nombre', 'seller:id,nombre', 'listing:id,titulo'])->get();
        if ($conversations->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $messages = Message::query()
            ->with('offer')
            ->whereIn('conversacion_id', $conversations->pluck('id'))
            ->orderBy('conversacion_id')
            ->orderBy('id')
            ->limit(500)
            ->get();

        return response()->json(['data' => $conversations->map(function (Conversation $c) use ($messages) {
            $msgs = $messages->where('conversacion_id', $c->id)->values();
            return [
                'id' => $c->id,
                'listing_title' => $c->listing?->titulo,
                'buyer' => ['id' => $c->buyer->id, 'name' => $c->buyer->nombre],
                'seller' => ['id' => $c->seller->id, 'name' => $c->seller->nombre],
                'messages' => $msgs->map(fn (Message $m) => [
                    'id' => $m->id,
                    'sender_id' => $m->remitente_id,
                    'body' => $m->cuerpo,
                    'offer_amount' => $m->offer?->monto,
                    'offer_status' => $m->offer?->estatus,
                    'created_at' => $m->creado_en,
                ])->values(),
            ];
        })->values()]);
    }

    /**
     * Resolver un reporte. Acciones:
     *  - descartar: sin fundamento.
     *  - ocultar_publicacion: suspende la publicación reportada (solo reportes de publicación).
     *  - advertir: advertencia a la cuenta (reportada o dueña de la publicación).
     *  - sancionar: suspensión temporal de `days` días (solo lectura, publicaciones ocultas).
     *  - eliminar_cuenta: baja definitiva; no vuelve a entrar y sus publicaciones se suspenden.
     * Las medidas sobre la cuenta cierran todos sus reportes abiertos. Se avisa a la cuenta afectada
     * (notificación + correo si se sanciona o elimina) y a cada persona que la reportó.
     */
    public function resolveReport(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['descartar', 'resolve', 'ocultar_publicacion', 'advertir', 'sancionar', 'eliminar_cuenta'])],
            'note' => ['required_if:action,advertir,sancionar,eliminar_cuenta', 'nullable', 'string', 'min:5', 'max:500'],
            'days' => ['required_if:action,sancionar', 'nullable', 'integer', Rule::in([1, 3, 7, 15, 30])],
        ], [
            'note.required_if' => 'Escribe el motivo: se le enviará a la cuenta.',
            'days.required_if' => 'Elige cuántos días dura la sanción.',
        ]);
        $action = $data['action'];
        $note = isset($data['note']) ? trim($data['note']) : null;

        abort_unless(in_array($report->estatus, ['abierto', 'investigating'], true), 422, 'Este reporte ya fue atendido.');

        $listing = $report->reportable_tipo === 'listing' ? Listing::withTrashed()->find($report->reportable_id) : null;
        abort_if($action === 'ocultar_publicacion' && (! $listing || $listing->trashed()), 422, 'La publicación reportada ya no existe.');

        // Cuenta sobre la que caen advertir/sancionar/eliminar: la reportada o la dueña de la publicación.
        $target = $report->reportable_tipo === 'user'
            ? User::find($report->reportable_id)
            : ($listing ? User::find($listing->usuario_id) : null);
        $onAccount = in_array($action, ['advertir', 'sancionar', 'eliminar_cuenta'], true);
        if ($onAccount) {
            abort_unless($target, 422, 'La cuenta reportada ya no existe.');
            abort_if($target->id === $request->user()->id, 422, 'No puedes sancionar tu propia cuenta.');
            abort_if($target->can('resolve reports') || $target->can('moderate listings'), 422,
                'No se puede sancionar a otro moderador o administrador desde aquí.');
            abort_if($target->isBanned(), 422, 'Esa cuenta ya fue eliminada.');
        }

        $until = $action === 'sancionar' ? now()->addDays((int) $data['days']) : null;

        $closed = DB::transaction(function () use ($request, $report, $action, $note, $listing, $target, $onAccount, $until, $data) {
            $close = [
                'estatus' => $action === 'descartar' ? 'dismissed' : 'resuelto',
                'resuelto_por' => $request->user()->id,
                'resuelto_en' => now(),
                'nota_resolucion' => $note,
                'accion' => $action,
            ];

            // Qué reportes abiertos se cierran con esta decisión.
            $open = Report::query()->whereIn('estatus', ['abierto', 'investigating']);
            if ($action === 'ocultar_publicacion') {
                $open->where('reportable_tipo', 'listing')->where('reportable_id', $listing->id);
            } elseif ($onAccount) {
                $listingIds = Listing::withTrashed()->where('usuario_id', $target->id)->pluck('id');
                $open->where(fn ($q) => $q
                    ->where(fn ($u) => $u->where('reportable_tipo', 'user')->where('reportable_id', $target->id))
                    ->orWhere(fn ($l) => $l->where('reportable_tipo', 'listing')->whereIn('reportable_id', $listingIds)));
            } else {
                $open->whereKey($report->id);
            }
            $closedReports = $open->lockForUpdate()->get(['id', 'reportante_id']);
            Report::whereIn('id', $closedReports->pluck('id'))->update($close);

            match ($action) {
                'ocultar_publicacion' => $listing->update([
                    'estatus' => 'suspended',
                    'motivo_moderacion' => $note ?: 'Suspendida tras un reporte.',
                ]),
                'advertir' => $target->forceFill(['advertencias' => $target->advertencias + 1])->save(),
                'sancionar' => $target->forceFill(['suspendido_hasta' => $until, 'motivo_suspension' => $note])->save(),
                'eliminar_cuenta' => $this->banAccount($target, $note),
                default => null,
            };

            $this->audit($request, 'report.'.$action, 'report', $report->id, [
                'nota' => $note,
                'dias' => $data['days'] ?? null,
                'usuario_afectado' => $target?->id,
                'publicacion_id' => $listing?->id,
                'reportes_cerrados' => $closedReports->pluck('id')->all(),
            ]);

            return $closedReports;
        });

        // Avisos (fuera de la transacción: un fallo de push no deshace la decisión).
        if ($action === 'ocultar_publicacion') {
            NotificationService::listingModerated('listing_suspended', $listing, $note);
        }
        if ($onAccount) {
            $type = ['advertir' => 'account_warning', 'sancionar' => 'account_suspended', 'eliminar_cuenta' => 'account_deleted'][$action];
            NotificationService::accountSanctioned($type, $target, $note, $until);
            if ($action !== 'advertir') {
                $this->emailSanction($target, $action, $note, $until);
            }
        }
        $targetName = $target ? trim($target->nombre.' '.$target->apellidos) : null;
        foreach ($closed->pluck('reportante_id')->unique() as $reporterId) {
            NotificationService::reportResolved((int) $reporterId, $action, $targetName, $listing?->titulo);
        }

        return response()->noContent();
    }

    /** Baja por moderación: no puede volver a entrar y nada suyo queda visible. */
    private function banAccount(User $user, ?string $reason): void
    {
        $user->forceFill(['eliminado_en' => now(), 'motivo_eliminacion' => $reason])->save();
        $user->tokens()->delete();
        Listing::where('usuario_id', $user->id)
            ->whereIn('estatus', ['publicada', 'en_revision', 'borrador'])
            ->update(['estatus' => 'suspended', 'motivo_moderacion' => 'Cuenta eliminada por moderación.']);
    }

    /** La cuenta sancionada o eliminada puede no ver la app: también se le avisa por correo. */
    private function emailSanction(User $user, string $action, ?string $reason, $until): void
    {
        $body = $action === 'sancionar'
            ? 'Tu cuenta de AgroLink quedó sancionada hasta el '.$until->timezone('America/Merida')->format('d/m/Y H:i')
                .".\n\nMotivo: {$reason}\n\nDurante la sanción puedes entrar y ver tu cuenta, pero no publicar, escribir ni ofertar."
            : "Tu cuenta de AgroLink fue eliminada por incumplir las normas.\n\nMotivo: {$reason}";
        try {
            app(ContactVerificationService::class)->deliver(
                $user->correo,
                $action === 'sancionar' ? 'Tu cuenta fue sancionada — AgroLink' : 'Tu cuenta fue eliminada — AgroLink',
                $body,
            );
        } catch (\Throwable) {
            // El aviso en la app ya quedó; el correo es un extra.
        }
    }

    /**
     * Prueba del correo: manda un mensaje a la cuenta de quien lo pide (nunca a otra) y
     * devuelve el error real del proveedor. Render gratis no tiene consola, así que esta
     * es la forma de diagnosticar Brevo desde la app.
     */
    public function mailTest(Request $request, ContactVerificationService $mail)
    {
        $to = $request->user()->correo;
        $ok = $mail->deliver($to, 'Prueba de correo — AgroLink',
            "Si lees esto, el correo de AgroLink funciona y los códigos de recuperar contraseña van a llegar.");

        return response()->json([
            'sent' => $ok,
            'to' => $to,
            'from' => config('services.brevo.from_email'),
            'provider' => config('services.brevo.key') ? 'brevo' : (config('services.resend.key') ? 'resend' : null),
            'error' => $ok ? null : $mail->lastError,
        ]);
    }
}
