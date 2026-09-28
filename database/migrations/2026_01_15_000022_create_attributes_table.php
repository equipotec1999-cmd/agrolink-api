<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            // Clave estable usada por Flutter y por listing_attribute_values.attribute_id
            // (p.ej. "raza", "peso"); un mismo atributo se reutiliza en varios tipos de producto.
            $table->string('attr_key', 100)->unique();
            $table->string('label', 150);
            $table->enum('data_type', ['text', 'number', 'select', 'boolean', 'date']);
            $table->string('unit', 30)->nullable();
            $table->enum('attr_group', ['general', 'salud', 'reproduccion', 'produccion', 'comercial'])
                ->default('general');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
