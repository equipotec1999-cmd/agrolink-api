<?php

namespace Tests\Feature;

use App\Models\SavedSearch;
use Tests\ApiTestCase;

class SavedSearchFavoriteTest extends ApiTestCase
{
    private function filters(): array
    {
        return ['query' => 'borregos', 'category_id' => 'animales', 'negotiable_only' => true, 'sort' => 'newest'];
    }

    public function test_guardar_listar_y_borrar_busquedas(): void
    {
        $user = $this->makeUser();

        $id = $this->as($user)->postJson('/api/saved-searches', ['name' => 'Borregos', 'filters' => $this->filters()])
            ->assertCreated()->json('data.id');

        $list = $this->getJson('/api/saved-searches')->assertOk();
        $this->assertSame('borregos', $list->json('data.0.filters.query'));

        $this->deleteJson("/api/saved-searches/$id")->assertNoContent();
        $this->assertSame(0, SavedSearch::count());
    }

    public function test_las_busquedas_son_privadas(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $id = $this->as($owner)->postJson('/api/saved-searches', ['name' => 'Mía', 'filters' => $this->filters()])->json('data.id');

        $this->as($other)->getJson('/api/saved-searches')->assertJsonCount(0, 'data');
        $this->deleteJson("/api/saved-searches/$id")->assertNotFound();
        $this->assertSame(1, SavedSearch::count());
    }

    public function test_limite_de_veinte_busquedas(): void
    {
        $user = $this->makeUser();
        $this->as($user);

        for ($i = 1; $i <= 20; $i++) {
            $this->postJson('/api/saved-searches', ['name' => "B$i", 'filters' => $this->filters()])->assertCreated();
        }
        $this->postJson('/api/saved-searches', ['name' => 'B21', 'filters' => $this->filters()])->assertUnprocessable();
    }

    public function test_exige_nombre_y_filtros(): void
    {
        $this->as($this->makeUser())->postJson('/api/saved-searches', ['filters' => $this->filters()])->assertUnprocessable();
        $this->postJson('/api/saved-searches', ['name' => 'Sin filtros'])->assertUnprocessable();
    }

    public function test_favoritos_agregar_y_quitar_es_idempotente(): void
    {
        $user = $this->makeUser();
        $listing = $this->makeListing($this->makeUser('vendedor'));

        $this->as($user)->putJson("/api/favorites/{$listing->id}")->assertSuccessful();
        $this->putJson("/api/favorites/{$listing->id}")->assertSuccessful();
        $this->assertContains($listing->id, $this->getJson('/api/favorites/ids')->json('data'));

        $this->deleteJson("/api/favorites/{$listing->id}")->assertSuccessful();
        $this->deleteJson("/api/favorites/{$listing->id}")->assertSuccessful();
        $this->assertNotContains($listing->id, $this->getJson('/api/favorites/ids')->json('data'));
    }
}
