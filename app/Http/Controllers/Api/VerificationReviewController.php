<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SellerProfile;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Verificación de vendedor, lado de moderación (permiso `moderate documents`). */
class VerificationReviewController extends Controller
{
    private function audit(Request $request, string $action, VerificationRequest $req, array $changes = []): void
    {
        AuditLog::create([
            'usuario_id' => $request->user()->id,
            'accion' => $action,
            'auditable_tipo' => 'verification_request',
            'auditable_id' => $req->id,
            'cambios' => $changes,
            'direccion_ip' => $request->ip(),
            'agente_usuario' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
    }

    /** Solicitudes pendientes, las más antiguas primero. */
    public function index()
    {
        $items = VerificationRequest::query()
            ->with(['user:id,nombre,correo', 'documents'])
            ->where('estatus', 'pending')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'business_name' => $r->nombre_negocio,
                'user' => ['id' => $r->user->id, 'name' => $r->user->nombre, 'email' => $r->user->correo],
                'created_at' => $r->creado_en,
                'documents' => $r->documents->map(fn ($d) => ['id' => $d->id, 'type' => $d->tipo, 'mime' => $d->tipo_mime])->values(),
            ]);

        return response()->json(['data' => $items]);
    }

    /** Entrega el archivo (a través de la API, con sesión de moderación; nunca un enlace público). */
    public function document(Request $request, VerificationRequest $verification, VerificationDocument $document)
    {
        abort_unless($document->solicitud_id === $verification->id, 404);
        $this->audit($request, 'verification.document_viewed', $verification, ['documento' => $document->tipo]);

        return Storage::disk(config('filesystems.default'))->response(
            $document->ruta_almacenamiento,
            null,
            ['Cache-Control' => 'private, no-store', 'Content-Type' => $document->tipo_mime]
        );
    }

    public function approve(Request $request, VerificationRequest $verification)
    {
        abort_unless($verification->estatus === 'pending', 422, 'Esta solicitud ya fue revisada.');

        DB::transaction(function () use ($request, $verification) {
            $verification->update([
                'estatus' => 'approved',
                'revisado_por' => $request->user()->id,
                'revisado_en' => now(),
                'motivo_rechazo' => null,
            ]);

            $values = ['verificado' => true, 'verificado_en' => now()];
            if ($verification->nombre_negocio) {
                $values['nombre_negocio'] = $verification->nombre_negocio;
            }
            SellerProfile::updateOrCreate(['usuario_id' => $verification->usuario_id], $values);

            $this->audit($request, 'verification.approved', $verification);
        });

        NotificationService::verificationReviewed('verification_approved', $verification->usuario_id, null);

        return response()->noContent();
    }

    public function reject(Request $request, VerificationRequest $verification)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:300']]);
        abort_unless($verification->estatus === 'pending', 422, 'Esta solicitud ya fue revisada.');

        DB::transaction(function () use ($request, $verification, $data) {
            $verification->update([
                'estatus' => 'rejected',
                'revisado_por' => $request->user()->id,
                'revisado_en' => now(),
                'motivo_rechazo' => $data['reason'],
            ]);
            $this->audit($request, 'verification.rejected', $verification, ['motivo' => $data['reason']]);
        });

        NotificationService::verificationReviewed('verification_rejected', $verification->usuario_id, $data['reason']);

        return response()->noContent();
    }
}
