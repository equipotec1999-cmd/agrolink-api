<?php

namespace Tests\Feature;

use App\Services\ConstelacionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\ApiTestCase;

class ConstelacionTest extends ApiTestCase
{
    private function conUbicacion(int $publicacionId, string $estado = 'Yucatán', string $municipio = 'Tizimín'): void
    {
        DB::statement(
            "INSERT INTO ubicaciones_publicacion (publicacion_id, estado, municipio, ubicacion_exacta, ubicacion_aproximada, creado_en)
             VALUES (?, ?, ?, ST_GeogFromText('POINT(-88.15 21.14)'), ST_GeogFromText('POINT(-88.15 21.14)'), now())",
            [$publicacionId, $estado, $municipio]
        );
    }

    private function cargar(): array
    {
        return app(ConstelacionService::class)->cargar();
    }

    public function test_carga_dimensiones_y_hecho_de_publicaciones(): void
    {
        $vendedor = $this->makeUser();
        $lista = $this->makeListing($vendedor);
        $this->conUbicacion($lista->id);

        $conteos = $this->cargar();

        $this->assertSame(1, $conteos['hecho_publicaciones']);
        $this->assertGreaterThan(0, $conteos['dim_producto']);
        $this->assertGreaterThan(30, $conteos['dim_tiempo']);

        $fila = DB::table('hecho_publicaciones as h')
            ->join('dim_ubicacion as u', 'u.ubicacion_sk', '=', 'h.ubicacion_sk')
            ->join('dim_estatus as e', 'e.estatus_sk', '=', 'h.estatus_sk')
            ->where('h.publicacion_id', $lista->id)
            ->first();

        $this->assertSame('Yucatán', $fila->estado);
        $this->assertSame('Tizimín', $fila->municipio);
        $this->assertSame('publicada', $fila->codigo);
        $this->assertFalse((bool) $fila->eliminada);
    }

    public function test_publicacion_sin_ubicacion_cae_en_sin_definir(): void
    {
        $lista = $this->makeListing($this->makeUser());
        $this->cargar();

        $estado = DB::table('hecho_publicaciones as h')
            ->join('dim_ubicacion as u', 'u.ubicacion_sk', '=', 'h.ubicacion_sk')
            ->where('h.publicacion_id', $lista->id)
            ->value('u.estado');

        $this->assertSame('Sin definir', $estado);
    }

    public function test_la_carga_es_idempotente_y_conserva_las_claves_sustitutas(): void
    {
        $lista = $this->makeListing($this->makeUser());
        $this->conUbicacion($lista->id);

        $primera = $this->cargar();
        $skAntes = DB::table('dim_producto')->orderBy('tipo_producto_id')->pluck('producto_sk', 'tipo_producto_id')->all();

        $segunda = $this->cargar();
        $skDespues = DB::table('dim_producto')->orderBy('tipo_producto_id')->pluck('producto_sk', 'tipo_producto_id')->all();

        $this->assertSame($primera, $segunda);
        $this->assertSame($skAntes, $skDespues);
    }

    public function test_precio_unitario_segun_tipo_de_precio(): void
    {
        $dueno = $this->makeUser();
        $porAnimal = $this->makeListing($dueno, ['precio' => 1500, 'tipo_precio' => 'per_animal', 'cantidad' => 10]);
        $lote = $this->makeListing($dueno, ['precio' => 1000, 'tipo_precio' => 'fixed', 'cantidad' => 10]);

        $this->cargar();

        $this->assertEquals(1500.00, DB::table('hecho_publicaciones')->where('publicacion_id', $porAnimal->id)->value('precio_unitario'));
        $this->assertEquals(100.00, DB::table('hecho_publicaciones')->where('publicacion_id', $lote->id)->value('precio_unitario'));
    }

    public function test_hecho_de_ofertas_calcula_la_diferencia_contra_el_precio_de_lista(): void
    {
        $vendedor = $this->makeUser();
        $comprador = $this->makeUser();
        $lista = $this->makeListing($vendedor, ['precio' => 1500, 'tipo_precio' => 'per_animal', 'cantidad' => 10]);

        $convId = DB::table('conversaciones')->insertGetId([
            'publicacion_id' => $lista->id, 'comprador_id' => $comprador->id, 'vendedor_id' => $vendedor->id,
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        // 10 cabezas a 12 000 en total = 1 200 c/u, un 20 % por debajo de los 1 500 de lista.
        $ofertaId = DB::table('ofertas')->insertGetId([
            'conversacion_id' => $convId, 'remitente_id' => $comprador->id,
            'monto' => 12000, 'cantidad' => 10, 'estatus' => 'enviada',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $this->cargar();

        $this->assertEquals(-20.00, DB::table('hecho_ofertas')->where('oferta_id', $ofertaId)->value('diferencia_pct_vs_lista'));
        $this->assertSame(1, (int) DB::table('hecho_publicaciones')->where('publicacion_id', $lista->id)->value('num_ofertas'));
    }

    public function test_hechos_comparten_las_mismas_dimensiones(): void
    {
        $vendedor = $this->makeUser();
        $comprador = $this->makeUser();
        $lista = $this->makeListing($vendedor);

        $convId = DB::table('conversaciones')->insertGetId([
            'publicacion_id' => $lista->id, 'comprador_id' => $comprador->id, 'vendedor_id' => $vendedor->id,
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $ofertaId = DB::table('ofertas')->insertGetId([
            'conversacion_id' => $convId, 'remitente_id' => $comprador->id,
            'monto' => 15000, 'cantidad' => 10, 'estatus' => 'aceptada',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);
        $opId = DB::table('operaciones')->insertGetId([
            'oferta_id' => $ofertaId, 'publicacion_id' => $lista->id,
            'comprador_id' => $comprador->id, 'vendedor_id' => $vendedor->id,
            'monto' => 15000, 'cantidad' => 10, 'estatus' => 'oferta_aceptada',
            'creado_en' => now(), 'actualizado_en' => now(),
        ]);

        $this->cargar();

        // Constelación: hechos distintos apuntando al MISMO registro de dimensión de producto.
        $skPub = DB::table('hecho_publicaciones')->where('publicacion_id', $lista->id)->value('producto_sk');
        $skOfe = DB::table('hecho_ofertas')->where('oferta_id', $ofertaId)->value('producto_sk');
        $skOp = DB::table('hecho_operaciones')->where('operacion_id', $opId)->value('producto_sk');

        $this->assertSame($skPub, $skOfe);
        $this->assertSame($skPub, $skOp);
        $this->assertEquals(1500.00, DB::table('hecho_operaciones')->where('operacion_id', $opId)->value('precio_unitario'));
    }

    public function test_hecho_de_reportes_mide_horas_a_resolver(): void
    {
        $vendedor = $this->makeUser();
        $reportante = $this->makeUser();
        $lista = $this->makeListing($vendedor);

        $id = DB::table('reportes')->insertGetId([
            'reportante_id' => $reportante->id, 'reportable_tipo' => 'listing', 'reportable_id' => $lista->id,
            'motivo' => 'fraude', 'estatus' => 'resuelto',
            'creado_en' => now()->subHours(5), 'resuelto_en' => now(), 'actualizado_en' => now(),
        ]);

        $this->cargar();

        $this->assertEquals(5, DB::table('hecho_reportes')->where('reporte_id', $id)->value('horas_a_resolver'));
    }

    public function test_refrescar_si_hace_falta_respeta_el_intervalo(): void
    {
        $servicio = app(ConstelacionService::class);
        $dueno = $this->makeUser();

        $this->makeListing($dueno);
        $servicio->refrescarSiHaceFalta(30);
        $this->assertSame(1, DB::table('hecho_publicaciones')->count());

        // Dentro de los 30 min no vuelve a cargar, aunque haya datos nuevos.
        $this->makeListing($dueno);
        $servicio->refrescarSiHaceFalta(30);
        $this->assertSame(1, DB::table('hecho_publicaciones')->count());

        // Pasado el intervalo sí recarga.
        $this->travel(31)->minutes();
        $servicio->refrescarSiHaceFalta(30);
        $this->assertSame(2, DB::table('hecho_publicaciones')->count());
    }

    public function test_las_dimensiones_no_guardan_datos_personales(): void
    {
        foreach (['nombre', 'correo', 'telefono', 'contrasena'] as $columna) {
            $this->assertFalse(Schema::hasColumn('dim_usuario', $columna), "dim_usuario no debe tener {$columna}");
        }
    }

    public function test_endpoint_de_precios_agrega_por_tipo_y_estado(): void
    {
        $dueno = $this->makeUser();
        $a = $this->makeListing($dueno, ['precio' => 1000, 'tipo_precio' => 'per_animal']);
        $b = $this->makeListing($dueno, ['precio' => 2000, 'tipo_precio' => 'per_animal']);
        $this->conUbicacion($a->id);
        $this->conUbicacion($b->id);

        $r = $this->getJson('/api/market/prices?state=Yucatán')->assertOk();

        $r->assertJsonPath('data.0.state', 'Yucatán')
            ->assertJsonPath('data.0.count', 2)
            ->assertJsonPath('data.0.min_unit_price', 1000)
            ->assertJsonPath('data.0.max_unit_price', 2000);
        $this->assertEquals(1500.0, $r->json('data.0.avg_unit_price'));
    }

    public function test_endpoint_de_resumen_cuenta_solo_publicaciones_activas(): void
    {
        $dueno = $this->makeUser();
        $this->makeListing($dueno);
        $this->makeListing($dueno, ['estatus' => 'borrador', 'publicado_en' => null]);

        $this->getJson('/api/market/summary')
            ->assertOk()
            ->assertJsonPath('data.listings.active', 1)
            ->assertJsonPath('data.listings.total', 2);
    }

    public function test_los_endpoints_de_mercado_son_publicos_y_no_exponen_usuarios(): void
    {
        $this->makeListing($this->makeUser());

        $json = $this->getJson('/api/market/prices')->assertOk()->getContent();

        $this->assertStringNotContainsString('usuario', $json);
        $this->assertStringNotContainsString('correo', $json);
    }
}
