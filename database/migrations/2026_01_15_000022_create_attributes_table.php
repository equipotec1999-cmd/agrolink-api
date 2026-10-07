<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atributos', function (Blueprint $table) {
            $table->id();
            // Clave estable usada por Flutter y por listing_attribute_values.attribute_id
            // (p.ej. "raza", "peso"); un mismo atributo se reutiliza en varios tipos de producto.
            $table->string('clave', 100)->unique();
            $table->string('etiqueta', 150);
            $table->enum('tipo_dato', ['text', 'number', 'select', 'boolean', 'date']);
            $table->string('unidad', 30)->nullable();
            $table->enum('grupo', ['general', 'salud', 'reproduccion', 'produccion', 'comercial'])
                ->default('general');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atributos');
    }
};
