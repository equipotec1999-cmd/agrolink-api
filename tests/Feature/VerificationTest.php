<?php

namespace Tests\Feature;

use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ApiTestCase;

class VerificationTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('filesystems.default'));
    }

    private function files(array $over = []): array
    {
        return $over + [
            'business_name' => 'Rancho Los Pinos',
            'id_document' => UploadedFile::fake()->image('ine.jpg', 600, 400),
            'activity_document' => UploadedFile::fake()->image('upp.png', 600, 400),
        ];
    }

    private function submit($user, array $over = [])
    {
        return $this->as($user)->post('/api/verification', $this->files($over), ['Accept' => 'application/json']);
    }

    public function test_solicitar_guarda_documentos_y_queda_pendiente(): void
    {
        $user = $this->makeUser();

        $this->submit($user)->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseCount('documentos_verificacion', 2);
        $this->getJson('/api/verification')->assertOk()
            ->assertJsonPath('data.is_verified', false)
            ->assertJsonPath('data.request.status', 'pending');

        foreach (\App\Models\VerificationDocument::all() as $doc) {
            Storage::disk(config('filesystems.default'))->assertExists($doc->ruta_almacenamiento);
        }
    }

    public function test_exige_identificacion_y_comprobante_y_solo_imagenes(): void
    {
        $user = $this->makeUser();

        $this->submit($user, ['id_document' => null])->assertUnprocessable()->assertJsonValidationErrors('id_document');
        $this->submit($user, ['activity_document' => null])->assertUnprocessable()->assertJsonValidationErrors('activity_document');
        $this->submit($user, ['id_document' => UploadedFile::fake()->create('ine.pdf', 100, 'application/pdf')])
            ->assertUnprocessable()->assertJsonValidationErrors('id_document');
        $this->assertDatabaseCount('solicitudes_verificacion', 0);
    }

    public function test_una_sola_solicitud_pendiente(): void
    {
        $user = $this->makeUser();

        $this->submit($user)->assertCreated();
        $this->submit($user)->assertUnprocessable();
        $this->assertDatabaseCount('solicitudes_verificacion', 1);
    }

    public function test_solo_moderacion_ve_la_cola_y_los_documentos(): void
    {
        $user = $this->makeUser();
        $this->submit($user)->assertCreated();
        $req = VerificationRequest::firstOrFail();
        $doc = $req->documents()->firstOrFail();

        $this->as($this->makeUser('vendedor'))->getJson('/api/moderation/verifications')->assertForbidden();
        $this->as($user)->getJson("/api/moderation/verifications/{$req->id}/documents/{$doc->id}")->assertForbidden();

        $mod = $this->makeUser('moderador');
        $this->as($mod)->getJson('/api/moderation/verifications')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonCount(2, 'data.0.documents');

        $this->get("/api/moderation/verifications/{$req->id}/documents/{$doc->id}")->assertOk();
        $this->assertDatabaseHas('bitacora_auditoria', ['usuario_id' => $mod->id, 'accion' => 'verification.document_viewed']);
    }

    public function test_aprobar_activa_la_insignia_avisa_y_no_se_repite(): void
    {
        $user = $this->makeUser();
        $this->submit($user)->assertCreated();
        $req = VerificationRequest::firstOrFail();

        $this->as($this->makeUser('moderador'))->postJson("/api/moderation/verifications/{$req->id}/approve")->assertNoContent();

        $this->assertDatabaseHas('perfiles_vendedor', ['usuario_id' => $user->id, 'verificado' => true, 'nombre_negocio' => 'Rancho Los Pinos']);
        $this->as($user)->getJson('/api/me')->assertJsonPath('data.seller_verified', true);
        $this->getJson('/api/verification')->assertJsonPath('data.is_verified', true);
        $this->assertSame(1, $this->getJson('/api/notifications')->json('unread_count'));

        $this->as($this->makeUser('moderador'))->postJson("/api/moderation/verifications/{$req->id}/approve")->assertUnprocessable();

        // Un vendedor ya verificado no vuelve a pedirla.
        $this->submit($user)->assertUnprocessable();
    }

    public function test_la_insignia_se_ve_en_sus_publicaciones(): void
    {
        $user = $this->makeUser('vendedor');
        $listing = $this->makeListing($user);
        $this->submit($user)->assertCreated();

        $this->as($this->makeUser('moderador'))
            ->postJson('/api/moderation/verifications/'.VerificationRequest::firstOrFail()->id.'/approve')->assertNoContent();

        $this->getJson("/api/listings/{$listing->id}")->assertJsonPath('data.seller.is_verified', true);
    }

    public function test_rechazar_exige_motivo_lo_muestra_y_permite_reenviar(): void
    {
        $user = $this->makeUser();
        $this->submit($user)->assertCreated();
        $req = VerificationRequest::firstOrFail();
        $mod = $this->makeUser('moderador');

        $this->as($mod)->postJson("/api/moderation/verifications/{$req->id}/reject", [])->assertUnprocessable();
        $this->postJson("/api/moderation/verifications/{$req->id}/reject", ['reason' => 'INE ilegible'])->assertNoContent();

        $this->as($user)->getJson('/api/verification')
            ->assertJsonPath('data.request.status', 'rejected')
            ->assertJsonPath('data.request.rejection_reason', 'INE ilegible')
            ->assertJsonPath('data.is_verified', false);

        $this->submit($user)->assertCreated();
        $this->assertDatabaseCount('solicitudes_verificacion', 2);
    }
}
