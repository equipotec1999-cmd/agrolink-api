<?php

namespace Tests\Feature;

use App\Models\FeaturedRequest;
use App\Support\FeaturedPricing;
use Tests\ApiTestCase;

class FeaturedTest extends ApiTestCase
{
    public function test_precio_baja_un_peso_por_dia_sin_bajar_de_8(): void
    {
        $this->assertSame(20, FeaturedPricing::total(1));
        $this->assertSame(39, FeaturedPricing::total(2));
        $this->assertSame(57, FeaturedPricing::total(3));
        $this->assertSame(74, FeaturedPricing::total(4));
        $this->assertSame(8, FeaturedPricing::dayPrice(13));
        $this->assertSame(8, FeaturedPricing::dayPrice(40));
        // 20+19+...+9 (12 días) = 174; después 8 por día.
        $this->assertSame(174 + 8 * 3, FeaturedPricing::total(15));

        $this->getJson('/api/featured/pricing?days=3')->assertOk()
            ->assertJsonPath('data.total', 57)
            ->assertJsonPath('data.per_day', [20, 19, 18])
            ->assertJsonPath('data.savings', 3)
            ->assertJsonPath('data.enabled', false);
    }

    public function test_usuarios_no_pueden_pedir_destacado_mientras_este_apagado(): void
    {
        $seller = $this->makeUser('vendedor');
        $listing = $this->makeListing($seller);
        $this->as($seller)->postJson('/api/featured/requests', ['type' => 'listing', 'listing_id' => $listing->id, 'days' => 2])
            ->assertForbidden();
        $this->assertDatabaseCount('solicitudes_destacado', 0);
    }

    public function test_solicitud_pagada_y_activacion_por_moderacion(): void
    {
        config(['agrolink.featured_sales' => true]);
        $seller = $this->makeUser('vendedor');
        $listing = $this->makeListing($seller);

        $id = $this->as($seller)->postJson('/api/featured/requests', ['type' => 'listing', 'listing_id' => $listing->id, 'days' => 3])
            ->assertCreated()
            ->assertJsonPath('data.amount', 57)
            ->assertJsonPath('data.status', 'pendiente_pago')
            ->json('data.id');

        // Ajena: no se puede destacar una publicación de otro.
        $this->as($this->makeUser('vendedor'))
            ->postJson('/api/featured/requests', ['type' => 'listing', 'listing_id' => $listing->id, 'days' => 1])
            ->assertForbidden();

        $this->as($this->makeUser('moderador'))->postJson("/api/moderation/featured-requests/{$id}/activate")
            ->assertOk()->assertJsonPath('data.status', 'activa');

        $this->assertTrue($listing->fresh()->destacada_hasta->between(now()->addDays(3)->subMinute(), now()->addDays(3)->addMinute()));
        $this->getJson('/api/listings?featured=1')->assertOk()->assertJsonPath('data.0.id', $listing->id)->assertJsonPath('data.0.featured', true);
        $this->assertDatabaseHas('notificaciones', ['notificable_id' => $seller->id, 'tipo' => 'featured_activated']);
    }

    public function test_moderador_destaca_y_quita_publicacion_y_perfil(): void
    {
        $mod = $this->makeUser('moderador');
        $seller = $this->makeUser('vendedor');
        $old = $this->makeListing($seller, ['publicado_en' => now()->subDays(5)]);
        $this->makeListing($this->makeUser('vendedor'));

        // Un vendedor cualquiera no puede.
        $this->as($seller)->postJson("/api/moderation/listings/{$old->id}/feature", ['days' => 2])->assertForbidden();

        $this->as($mod)->postJson("/api/moderation/listings/{$old->id}/feature", ['days' => 2])->assertOk();
        // La destacada sale primero aunque sea más vieja.
        $this->getJson('/api/listings')->assertOk()->assertJsonPath('data.0.id', $old->id);
        // Volver a destacar suma días a partir del vencimiento.
        $this->as($mod)->postJson("/api/moderation/listings/{$old->id}/feature", ['days' => 1])->assertOk();
        $this->assertTrue($old->fresh()->destacada_hasta->greaterThan(now()->addDays(3)->subMinute()));
        $this->as($mod)->deleteJson("/api/moderation/listings/{$old->id}/feature")->assertOk();
        $this->assertNull($old->fresh()->destacada_hasta);

        $this->as($mod)->postJson("/api/moderation/users/{$seller->id}/feature", ['days' => 7])->assertOk();
        $this->getJson("/api/users/{$seller->id}/public")->assertJsonPath('data.is_featured', true);
        $this->as($mod)->deleteJson("/api/moderation/users/{$seller->id}/feature")->assertOk();
        $this->getJson("/api/users/{$seller->id}/public")->assertJsonPath('data.is_featured', false);
    }

    public function test_rechazar_solicitud_avisa_con_motivo(): void
    {
        $seller = $this->makeUser('vendedor');
        $req = FeaturedRequest::create([
            'usuario_id' => $seller->id, 'tipo' => 'perfil', 'dias' => 2, 'monto' => 39, 'estatus' => 'pendiente_pago',
        ]);
        $this->as($this->makeUser('moderador'))->postJson("/api/moderation/featured-requests/{$req->id}/reject", ['reason' => 'No se recibió el pago'])
            ->assertOk()->assertJsonPath('data.status', 'rechazada');
        $this->assertNull($seller->fresh()->destacado_hasta);
    }

    public function test_buscar_perfiles_por_nombre_sin_datos_privados(): void
    {
        $ana = $this->makeUser('vendedor');
        $ana->forceFill(['nombre' => 'Ána', 'apellidos' => 'Pech Canul'])->save();
        $this->makeListing($ana);
        $suspendido = $this->makeUser('vendedor');
        $suspendido->forceFill(['nombre' => 'Ana', 'apellidos' => 'Oculta', 'suspendido_hasta' => now()->addDay()])->save();

        $res = $this->getJson('/api/users/search?q=ana pech')->assertOk();
        $this->assertSame([$ana->id], collect($res->json('data'))->pluck('id')->all());
        $this->assertSame(1, $res->json('data.0.listings_count'));
        $this->assertArrayNotHasKey('email', $res->json('data.0'));

        // Sin texto: sugerencias de cuentas con publicaciones, destacadas primero.
        $otro = $this->makeUser('vendedor');
        $this->makeListing($otro);
        $otro->forceFill(['destacado_hasta' => now()->addDay()])->save();
        $this->getJson('/api/users/search')->assertOk()->assertJsonPath('data.0.id', $otro->id)->assertJsonPath('data.0.is_featured', true);
    }
}
