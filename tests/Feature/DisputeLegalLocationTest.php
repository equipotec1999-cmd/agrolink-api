<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Offer;
use App\Models\Operation;
use App\Models\SellerProfile;
use Tests\ApiTestCase;

class DisputeLegalLocationTest extends ApiTestCase
{
    private function operation($seller, $buyer): array
    {
        $conv = $this->as($buyer)->postJson('/api/listings/'.$this->makeListing($seller)->id.'/conversation')->json('data.id');
        $this->postJson("/api/conversations/$conv/offers", ['amount' => 1000, 'quantity' => 1])->assertCreated();
        $this->as($seller)->postJson('/api/offers/'.Offer::firstOrFail()->id.'/accept')->assertOk();

        return [Operation::firstOrFail()->id, $conv];
    }

    public function test_compartir_ubicacion_en_el_chat(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        [, $conv] = $this->operation($seller, $buyer);

        $this->as($buyer)->postJson("/api/conversations/$conv/location", ['lat' => 21.1555, 'lng' => -88.1532, 'label' => 'Entrada del rancho'])
            ->assertCreated()
            ->assertJsonPath('data.location.lat', 21.1555)
            ->assertJsonPath('data.location.label', 'Entrada del rancho');
        $this->postJson("/api/conversations/$conv/location", ['lat' => 120, 'lng' => 0])->assertUnprocessable();

        $loc = collect($this->as($seller)->getJson("/api/conversations/$conv/messages")->assertOk()->json('data'))
            ->firstWhere('location', '!=', null);
        $this->assertSame(-88.1532, $loc['location']['lng']);
        $this->as($this->makeUser())->postJson("/api/conversations/$conv/location", ['lat' => 1, 'lng' => 1])->assertNotFound();
    }

    public function test_disputa_congela_la_operacion_y_moderacion_decide(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        [$op] = $this->operation($seller, $buyer);

        // Recién aceptada no hay nada en juego: se cancela, no se disputa.
        $this->as($buyer)->postJson("/api/operations/$op/dispute", ['reason' => 'no_llego', 'description' => 'Todavía no pasa nada aquí.'])
            ->assertUnprocessable();

        $this->postJson("/api/operations/$op/transition", ['status' => 'pendiente_pago'])->assertOk();
        $this->as($seller)->postJson("/api/operations/$op/transition", ['status' => 'pagado'])->assertOk();

        $this->as($buyer)->postJson("/api/operations/$op/dispute", ['reason' => 'no_llego', 'description' => 'Pagué hace una semana y no me han entregado.'])
            ->assertOk()
            ->assertJsonPath('data.dispute.status', 'abierta')
            ->assertJsonPath('data.dispute.opened_by_me', true)
            ->assertJsonPath('data.actions', [])
            ->assertJsonPath('data.can_dispute', false);
        // Solo una disputa por operación.
        $this->postJson("/api/operations/$op/dispute", ['reason' => 'otro', 'description' => 'Otra vez el mismo problema.'])->assertUnprocessable();
        // Quien la abrió no se responde a sí mismo.
        $this->postJson("/api/operations/$op/dispute/reply", ['text' => 'Respuesta de mí mismo.'])->assertUnprocessable();

        // Congelada: el vendedor no puede moverla.
        $this->as($seller)->postJson("/api/operations/$op/transition", ['status' => 'en_transito'])->assertStatus(409);
        $this->postJson("/api/operations/$op/dispute/reply", ['text' => 'Ya lo mandé por paquetería, tengo la guía.'])
            ->assertOk()->assertJsonPath('data.dispute.response', 'Ya lo mandé por paquetería, tengo la guía.');
        $this->postJson("/api/operations/$op/dispute/reply", ['text' => 'Otra respuesta más larga.'])->assertUnprocessable();

        $this->as($this->makeUser('vendedor'))->getJson('/api/moderation/disputes')->assertForbidden();
        $mod = $this->makeUser('moderador');
        $dispute = $this->as($mod)->getJson('/api/moderation/disputes')->assertOk()->assertJsonCount(1, 'data')->json('data.0');
        $this->assertNotEmpty($dispute['messages']);

        $this->postJson("/api/moderation/disputes/{$dispute['id']}/resolve", ['decision' => 'favor_comprador', 'note' => 'El vendedor no mostró la guía de envío.'])
            ->assertOk()->assertJsonPath('data.decision', 'favor_comprador');
        $this->assertSame('cancelado', Operation::find($op)->estatus);
        $this->postJson("/api/moderation/disputes/{$dispute['id']}/resolve", ['decision' => 'reanudar', 'note' => 'Otra decisión distinta.'])->assertUnprocessable();

        $this->assertDatabaseHas('notificaciones', ['notificable_id' => $buyer->id, 'tipo' => 'dispute_resolved']);
        $this->assertDatabaseHas('notificaciones', ['notificable_id' => $seller->id, 'tipo' => 'dispute_resolved']);
    }

    public function test_disputa_a_favor_del_vendedor_completa_la_operacion(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        [$op] = $this->operation($seller, $buyer);
        $this->as($buyer)->postJson("/api/operations/$op/transition", ['status' => 'pendiente_pago'])->assertOk();

        $this->as($seller)->postJson("/api/operations/$op/dispute", ['reason' => 'pago_no_recibido', 'description' => 'Dice que pagó pero no me llegó nada.'])->assertOk();
        $id = Dispute::firstOrFail()->id;
        $this->as($this->makeUser('moderador'))
            ->postJson("/api/moderation/disputes/$id/resolve", ['decision' => 'favor_vendedor', 'note' => 'El comprador mostró la entrega firmada.'])
            ->assertOk();

        $this->assertSame('completado', Operation::find($op)->estatus);
        $this->assertSame(1, SellerProfile::find($seller->id)->operaciones_completadas);
    }

    public function test_textos_legales_registro_y_aceptacion(): void
    {
        $this->getJson('/api/legal/privacidad')->assertOk()->assertJsonPath('data.version', config('legal.version'));
        $this->getJson('/api/legal/terminos')->assertOk();
        $this->getJson('/api/legal/otro')->assertNotFound();
        $this->get('/privacidad')->assertOk()->assertSee('Aviso de privacidad', false);

        $this->postJson('/api/register', [
            'name' => 'Ana', 'email' => 'sin-terminos@example.com',
            'password' => 'Secreta123', 'password_confirmation' => 'Secreta123',
        ])->assertUnprocessable()->assertJsonValidationErrors('accept_terms');

        $user = $this->makeUser();
        $this->as($user)->getJson('/api/me')->assertOk()->assertJsonPath('data.must_accept_terms', true);
        $this->postJson('/api/me/accept-terms', ['version' => 'vieja'])->assertStatus(409);
        $this->postJson('/api/me/accept-terms', ['version' => config('legal.version')])->assertOk();
        $this->assertSame(config('legal.version'), $user->fresh()->terminos_version);
    }

    public function test_eliminar_mi_cuenta(): void
    {
        $seller = $this->makeUser('vendedor');
        $buyer = $this->makeUser();
        $this->operation($seller, $buyer);

        // Con una operación sin terminar no se puede.
        $this->as($buyer)->postJson('/api/me/delete', ['password' => 'password'])->assertUnprocessable();

        $loner = $this->makeUser();
        $this->as($loner)->postJson('/api/me/delete', ['password' => 'mala'])->assertUnprocessable();
        $this->postJson('/api/me/delete', ['password' => 'password'])->assertOk();

        $fresh = $loner->fresh();
        $this->assertNotNull($fresh->eliminado_en);
        $this->assertSame('Usuario eliminado', $fresh->nombre);
        $this->assertStringEndsWith('@agrolink.invalid', $fresh->correo);
    }
}
