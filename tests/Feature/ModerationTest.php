<?php

namespace Tests\Feature;

use App\Models\Report;
use Tests\ApiTestCase;

class ModerationTest extends ApiTestCase
{
    public function test_solo_quien_tiene_permiso_ve_la_cola(): void
    {
        $this->as($this->makeUser('vendedor'))->getJson('/api/moderation/listings')->assertForbidden();
        $this->as($this->makeUser('moderador'))->getJson('/api/moderation/listings')->assertOk();
    }

    public function test_cola_incluye_publicadas_sin_revisar_y_reenviadas(): void
    {
        $owner = $this->makeUser('vendedor');
        $sinRevisar = $this->makeListing($owner);
        $reenviada = $this->makeListing($owner, ['estatus' => 'en_revision']);
        $aprobada = $this->makeListing($owner, ['estatus_moderacion' => 'aprobada']);

        $ids = collect($this->as($this->makeUser('moderador'))->getJson('/api/moderation/listings')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($sinRevisar->id));
        $this->assertTrue($ids->contains($reenviada->id));
        $this->assertFalse($ids->contains($aprobada->id));
    }

    public function test_rechazar_oculta_guarda_motivo_y_avisa_al_dueno(): void
    {
        $owner = $this->makeUser('vendedor');
        $listing = $this->makeListing($owner);

        $this->as($this->makeUser('moderador'))
            ->postJson("/api/moderation/listings/{$listing->id}/reject", ['reason' => 'Fotos que no corresponden'])
            ->assertNoContent();

        $listing->refresh();
        $this->assertSame('rechazada', $listing->estatus);
        $this->assertSame('Fotos que no corresponden', $listing->motivo_moderacion);

        $mine = $this->as($owner)->getJson('/api/my/listings')->assertOk();
        $this->assertSame('Fotos que no corresponden', $mine->json('data.0.moderation_note'));
        $this->assertSame(1, $this->getJson('/api/notifications')->json('unread_count'));
        $this->assertDatabaseHas('bitacora_auditoria', ['accion' => 'listing.rejected', 'auditable_id' => $listing->id]);
    }

    public function test_rechazar_exige_motivo(): void
    {
        $listing = $this->makeListing($this->makeUser('vendedor'));

        $this->as($this->makeUser('moderador'))
            ->postJson("/api/moderation/listings/{$listing->id}/reject", [])
            ->assertUnprocessable();
    }

    public function test_el_motivo_solo_lo_ve_el_dueno(): void
    {
        $owner = $this->makeUser('vendedor');
        $listing = $this->makeListing($owner, ['motivo_moderacion' => 'privado']);

        $this->as($this->makeUser())->getJson("/api/listings/{$listing->id}")->assertOk()->assertJsonMissingPath('data.moderation_note');
        $this->as($owner)->getJson("/api/listings/{$listing->id}")->assertOk()->assertJsonPath('data.moderation_note', 'privado');
    }

    public function test_reenviar_solo_rechazadas_o_suspendidas_y_solo_el_dueno(): void
    {
        $owner = $this->makeUser('vendedor');
        $rechazada = $this->makeListing($owner, ['estatus' => 'rechazada', 'estatus_moderacion' => 'rechazada']);
        $publicada = $this->makeListing($owner);

        $this->as($this->makeUser('vendedor'))->postJson("/api/listings/{$rechazada->id}/resubmit")->assertForbidden();

        $this->as($owner)->postJson("/api/listings/{$publicada->id}/resubmit")->assertUnprocessable();
        $this->postJson("/api/listings/{$rechazada->id}/resubmit")->assertOk();
        $this->assertSame('en_revision', $rechazada->fresh()->estatus);
    }

    public function test_aprobar_una_reenviada_la_vuelve_visible_y_limpia_el_motivo(): void
    {
        $listing = $this->makeListing($this->makeUser('vendedor'), [
            'estatus' => 'en_revision', 'estatus_moderacion' => 'pendiente', 'motivo_moderacion' => 'antes mal',
        ]);

        $this->as($this->makeUser('moderador'))->postJson("/api/moderation/listings/{$listing->id}/approve")->assertNoContent();

        $listing->refresh();
        $this->assertSame('publicada', $listing->estatus);
        $this->assertSame('aprobada', $listing->estatus_moderacion);
        $this->assertNull($listing->motivo_moderacion);
    }

    public function test_reportar_valida_motivo_no_permite_el_propio_ni_duplicados(): void
    {
        $owner = $this->makeUser('vendedor');
        $listing = $this->makeListing($owner);
        $reporter = $this->makeUser();

        $this->as($owner)->postJson("/api/listings/{$listing->id}/report", ['reason' => 'fraude'])->assertUnprocessable();

        $this->as($reporter)->postJson("/api/listings/{$listing->id}/report", ['reason' => 'inventado'])->assertUnprocessable();
        $this->postJson("/api/listings/{$listing->id}/report", ['reason' => 'fraude'])->assertCreated();
        $this->postJson("/api/listings/{$listing->id}/report", ['reason' => 'fraude'])->assertUnprocessable();
    }

    public function test_ocultar_por_reporte_suspende_cierra_todos_los_reportes_y_avisa(): void
    {
        $owner = $this->makeUser('vendedor');
        $listing = $this->makeListing($owner);

        foreach ([$this->makeUser(), $this->makeUser()] as $reporter) {
            $this->as($reporter)->postJson("/api/listings/{$listing->id}/report", ['reason' => 'fraude'])->assertCreated();
        }
        $this->assertSame(2, Report::where('estatus', 'abierto')->count());

        $reportId = Report::first()->id;
        $this->as($this->makeUser('moderador'))
            ->postJson("/api/moderation/reports/{$reportId}/resolve", ['action' => 'ocultar_publicacion', 'note' => 'Sin pruebas'])
            ->assertNoContent();

        $this->assertSame('suspended', $listing->fresh()->estatus);
        $this->assertSame(0, Report::where('estatus', 'abierto')->count());
        $this->assertSame(1, $this->as($owner)->getJson('/api/notifications')->json('unread_count'));

        $this->as($this->makeUser('moderador'))
            ->postJson("/api/moderation/reports/{$reportId}/resolve", ['action' => 'descartar'])
            ->assertUnprocessable();
    }

    public function test_desestimar_un_reporte_no_toca_la_publicacion(): void
    {
        $listing = $this->makeListing($this->makeUser('vendedor'));
        $this->as($this->makeUser())->postJson("/api/listings/{$listing->id}/report", ['reason' => 'otro'])->assertCreated();

        $this->as($this->makeUser('moderador'))
            ->postJson('/api/moderation/reports/'.Report::first()->id.'/resolve', ['action' => 'descartar'])
            ->assertNoContent();

        $this->assertSame('publicada', $listing->fresh()->estatus);
    }

    public function test_reportar_usuario_llega_a_la_cola_y_no_se_duplica(): void
    {
        $reportado = $this->makeUser('vendedor');
        $quien = $this->makeUser();

        $this->as($quien)->postJson("/api/users/{$reportado->id}/report", ['reason' => 'fraude'])->assertCreated();
        $this->as($quien)->postJson("/api/users/{$reportado->id}/report", ['reason' => 'fraude'])->assertUnprocessable();
        $this->as($reportado)->postJson("/api/users/{$reportado->id}/report", ['reason' => 'otro'])->assertUnprocessable();

        $rows = $this->as($this->makeUser('moderador'))->getJson('/api/moderation/reports')->assertOk()->json('data');
        $this->assertSame('user', $rows[0]['target_type']);
        $this->assertSame($reportado->id, $rows[0]['user']['id']);
    }
}
