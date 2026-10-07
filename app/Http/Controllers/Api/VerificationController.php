<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VerificationRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Verificación de vendedor, lado de quien la pide. Los documentos (identificación y comprobante
 * de actividad) son datos sensibles: se guardan con nombre impredecible y solo los ve moderación.
 */
class VerificationController extends Controller
{
    private const DOCS = [
        'id_document' => 'identificacion',
        'activity_document' => 'comprobante_actividad',
        'other_document' => 'otro',
    ];

    /** Estado actual: verificado, y la última solicitud (si hay). */
    public function show(Request $request)
    {
        $user = $request->user();
        $last = VerificationRequest::where('usuario_id', $user->id)->latest('id')->first();

        return response()->json(['data' => [
            'is_verified' => (bool) $user->sellerProfile?->verificado,
            'request' => $last ? [
                'id' => $last->id,
                'status' => $last->estatus,
                'business_name' => $last->nombre_negocio,
                'rejection_reason' => $last->estatus === 'rechazada' ? $last->motivo_rechazo : null,
                'created_at' => $last->creado_en,
            ] : null,
        ]]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_if($user->sellerProfile?->verificado, 422, 'Tu cuenta ya es de vendedor verificado.');

        $image = ['file', 'mimes:jpg,jpeg,png', 'max:6144'];
        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:150'],
            'id_document' => ['required', ...$image],
            'activity_document' => ['required', ...$image],
            'other_document' => ['nullable', ...$image],
        ]);

        abort_if(
            VerificationRequest::where('usuario_id', $user->id)->where('estatus', 'pendiente')->exists(),
            422,
            'Ya tienes una solicitud en revisión.'
        );

        $disk = config('filesystems.verification_disk');
        $stored = [];

        try {
            $req = DB::transaction(function () use ($request, $user, $data, $disk, &$stored) {
                $req = VerificationRequest::create([
                    'usuario_id' => $user->id,
                    'estatus' => 'pendiente',
                    'nombre_negocio' => $data['business_name'] ?? null,
                ]);

                foreach (self::DOCS as $field => $type) {
                    $file = $request->file($field);
                    if (! $file) {
                        continue;
                    }
                    // Nombre aleatorio: la ruta no se puede adivinar aunque el almacenamiento sea público.
                    $path = $file->storeAs(
                        "verificacion/{$user->id}",
                        Str::random(40).'.'.$file->getClientOriginalExtension(),
                        ['disk' => $disk, 'visibility' => 'private']
                    );
                    abort_if($path === false, 502, 'No se pudo guardar el documento.');
                    $stored[] = $path;

                    $req->documents()->create([
                        'tipo' => $type,
                        'ruta_almacenamiento' => $path,
                        'nombre_original' => Str::limit($file->getClientOriginalName(), 200, ''),
                        'tipo_mime' => (string) $file->getMimeType(),
                        'tamano' => (int) $file->getSize(),
                        'disco' => $disk,
                    ]);
                }

                return $req;
            });
        } catch (UniqueConstraintViolationException) {
            $this->discard($disk, $stored);
            abort(422, 'Ya tienes una solicitud en revisión.');
        } catch (\Throwable $e) {
            $this->discard($disk, $stored);
            throw $e;
        }

        return response()->json(['data' => ['id' => $req->id, 'status' => 'pendiente']], 201);
    }

    private function discard(string $disk, array $paths): void
    {
        foreach ($paths as $p) {
            Storage::disk($disk)->delete($p);
        }
    }
}
