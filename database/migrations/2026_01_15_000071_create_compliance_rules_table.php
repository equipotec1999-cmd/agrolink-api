<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Capa de reglas regulatorias, independiente del código (Fase 1 §22).
        // Se edita desde administración; el backend nunca decide requisitos legales a mano.
        Schema::create('reglas_cumplimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_producto_id')->nullable()->constrained('tipos_producto')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->cascadeOnDelete();
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->boolean('documento_sugerido')->default(false);
            $table->boolean('documento_requerido')->default(false);
            // Siempre citar la fuente oficial (SENASICA, SAT, PROFECO...), nunca inventarla.
            $table->string('nombre_fuente', 120);
            $table->string('url_fuente')->nullable();
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        DB::statement('ALTER TABLE reglas_cumplimiento ADD CONSTRAINT reglas_cumplimiento_alcance_chk CHECK (tipo_producto_id IS NOT NULL OR categoria_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_cumplimiento');
    }
};
