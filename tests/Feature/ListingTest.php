<?php

namespace Tests\Feature;

use App\Models\ProductType;
use Tests\ApiTestCase;

class ListingTest extends ApiTestCase
{
    private function type(): ProductType
    {
        return ProductType::query()->orderBy('id')->firstOrFail();
    }

    /** Valores válidos para los atributos obligatorios del tipo (respetando mínimos y máximos). */
    private function requiredAttributes(ProductType $type): array
    {
        $values = [];
        foreach ($type->attributes()->get() as $attr) {
            if (! $attr->pivot->es_obligatorio) {
                continue;
            }
            $values[$attr->clave] = match ($attr->tipo_dato) {
                'number' => $attr->pivot->valor_minimo ?? 1,
                'boolean' => true,
                'date' => '2026-01-01',
                'select' => $attr->options()->orderBy('id')->value('valor') ?? 'x',
                default => 'x',
            };
        }

        return $values;
    }

    private function payload(array $over = []): array
    {
        $type = $this->type();

        return $over + [
            'product_type_id' => $type->id,
            'title' => 'Lote de prueba',
            'description' => 'Cosecha reciente',
            'price' => 120,
            'price_type' => 'per_kg',
            'quantity' => 50,
            'unit' => 'kg',
            'sale_mode' => 'individual',
            'negotiable' => true,
            'attributes' => $this->requiredAttributes($type),
            'location' => ['state' => 'Yucatán', 'municipality' => 'Tizimín', 'lat' => 21.1411, 'lng' => -88.1500],
        ];
    }

    public function test_crear_es_borrador_y_no_expone_la_ubicacion_exacta(): void
    {
        $r = $this->as($this->makeUser())->postJson('/api/listings', $this->payload())->assertCreated();

        $r->assertJsonPath('data.status', 'draft')->assertJsonPath('data.location.municipality', 'Tizimín');

        $lat = $r->json('data.location.approx_lat');
        $this->assertIsFloat($lat);
        $this->assertNotSame(21.1411, $lat, 'la ubicación pública debe llevar desplazamiento');
        $this->assertLessThan(0.1, abs($lat - 21.1411));
        $this->assertArrayNotHasKey('lat', $r->json('data.location'));
    }

    public function test_crear_valida_campos(): void
    {
        $this->as($this->makeUser());

        $this->postJson('/api/listings', $this->payload(['title' => '']))->assertUnprocessable();
        $this->postJson('/api/listings', $this->payload(['quantity' => 0]))->assertUnprocessable();
        $this->postJson('/api/listings', $this->payload(['price_type' => 'regalado']))->assertUnprocessable();
        $this->postJson('/api/listings', $this->payload(['location' => null]))->assertUnprocessable();
    }

    public function test_atributos_obligatorios_faltantes_se_rechazan(): void
    {
        $type = $this->type();
        if ($this->requiredAttributes($type) === []) {
            $this->markTestSkipped('El primer tipo de producto no tiene atributos obligatorios.');
        }

        $this->as($this->makeUser())->postJson('/api/listings', $this->payload(['attributes' => []]))
            ->assertUnprocessable()->assertJsonValidationErrors('attributes');
    }

    public function test_crear_exige_sesion(): void
    {
        $this->postJson('/api/listings', $this->payload())->assertUnauthorized();
    }

    public function test_publicar_la_muestra_en_el_feed_y_los_borradores_no(): void
    {
        $user = $this->makeUser();
        $this->as($user);

        $borrador = $this->postJson('/api/listings', $this->payload(['title' => 'Borrador oculto']))->json('data.id');
        $publicada = $this->postJson('/api/listings', $this->payload(['title' => 'Publicada visible']))->json('data.id');
        $this->postJson("/api/listings/$publicada/publish")->assertOk()->assertJsonPath('data.status', 'published');

        $ids = collect($this->getJson('/api/listings')->assertOk()->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($publicada));
        $this->assertFalse($ids->contains($borrador));
    }

    public function test_solo_el_dueno_edita_archiva_y_elimina(): void
    {
        $owner = $this->makeUser();
        $id = $this->as($owner)->postJson('/api/listings', $this->payload())->json('data.id');

        $this->as($this->makeUser())->patchJson("/api/listings/$id", ['title' => 'Robada'])->assertForbidden();
        $this->postJson("/api/listings/$id/archive")->assertForbidden();
        $this->deleteJson("/api/listings/$id")->assertForbidden();

        $this->as($owner)->patchJson("/api/listings/$id", ['title' => 'Nuevo título', 'price' => 99])
            ->assertOk()->assertJsonPath('data.title', 'Nuevo título');
        $this->postJson("/api/listings/$id/archive")->assertOk()->assertJsonPath('data.status', 'archived');
        $this->deleteJson("/api/listings/$id")->assertOk();
        $this->assertSoftDeleted('publicaciones', ['id' => $id], null, 'eliminado_en');
    }

    public function test_mis_publicaciones_solo_trae_las_mias(): void
    {
        $yo = $this->makeUser();
        $otro = $this->makeUser();
        $this->as($otro)->postJson('/api/listings', $this->payload(['title' => 'Ajena']))->assertCreated();
        $this->as($yo)->postJson('/api/listings', $this->payload(['title' => 'Mía']))->assertCreated();

        $titles = collect($this->getJson('/api/my/listings')->assertOk()->json('data'))->pluck('title');
        $this->assertSame(['Mía'], $titles->all());
    }
}
