<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Disputas: cuando comprador y vendedor no se ponen de acuerdo en una operación
 * (no llegó, llegó distinto, no se recibió el pago...). Mientras está abierta,
 * la operación queda congelada y moderación decide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones')->cascadeOnDelete();
            $table->foreignId('abierta_por_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('motivo', 40);
            $table->text('descripcion');
            // Versión de la otra parte (una sola respuesta).
            $table->text('respuesta')->nullable();
            $table->timestamp('respondida_en')->nullable();
            // Estado de la operación al abrirla (para la línea de tiempo y para moderación).
            $table->string('estatus_operacion', 30);
            $table->string('estatus', 20)->default('abierta'); // abierta | resuelta
            // favor_comprador (se cancela) | favor_vendedor (se completa) | reanudar (sigue el flujo)
            $table->string('decision', 20)->nullable();
            $table->text('nota_resolucion')->nullable();
            $table->foreignId('resuelta_por_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('resuelta_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            // Una disputa por operación (si algo más sale mal, se reporta a la cuenta).
            $table->unique('operacion_id');
        });

        DB::statement("CREATE INDEX disputas_abiertas_idx ON disputas (creado_en) WHERE estatus = 'abierta'");
    }

    public function down(): void
    {
        Schema::dropIfExists('disputas');
    }
};
