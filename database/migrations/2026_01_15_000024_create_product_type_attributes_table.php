<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla puente: qué atributos aplican a cada tipo de producto, y con qué reglas.
        // Es el corazón del catálogo dinámico (Fase 1 §3).
        Schema::create('tipo_producto_atributos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_producto_id')->constrained('tipos_producto')->cascadeOnDelete();
            $table->foreignId('atributo_id')->constrained('atributos')->cascadeOnDelete();
            $table->boolean('es_obligatorio')->default(false);
            $table->boolean('es_filtrable')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            // Rango sugerido para atributos numéricos (p.ej. peso 0-900 kg en bovinos);
            // el frontend lo usa para validar antes de enviar.
            $table->decimal('valor_minimo', 12, 2)->nullable();
            $table->decimal('valor_maximo', 12, 2)->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->unique(['tipo_producto_id', 'atributo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_producto_atributos');
    }
};
