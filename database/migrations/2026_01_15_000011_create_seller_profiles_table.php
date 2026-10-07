<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_vendedor', function (Blueprint $table) {
            $table->foreignId('usuario_id')->primary()->constrained('usuarios')->cascadeOnDelete();
            $table->string('nombre_negocio')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamp('verificado_en')->nullable();
            // Métricas de reputación cacheadas: se recalculan con cada review/operación
            // (evita agregar sobre todo el historial en cada lectura del perfil).
            $table->unsignedInteger('operaciones_completadas')->default(0);
            $table->unsignedInteger('operaciones_canceladas')->default(0);
            $table->decimal('calificacion_exactitud', 3, 2)->nullable();
            $table->decimal('calificacion_cumplimiento', 3, 2)->nullable();
            $table->decimal('calificacion_comunicacion', 3, 2)->nullable();
            $table->unsignedInteger('minutos_respuesta_promedio')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_vendedor');
    }
};
