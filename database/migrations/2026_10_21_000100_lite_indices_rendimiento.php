<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Versión Lite: índices que faltaban para las consultas más frecuentes.
 * IF NOT EXISTS en todos para poder correrla sobre una base ya existente sin riesgo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Feed público: solo filas visibles, ordenadas por fecha. Índice parcial = más chico y rápido
        // que el compuesto general (que además arrastra borradores, rechazadas, vencidas...).
        DB::statement(
            "CREATE INDEX IF NOT EXISTS publicaciones_feed_idx ON publicaciones (publicado_en DESC, id DESC)
             WHERE estatus = 'publicada' AND eliminado_en IS NULL"
        );
        // Filtro por tipo dentro del feed.
        DB::statement(
            "CREATE INDEX IF NOT EXISTS publicaciones_feed_tipo_idx ON publicaciones (tipo_producto_id, publicado_en DESC)
             WHERE estatus = 'publicada' AND eliminado_en IS NULL"
        );
        // "Mis publicaciones" y panel del vendedor.
        DB::statement('CREATE INDEX IF NOT EXISTS publicaciones_usuario_idx ON publicaciones (usuario_id, id DESC)');
        // Bandeja de moderación (cola de revisión).
        DB::statement(
            "CREATE INDEX IF NOT EXISTS publicaciones_revision_idx ON publicaciones (creado_en)
             WHERE estatus = 'en_revision'"
        );
        // Bandeja de entrada del comprador (la del vendedor ya existe).
        DB::statement('CREATE INDEX IF NOT EXISTS conversaciones_comprador_idx ON conversaciones (comprador_id, ultimo_mensaje_en DESC)');
        // Ofertas activas por estado y operaciones por participante.
        DB::statement('CREATE INDEX IF NOT EXISTS ofertas_estatus_idx ON ofertas (estatus, vence_en)');
        DB::statement('CREATE INDEX IF NOT EXISTS operaciones_comprador_idx ON operaciones (comprador_id, creado_en DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS operaciones_vendedor_idx ON operaciones (vendedor_id, creado_en DESC)');
        // Favoritos: listado del usuario, más recientes primero.
        DB::statement('CREATE INDEX IF NOT EXISTS favoritos_usuario_idx ON favoritos (usuario_id, creado_en DESC)');
        // Cuántos favoritos tiene una publicación (contador de la ficha).
        DB::statement('CREATE INDEX IF NOT EXISTS favoritos_publicacion_idx ON favoritos (publicacion_id)');
    }

    public function down(): void
    {
        foreach ([
            'publicaciones_feed_idx', 'publicaciones_feed_tipo_idx', 'publicaciones_usuario_idx',
            'publicaciones_revision_idx', 'conversaciones_comprador_idx', 'ofertas_estatus_idx',
            'operaciones_comprador_idx', 'operaciones_vendedor_idx', 'favoritos_usuario_idx',
            'favoritos_publicacion_idx',
        ] as $indice) {
            DB::statement("DROP INDEX IF EXISTS {$indice}");
        }
    }
};
