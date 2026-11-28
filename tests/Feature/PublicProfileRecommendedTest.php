<?php

namespace Tests\Feature;

use App\Models\ProductType;
use Tests\ApiTestCase;

class PublicProfileRecommendedTest extends ApiTestCase
{
    public function test_perfil_publico_sin_datos_privados_y_sus_publicaciones(): void
    {
        $seller = $this->makeUser();
        $this->makeListing($seller);
        $this->makeListing($seller, ['estatus' => 'en_revision']);
        $other = $this->makeListing($this->makeUser());

        $res = $this->getJson("/api/users/{$seller->id}/public")->assertOk()
            ->assertJsonPath('data.id', $seller->id)
            ->assertJsonPath('data.listings_count', 1);
        $this->assertArrayNotHasKey('email', $res->json('data'));
        $this->assertArrayNotHasKey('phone', $res->json('data'));

        $ids = collect($this->getJson("/api/listings?seller_id={$seller->id}")->assertOk()->json('data'))->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_perfil_de_cuenta_sancionada_no_se_ve(): void
    {
        $seller = $this->makeUser();
        $seller->forceFill(['suspendido_hasta' => now()->addDays(3)])->save();
        $this->getJson("/api/users/{$seller->id}/public")->assertNotFound();
    }

    public function test_recomendados_primero_del_mismo_tipo_y_sin_la_misma(): void
    {
        $types = ProductType::query()->orderBy('id')->get();
        $base = $this->makeListing($this->makeUser(), ['tipo_producto_id' => $types[0]->id]);
        $otherType = $types->first(fn ($t) => $t->id !== $types[0]->id);
        $distinto = $this->makeListing($this->makeUser(), ['tipo_producto_id' => $otherType->id, 'publicado_en' => now()->addMinute()]);
        $igual = $this->makeListing($this->makeUser(), ['tipo_producto_id' => $types[0]->id]);
        $oculta = $this->makeListing($this->makeUser(), ['tipo_producto_id' => $types[0]->id, 'estatus' => 'en_revision']);

        $ids = collect($this->getJson("/api/listings/{$base->id}/recommended")->assertOk()->json('data'))->pluck('id')->all();
        $this->assertNotContains($base->id, $ids);
        $this->assertNotContains($oculta->id, $ids);
        $this->assertSame($igual->id, $ids[0]);
        $this->assertContains($distinto->id, $ids);
    }
}
