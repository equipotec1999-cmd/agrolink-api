<?php

namespace Tests\Feature;

use Tests\ApiTestCase;

class ChatOfferTest extends ApiTestCase
{
    private function conversation($buyer, $listing): int
    {
        return $this->as($buyer)->postJson("/api/listings/{$listing->id}/conversation")->json('data.id');
    }

    public function test_iniciar_conversacion_es_idempotente_y_no_con_uno_mismo(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $listing = $this->makeListing($seller);

        $this->as($buyer)->postJson("/api/listings/{$listing->id}/conversation")->assertCreated();
        $this->postJson("/api/listings/{$listing->id}/conversation")->assertOk();
        $this->assertDatabaseCount('conversaciones', 1);

        $this->as($seller)->postJson("/api/listings/{$listing->id}/conversation")->assertUnprocessable();
    }

    public function test_no_se_puede_escribir_en_una_publicacion_no_publicada(): void
    {
        $listing = $this->makeListing($this->makeUser('vendedor'), ['estatus' => 'borrador']);

        $this->as($this->makeUser())->postJson("/api/listings/{$listing->id}/conversation")->assertNotFound();
    }

    public function test_mensajes_solo_para_participantes_y_con_conteo_de_no_leidos(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $stranger = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($seller));

        $this->postJson("/api/conversations/$id/messages", ['body' => 'Hola, ¿sigue disponible?'])->assertCreated();

        $this->as($stranger)->getJson("/api/conversations/$id/messages")->assertNotFound();
        $this->postJson("/api/conversations/$id/messages", ['body' => 'intruso'])->assertNotFound();

        $inbox = $this->as($seller)->getJson('/api/conversations')->assertOk();
        $this->assertSame(1, (int) $inbox->json('data.0.unread_count'));

        $this->getJson("/api/conversations/$id/messages")->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame(0, (int) $this->getJson('/api/conversations')->json('data.0.unread_count'));
    }

    public function test_mensaje_vacio_se_rechaza(): void
    {
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($this->makeUser('vendedor')));

        $this->postJson("/api/conversations/$id/messages", ['body' => '   '])->assertUnprocessable();
    }

    public function test_oferta_no_puede_superar_la_cantidad_disponible(): void
    {
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($this->makeUser('vendedor'), ['cantidad' => 10]));

        $this->postJson("/api/conversations/$id/offers", ['amount' => 100, 'quantity' => 11])->assertUnprocessable();
        $this->postJson("/api/conversations/$id/offers", ['amount' => 100, 'quantity' => 10])->assertCreated();
    }

    public function test_una_oferta_por_turno(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($seller));

        $this->postJson("/api/conversations/$id/offers", ['amount' => 1000, 'quantity' => 2])->assertCreated();
        // Quien ofertó no manda otra hasta que le respondan.
        $this->postJson("/api/conversations/$id/offers", ['amount' => 1100, 'quantity' => 2])
            ->assertUnprocessable()->assertJsonFragment(['message' => 'Ya hiciste una oferta. Espera a que la acepten o la rechacen (o cancélala) para hacer otra.']);
        // Quien la recibió tampoco: primero acepta o rechaza.
        $this->as($seller)->postJson("/api/conversations/$id/offers", ['amount' => 1300, 'quantity' => 2])
            ->assertUnprocessable()->assertJsonFragment(['message' => 'Tienes una oferta por responder. Acéptala o recházala antes de hacer otra.']);

        $offer = \App\Models\Offer::firstOrFail();
        $this->postJson("/api/offers/{$offer->id}/reject")->assertOk();
        // Rechazada: ahora sí puede contraofertar.
        $this->postJson("/api/conversations/$id/offers", ['amount' => 1300, 'quantity' => 2])->assertCreated();
        $this->assertSame(1, \App\Models\Offer::where('estatus', 'enviada')->count());
        $this->assertDatabaseHas('notificaciones', ['notificable_id' => $buyer->id, 'tipo' => 'offer_countered']);
    }

    public function test_con_operacion_en_curso_no_hay_ofertas_nuevas(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($seller));

        $this->postJson("/api/conversations/$id/offers", ['amount' => 1000, 'quantity' => 1])->assertCreated();
        $offer = \App\Models\Offer::firstOrFail();
        $this->as($seller)->postJson("/api/offers/{$offer->id}/accept")->assertOk();

        $this->as($buyer)->postJson("/api/conversations/$id/offers", ['amount' => 900, 'quantity' => 1])->assertUnprocessable();
    }

    public function test_aceptar_crea_la_operacion_con_el_total(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($seller));

        $this->postJson("/api/conversations/$id/offers", ['amount' => 1400, 'quantity' => 2])->assertCreated();
        $offer = \App\Models\Offer::firstOrFail();

        // Quien la envió no puede aceptarla.
        $this->postJson("/api/offers/{$offer->id}/accept")->assertForbidden();

        $this->as($seller)->postJson("/api/offers/{$offer->id}/accept")->assertOk();

        $this->assertSame('aceptada', $offer->fresh()->estatus);
        $this->assertDatabaseHas('operaciones', [
            'oferta_id' => $offer->id,
            'comprador_id' => $buyer->id,
            'vendedor_id' => $seller->id,
            'monto' => 2800,
            'estatus' => 'oferta_aceptada',
        ]);

        // Ya respondida: no se puede volver a aceptar.
        $this->postJson("/api/offers/{$offer->id}/accept")->assertStatus(422);
    }

    public function test_rechazar_y_cancelar_respetan_quien_es_quien(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($seller));

        $this->postJson("/api/conversations/$id/offers", ['amount' => 1000, 'quantity' => 1])->assertCreated();
        $offer = \App\Models\Offer::firstOrFail();

        $this->postJson("/api/offers/{$offer->id}/reject")->assertForbidden();
        $this->as($seller)->postJson("/api/offers/{$offer->id}/cancel")->assertForbidden();

        $this->postJson("/api/offers/{$offer->id}/reject")->assertOk();
        $this->assertSame('rechazada', $offer->fresh()->estatus);
    }

    public function test_ofertas_de_otra_conversacion_no_se_pueden_tocar(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($seller));
        $this->postJson("/api/conversations/$id/offers", ['amount' => 1000, 'quantity' => 1])->assertCreated();
        $offer = \App\Models\Offer::firstOrFail();

        $this->as($this->makeUser())->postJson("/api/offers/{$offer->id}/accept")->assertNotFound();
    }

    public function test_listado_de_operaciones_por_rol(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($seller));
        $this->postJson("/api/conversations/$id/offers", ['amount' => 1000, 'quantity' => 1])->assertCreated();
        $this->as($seller)->postJson('/api/offers/'.\App\Models\Offer::firstOrFail()->id.'/accept')->assertOk();

        $this->getJson('/api/operations?role=seller')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/operations?role=buyer')->assertOk()->assertJsonCount(0, 'data');
        $this->as($buyer)->getJson('/api/operations?role=buyer')->assertOk()->assertJsonCount(1, 'data');
    }

    private function operation($seller, $buyer): int
    {
        $id = $this->conversation($buyer, $this->makeListing($seller));
        $this->postJson("/api/conversations/$id/offers", ['amount' => 1000, 'quantity' => 1])->assertCreated();
        $this->as($seller)->postJson('/api/offers/'.\App\Models\Offer::firstOrFail()->id.'/accept')->assertOk();

        return \App\Models\Operation::firstOrFail()->id;
    }

    public function test_ciclo_completo_pago_y_entrega_con_pin(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $op = $this->operation($seller, $buyer);

        $pin = $this->as($buyer)->postJson("/api/operations/$op/transition", ['status' => 'pendiente_pago'])
            ->assertOk()->assertJsonPath('data.status', 'pendiente_pago')->json('data.delivery_pin');
        $this->assertMatchesRegularExpression('/^[0-9]{4}$/', $pin);
        // El comprador no puede confirmar su propio pago.
        $this->postJson("/api/operations/$op/transition", ['status' => 'pagado'])->assertUnprocessable();
        // El comprador no puede escribir el PIN.
        $this->postJson("/api/operations/$op/deliver", ['pin' => $pin])->assertForbidden();

        $this->as($seller)->postJson("/api/operations/$op/transition", ['status' => 'pagado'])
            ->assertOk()->assertJsonPath('data.delivery_pin', null)->assertJsonPath('data.can_deliver', true);
        $this->postJson("/api/operations/$op/transition", ['status' => 'en_transito'])->assertOk();
        // Sin PIN el vendedor no puede marcarla entregada ni completada.
        $this->postJson("/api/operations/$op/transition", ['status' => 'entregado'])->assertUnprocessable();
        $this->postJson("/api/operations/$op/transition", ['status' => 'completado'])->assertUnprocessable();

        $wrong = $pin === '0000' ? '1111' : '0000';
        $this->postJson("/api/operations/$op/deliver", ['pin' => $wrong])->assertUnprocessable();
        $this->postJson("/api/operations/$op/deliver", ['pin' => $pin])
            ->assertOk()->assertJsonPath('data.status', 'completado')->assertJsonPath('data.can_review', true);
        // Reintento idempotente.
        $this->postJson("/api/operations/$op/deliver", ['pin' => $pin])->assertOk();

        $this->assertSame(1, \App\Models\SellerProfile::find($seller->id)->operaciones_completadas);
        // creada, pendiente_pago, pagado, en_transito, entregado, completado
        $this->assertSame(6, \App\Models\OperationEvent::where('operacion_id', $op)->count());
    }

    public function test_el_comprador_confirma_que_recibio_sin_pin(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $op = $this->operation($seller, $buyer);

        $this->as($seller)->postJson("/api/operations/$op/transition", ['status' => 'pagado'])->assertOk();
        $this->postJson("/api/operations/$op/transition", ['status' => 'en_transito'])->assertOk();
        $this->as($buyer)->postJson("/api/operations/$op/transition", ['status' => 'completado'])
            ->assertOk()->assertJsonPath('data.status', 'completado');
    }

    public function test_pin_se_bloquea_tras_cinco_fallos_y_el_comprador_genera_otro(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $op = $this->operation($seller, $buyer);
        $pin = \App\Models\Operation::find($op)->pin_entrega;
        $wrong = $pin === '0000' ? '1111' : '0000';

        $this->as($seller);
        for ($i = 0; $i < 4; $i++) {
            $this->postJson("/api/operations/$op/deliver", ['pin' => $wrong])->assertUnprocessable();
        }
        $this->postJson("/api/operations/$op/deliver", ['pin' => $wrong])->assertStatus(423);
        $this->postJson("/api/operations/$op/deliver", ['pin' => $pin])->assertStatus(423);

        $new = $this->as($buyer)->postJson("/api/operations/$op/pin")->assertOk()
            ->assertJsonPath('data.pin_locked', false)->json('data.delivery_pin');
        $this->as($seller)->postJson("/api/operations/$op/deliver", ['pin' => $new])
            ->assertOk()->assertJsonPath('data.status', 'completado');
    }

    public function test_cancelar_exige_motivo_y_respeta_el_momento(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $op = $this->operation($seller, $buyer);

        $this->as($this->makeUser())->postJson("/api/operations/$op/transition", ['status' => 'cancelado', 'reason' => 'x x x'])->assertNotFound();
        $this->as($buyer)->postJson("/api/operations/$op/transition", ['status' => 'cancelado'])->assertUnprocessable();
        $this->postJson("/api/operations/$op/transition", ['status' => 'cancelado', 'reason' => 'Ya no lo necesito'])
            ->assertOk()->assertJsonPath('data.status', 'cancelado')
            ->assertJsonPath('data.cancel_reason', 'Ya no lo necesito');
        $this->as($seller)->postJson("/api/operations/$op/transition", ['status' => 'pagado'])->assertUnprocessable();
    }

    public function test_tras_el_pago_solo_el_vendedor_cancela_y_en_camino_nadie(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $op = $this->operation($seller, $buyer);
        $this->as($seller)->postJson("/api/operations/$op/transition", ['status' => 'pagado'])->assertOk();

        $this->as($buyer)->postJson("/api/operations/$op/transition", ['status' => 'cancelado', 'reason' => 'Me arrepentí'])->assertUnprocessable();
        $this->as($seller)->getJson("/api/operations/$op")->assertJsonPath('data.can_cancel', true);
        $this->postJson("/api/operations/$op/transition", ['status' => 'en_transito'])->assertOk()
            ->assertJsonPath('data.can_cancel', false);
        $this->postJson("/api/operations/$op/transition", ['status' => 'cancelado', 'reason' => 'Sin producto'])->assertUnprocessable();
    }

    public function test_calificar_solo_una_vez_y_solo_si_esta_completada(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $op = $this->operation($seller, $buyer);
        $rating = ['accuracy' => 5, 'fulfillment' => 4, 'communication' => 5, 'comment' => 'Todo bien'];

        $this->as($buyer)->postJson("/api/operations/$op/review", $rating)->assertUnprocessable();

        \App\Models\Operation::whereKey($op)->update(['estatus' => 'completado']);
        $this->postJson("/api/operations/$op/review", ['accuracy' => 9] + $rating)->assertUnprocessable();
        $this->postJson("/api/operations/$op/review", $rating)->assertCreated();
        $this->postJson("/api/operations/$op/review", $rating)->assertUnprocessable();

        $perfil = \App\Models\SellerProfile::find($seller->id);
        $this->assertEquals(5.0, $perfil->calificacion_exactitud);
        $this->assertEquals(4.0, $perfil->calificacion_cumplimiento);

        // El vendedor también califica (otros ejes) y eso no toca su propio perfil.
        $this->as($seller)->postJson("/api/operations/$op/review", ['payment' => 5, 'reception' => 5, 'communication' => 4])->assertCreated();
        $this->getJson("/api/sellers/{$seller->id}/reviews")->assertOk()->assertJsonCount(1, 'data');
    }
}
