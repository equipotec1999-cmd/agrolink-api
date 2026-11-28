<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Services\WeeklyPicksService;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class WeeklyPicksTest extends ApiTestCase
{
    private function favorite(Listing $listing): void
    {
        DB::table('favoritos')->insert([
            'usuario_id' => $this->makeUser()->id,
            'publicacion_id' => $listing->id,
            'creado_en' => now(),
        ]);
    }

    private function report(User $user): void
    {
        DB::table('reportes')->insert([
            'reportante_id' => $this->makeUser()->id,
            'reportable_tipo' => 'user',
            'reportable_id' => $user->id,
            'motivo' => 'fraude',
            'estatus' => 'abierto',
            'creado_en' => now(),
        ]);
    }

    public function test_pagados_primero_y_gratis_solo_si_cumplen_requisitos(): void
    {
        $buena = $this->makeListing($this->makeUser('vendedor'));
        $this->favorite($buena);

        $reportado = $this->makeUser('vendedor');
        $conReporte = $this->makeListing($reportado);
        $this->favorite($conReporte);
        $this->report($reportado);

        $sinActividad = $this->makeListing($this->makeUser('vendedor'));

        $advertido = $this->makeUser('vendedor');
        $advertido->forceFill(['advertencias' => 1])->save();
        $conAdvertencia = $this->makeListing($advertido);
        $this->favorite($conAdvertencia);

        $pagada = $this->makeListing($this->makeUser('vendedor'));
        $pagada->forceFill(['destacada_hasta' => now()->addDays(2)])->save();

        $res = $this->getJson('/api/listings/weekly')->assertOk();
        $items = collect($res->json('data'));
        $this->assertSame($pagada->id, $items->first()['id']);
        $this->assertSame('paid', $items->first()['weekly_kind']);
        $merit = $items->where('weekly_kind', 'merit')->pluck('id')->all();
        $this->assertSame([$buena->id], $merit);
        $this->assertNotContains($conReporte->id, $merit);
        $this->assertNotContains($sinActividad->id, $merit);
        $this->assertNotContains($conAdvertencia->id, $merit);
        $this->assertNotNull($res->json('rotates_at'));
    }

    public function test_solo_4_gratis_uno_por_vendedor_y_rotan_cada_2_dias(): void
    {
        $ids = [];
        for ($i = 0; $i < 6; $i++) {
            $seller = $this->makeUser('vendedor');
            $l = $this->makeListing($seller);
            $this->favorite($l);
            $ids[] = $l->id;
            // Segunda publicación del mismo vendedor: no entra dos veces.
            $otra = $this->makeListing($seller);
            $this->favorite($otra);
        }

        $pool = WeeklyPicksService::candidates();
        $this->assertCount(6, $pool);

        $now = WeeklyPicksService::pick(10);
        $next = WeeklyPicksService::pick(11);
        $this->assertCount(4, $now);
        $this->assertCount(4, $next);
        $this->assertNotEquals($now, $next);

        $merit = collect($this->getJson('/api/listings/weekly')->assertOk()->json('data'))->where('weekly_kind', 'merit');
        $this->assertCount(4, $merit);
        $this->assertCount(4, $merit->map(fn ($l) => $l['seller']['id'])->unique());
    }
}
