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
        Schema::create('product_type_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            // Rango sugerido para atributos numéricos (p.ej. peso 0-900 kg en bovinos);
            // el frontend lo usa para validar antes de enviar.
            $table->decimal('min_value', 12, 2)->nullable();
            $table->decimal('max_value', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['product_type_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_type_attributes');
    }
};
