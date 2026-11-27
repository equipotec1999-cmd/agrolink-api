<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Índices para consultas nuevas (solo lectura más rápida; no cambia datos). Complementa
 * 2026_11_26_000300 (reseñas por evaluado y cuentas suspendidas):
 *  - Cancelaciones del vendedor en su reputación: eventos «cancelado» por quien canceló.
 *  - Moderación: reportes abiertos de una cuenta o publicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE INDEX IF NOT EXISTS eventos_operacion_cancelado_idx ON eventos_operacion (actor_id) WHERE estatus_nuevo = 'cancelado'");
        DB::statement("CREATE INDEX IF NOT EXISTS reportes_abiertos_idx ON reportes (reportable_tipo, reportable_id) WHERE estatus IN ('abierto', 'investigating')");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS eventos_operacion_cancelado_idx');
        DB::statement('DROP INDEX IF EXISTS reportes_abiertos_idx');
    }
};
