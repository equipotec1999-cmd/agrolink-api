<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reputación de 3+ ejes, no una sola estrella (Fase 1 §13).
        // Cada participante de la operación califica una sola vez (unique operation_id+reviewer_id);
        // qué columnas llena depende de si reviewer es comprador o vendedor (se valida en el Service).
        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones')->cascadeOnDelete();
            $table->foreignId('autor_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('evaluado_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unsignedTinyInteger('exactitud')->nullable();
            $table->unsignedTinyInteger('cumplimiento')->nullable();
            $table->unsignedTinyInteger('comunicacion')->nullable();
            $table->unsignedTinyInteger('pago')->nullable();
            $table->unsignedTinyInteger('recepcion')->nullable();
            $table->string('comentario', 500)->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->unique(['operacion_id', 'autor_id']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE resenas ADD CONSTRAINT resenas_calificaciones_rango_chk CHECK (
              (exactitud IS NULL OR exactitud BETWEEN 1 AND 5) AND
              (cumplimiento IS NULL OR cumplimiento BETWEEN 1 AND 5) AND
              (comunicacion IS NULL OR comunicacion BETWEEN 1 AND 5) AND
              (pago IS NULL OR pago BETWEEN 1 AND 5) AND
              (recepcion IS NULL OR recepcion BETWEEN 1 AND 5)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('resenas');
    }
};
