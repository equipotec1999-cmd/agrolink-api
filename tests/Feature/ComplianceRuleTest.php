<?php

namespace Tests\Feature;

use App\Models\ComplianceRule;
use Tests\ApiTestCase;

class ComplianceRuleTest extends ApiTestCase
{
    private function admin()
    {
        return $this->makeUser('administrador', twoFactor: true);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'product_type_id' => $this->productType()->id,
            'title' => 'Guía de tránsito',
            'source_name' => 'SENASICA',
            'source_url' => 'https://www.gob.mx/senasica',
            'document_required' => true,
        ];
    }

    public function test_moderador_y_vendedor_no_administran_reglas(): void
    {
        $this->as($this->makeUser('moderador'))->getJson('/api/admin/compliance-rules')->assertForbidden();
        $this->as($this->makeUser('vendedor'))->postJson('/api/admin/compliance-rules', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('reglas_cumplimiento', 0);
    }

    public function test_admin_crea_lista_edita_y_elimina_con_bitacora(): void
    {
        $admin = $this->admin();

        $id = $this->as($admin)->postJson('/api/admin/compliance-rules', $this->payload())
            ->assertCreated()->assertJsonPath('data.document_required', true)->json('data.id');

        $this->getJson('/api/admin/compliance-rules')->assertOk()->assertJsonCount(1, 'data');

        $this->patchJson("/api/admin/compliance-rules/$id", ['title' => 'Guía actualizada'])
            ->assertOk()->assertJsonPath('data.title', 'Guía actualizada');

        $this->deleteJson("/api/admin/compliance-rules/$id")->assertNoContent();
        $this->assertSame(0, ComplianceRule::count());

        foreach (['created', 'updated', 'deleted'] as $accion) {
            $this->assertDatabaseHas('bitacora_auditoria', ['usuario_id' => $admin->id, 'accion' => "compliance_rule.$accion"]);
        }
    }

    public function test_exige_alcance_fuente_y_url_valida(): void
    {
        $this->as($this->admin());

        $this->postJson('/api/admin/compliance-rules', $this->payload(['product_type_id' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('product_type_id');

        $this->postJson('/api/admin/compliance-rules', $this->payload(['source_name' => '']))->assertUnprocessable();
        $this->postJson('/api/admin/compliance-rules', $this->payload(['source_url' => 'no-es-url']))->assertUnprocessable();
    }
}
