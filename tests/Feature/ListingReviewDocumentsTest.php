<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ApiTestCase;

class ListingReviewDocumentsTest extends ApiTestCase
{
    public function test_una_archivada_se_reactiva_y_pasa_a_revision(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);
        $this->as($owner)->postJson("/api/listings/{$listing->id}/archive")->assertOk()
            ->assertJsonPath('data.status', 'archivada');

        $this->postJson("/api/listings/{$listing->id}/resubmit")->assertOk()
            ->assertJsonPath('data.status', 'en_revision');

        // Y aparece en la cola de moderación.
        $this->as($this->makeUser('moderador', twoFactor: true))
            ->getJson('/api/moderation/listings')->assertOk()
            ->assertJsonFragment(['id' => $listing->id]);
    }

    public function test_una_publicada_no_se_reactiva_ni_la_reactiva_otro(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);
        $this->as($owner)->postJson("/api/listings/{$listing->id}/resubmit")->assertStatus(422);

        $listing->update(['estatus' => 'archivada']);
        $this->as($this->makeUser())->postJson("/api/listings/{$listing->id}/resubmit")->assertForbidden();
    }

    public function test_cambiar_titulo_o_descripcion_manda_a_revision(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->as($owner)->patchJson("/api/listings/{$listing->id}", ['title' => 'Borregos pelibuey'])
            ->assertOk()
            ->assertJsonPath('data.status', 'en_revision')
            ->assertJsonPath('sent_to_review', true);

        $listing->update(['estatus' => 'publicada', 'estatus_moderacion' => 'aprobada']);
        $this->patchJson("/api/listings/{$listing->id}", ['description' => 'Ahora con vacunas al día'])
            ->assertOk()->assertJsonPath('data.status', 'en_revision');
    }

    public function test_cambiar_precio_o_mandar_lo_mismo_no_manda_a_revision(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->as($owner)->patchJson("/api/listings/{$listing->id}", [
            'title' => 'Borregos de engorda',   // igual que antes
            'description' => ' Lote de prueba ', // igual, con espacios
            'price' => 1800,
            'negotiable' => false,
        ])->assertOk()
            ->assertJsonPath('data.status', 'publicada')
            ->assertJsonPath('sent_to_review', false);
    }

    public function test_subir_abrir_y_borrar_documento(): void
    {
        Storage::fake(config('filesystems.default'));
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner, ['estatus' => 'borrador']);

        $doc = $this->as($owner)->post("/api/listings/{$listing->id}/documents", [
            'document' => UploadedFile::fake()->create('guia-transito.pdf', 200, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'guia-transito.pdf')
            ->assertJsonPath('data.status', 'pendiente')
            ->json('data');

        $this->assertNotNull($doc['url']);
        $this->get(parse_url($doc['url'], PHP_URL_PATH).'?'.parse_url($doc['url'], PHP_URL_QUERY))->assertOk();
        // Sin firma no se abre.
        $this->get("/api/listing-documents/{$doc['id']}/file")->assertForbidden();

        // El dueño lo ve en su publicación con enlace; otra persona sin enlace.
        $this->getJson("/api/listings/{$listing->id}")->assertJsonPath('data.documents.0.name', 'guia-transito.pdf');

        $this->deleteJson("/api/listings/{$listing->id}/documents/{$doc['id']}")->assertOk();
        $this->assertDatabaseMissing('documentos_publicacion', ['id' => $doc['id']]);
    }

    public function test_documento_valida_tipo_dueno_y_limite(): void
    {
        Storage::fake(config('filesystems.default'));
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->as($owner)->post("/api/listings/{$listing->id}/documents", [
            'document' => UploadedFile::fake()->create('virus.exe', 10),
        ], ['Accept' => 'application/json'])->assertUnprocessable();

        for ($i = 0; $i < 5; $i++) {
            $this->post("/api/listings/{$listing->id}/documents", [
                'document' => UploadedFile::fake()->image("foto{$i}.jpg"),
            ], ['Accept' => 'application/json'])->assertCreated();
        }
        $this->post("/api/listings/{$listing->id}/documents", [
            'document' => UploadedFile::fake()->image('sexta.jpg'),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->as($this->makeUser())->post("/api/listings/{$listing->id}/documents", [
            'document' => UploadedFile::fake()->image('ajeno.jpg'),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_aprobar_la_publicacion_verifica_sus_documentos_pendientes(): void
    {
        Storage::fake(config('filesystems.default'));
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner, ['estatus' => 'en_revision']);
        $this->as($owner)->post("/api/listings/{$listing->id}/documents", [
            'document' => UploadedFile::fake()->image('certificado.png'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $mod = $this->makeUser('moderador', twoFactor: true);
        $this->as($mod)->getJson('/api/moderation/listings')->assertOk()
            ->assertJsonPath('data.0.documents.0.name', 'certificado.png');
        $this->postJson("/api/moderation/listings/{$listing->id}/approve")->assertNoContent();

        $this->assertDatabaseHas('documentos_publicacion', ['publicacion_id' => $listing->id, 'estatus' => 'verified', 'revisado_por' => $mod->id]);
    }
}
