<?php

namespace Tests\Feature;

use App\Models\Listing;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\ApiTestCase;

class DraftsAndMediaTest extends ApiTestCase
{
    public function test_los_borradores_de_mas_de_7_dias_se_borran_solos(): void
    {
        $owner = $this->makeUser();
        $old = $this->makeListing($owner, ['estatus' => 'borrador']);
        $new = $this->makeListing($owner, ['estatus' => 'borrador']);
        $published = $this->makeListing($owner);
        DB::table('publicaciones')->whereIn('id', [$old->id, $published->id])->update(['creado_en' => now()->subDays(8)]);

        $ids = collect($this->as($owner)->getJson('/api/my/listings')->assertOk()->json('data'))->pluck('id');

        $this->assertNotContains($old->id, $ids);
        $this->assertContains($new->id, $ids);
        $this->assertContains($published->id, $ids);
        $this->assertDatabaseMissing('publicaciones', ['id' => $old->id]);
    }

    public function test_el_borrador_dice_cuando_vence(): void
    {
        $owner = $this->makeUser();
        $draft = $this->makeListing($owner, ['estatus' => 'borrador']);

        $r = $this->as($owner)->getJson("/api/listings/{$draft->id}")->assertOk();
        $this->assertNotNull($r->json('data.draft_expires_at'));
        $this->assertNull($this->getJson('/api/listings/'.$this->makeListing($owner)->id)->json('data.draft_expires_at'));
    }

    public function test_fotos_agregar_quitar_y_reordenar(): void
    {
        Storage::fake(config('filesystems.default'));
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner, ['estatus' => 'borrador']);
        $this->as($owner);

        $ids = [];
        foreach (['a', 'b', 'c'] as $n) {
            $ids[] = $this->post("/api/listings/{$listing->id}/media", ['foto' => UploadedFile::fake()->image("{$n}.jpg")], ['Accept' => 'application/json'])
                ->assertCreated()->json('data.id');
        }

        // La tercera pasa a portada.
        $this->postJson("/api/listings/{$listing->id}/media/order", ['ids' => [$ids[2], $ids[0], $ids[1]]])
            ->assertOk()->assertJsonPath('data.0.id', $ids[2]);
        // Una lista que no coincide se rechaza.
        $this->postJson("/api/listings/{$listing->id}/media/order", ['ids' => [$ids[0]]])->assertStatus(422);

        $this->deleteJson("/api/listings/{$listing->id}/media/{$ids[2]}")->assertOk();
        $this->assertSame([0, 1], Listing::find($listing->id)->media()->pluck('posicion')->map(fn ($p) => (int) $p)->all());
    }

    public function test_foto_nueva_en_publicada_la_manda_a_revision(): void
    {
        Storage::fake(config('filesystems.default'));
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->as($owner)->post("/api/listings/{$listing->id}/media", ['foto' => UploadedFile::fake()->image('n.jpg')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('sent_to_review', true);
        $this->assertSame('en_revision', $listing->fresh()->estatus);
    }
}
