<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Índices para lo nuevo de noviembre: reputación del vendedor (reseñas por evaluado, más recientes
 * primero) y sanciones de cuentas. IF NOT EXISTS: es seguro correrla dos veces o sobre una BD compartida.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS resenas_evaluado_idx ON resenas (evaluado_id, id DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS usuarios_suspendidos_idx ON usuarios (suspendido_hasta) WHERE suspendido_hasta IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS resenas_evaluado_idx');
        DB::statement('DROP INDEX IF EXISTS usuarios_suspendidos_idx');
    }
};
