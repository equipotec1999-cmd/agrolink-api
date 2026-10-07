<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ComplianceRule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Administración de reglas de cumplimiento (Fase 1 §22). Solo con el permiso
 * `manage compliance rules` y verificación en dos pasos (ver rutas). Cada cambio queda en la bitácora.
 */
class ComplianceRuleController extends Controller
{
    private function present(ComplianceRule $r): array
    {
        return [
            'id' => $r->id,
            'product_type_id' => $r->tipo_producto_id,
            'category_id' => $r->categoria_id,
            'title' => $r->titulo,
            'description' => $r->descripcion,
            'document_suggested' => $r->documento_sugerido,
            'document_required' => $r->documento_requerido,
            'source_name' => $r->nombre_fuente,
            'source_url' => $r->url_fuente,
            'valid_from' => $r->vigente_desde?->toDateString(),
            'valid_until' => $r->vigente_hasta?->toDateString(),
        ];
    }

    private function rules(bool $creating): array
    {
        $s = $creating ? 'required' : 'sometimes';

        return [
            'product_type_id' => ['nullable', 'integer', 'exists:tipos_producto,id'],
            'category_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'title' => [$s, 'string', 'min:3', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'document_suggested' => ['sometimes', 'boolean'],
            'document_required' => ['sometimes', 'boolean'],
            // Siempre se cita la fuente oficial.
            'source_name' => [$s, 'string', 'min:2', 'max:120'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    private function columns(array $d): array
    {
        $map = [
            'product_type_id' => 'tipo_producto_id', 'category_id' => 'categoria_id', 'title' => 'titulo',
            'description' => 'descripcion', 'document_suggested' => 'documento_sugerido',
            'document_required' => 'documento_requerido', 'source_name' => 'nombre_fuente',
            'source_url' => 'url_fuente', 'valid_from' => 'vigente_desde', 'valid_until' => 'vigente_hasta',
        ];
        $out = [];
        foreach ($map as $api => $col) {
            if (array_key_exists($api, $d)) {
                $out[$col] = $d[$api];
            }
        }

        return $out;
    }

    private function audit(Request $request, string $action, int $id, array $changes): void
    {
        AuditLog::create([
            'usuario_id' => $request->user()->id,
            'accion' => $action,
            'auditable_tipo' => 'compliance_rule',
            'auditable_id' => $id,
            'cambios' => $changes,
            'direccion_ip' => $request->ip(),
            'agente_usuario' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
    }

    public function index()
    {
        return response()->json([
            'data' => ComplianceRule::query()->orderByDesc('id')->limit(200)->get()->map(fn ($r) => $this->present($r)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(true));
        $this->ensureScope($data);

        $rule = ComplianceRule::create($this->columns($data));
        $this->audit($request, 'compliance_rule.created', $rule->id, $this->columns($data));

        return response()->json(['data' => $this->present($rule)], 201);
    }

    public function update(Request $request, ComplianceRule $rule)
    {
        $data = $request->validate($this->rules(false));
        // El alcance resultante debe seguir existiendo (la BD exige tipo o categoría).
        $this->ensureScope(array_merge(
            ['product_type_id' => $rule->tipo_producto_id, 'category_id' => $rule->categoria_id],
            $data,
        ));

        $rule->update($this->columns($data));
        $this->audit($request, 'compliance_rule.updated', $rule->id, $this->columns($data));

        return response()->json(['data' => $this->present($rule->refresh())]);
    }

    public function destroy(Request $request, ComplianceRule $rule)
    {
        $this->audit($request, 'compliance_rule.deleted', $rule->id, ['titulo' => $rule->titulo]);
        $rule->delete();

        return response()->noContent();
    }

    private function ensureScope(array $d): void
    {
        if (empty($d['product_type_id']) && empty($d['category_id'])) {
            throw ValidationException::withMessages([
                'product_type_id' => ['Indica el tipo de producto o la categoría a la que aplica la regla.'],
            ]);
        }
    }
}
