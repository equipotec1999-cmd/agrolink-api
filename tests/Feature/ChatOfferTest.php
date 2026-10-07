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

    public function test_contraoferta_marca_la_anterior_como_contraofertada(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $id = $this->conversation($buyer, $this->makeListing($seller));

        $first = $this->postJson("/api/conversations/$id/offers", ['amount' => 1000, 'quantity' => 2])->assertCreated();
        $offerId = $first->json('data.offer.id') ?? $first->json('data.offer_id');

        $this->as($seller)->postJson("/api/conversations/$id/offers", ['amount' => 1300, 'quantity' => 2])->assertCreated();

        $this->assertDatabaseHas('ofertas', ['conversacion_id' => $id, 'estatus' => 'contraoferta']);
        $this->assertDatabaseHas('ofertas', ['conversacion_id' => $id, 'estatus' => 'enviada', 'monto' => 1300]);
        $this->assertSame(1, \App\Models\Offer::where('estatus', 'enviada')->count());
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
}
