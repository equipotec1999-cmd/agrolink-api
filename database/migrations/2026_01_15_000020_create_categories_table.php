<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            // Árbol de categorías: permite subcategorías (p.ej. Animales > Bovinos) sin
            // tocar el esquema (Fase 1 §2, "agregar categorías sin modificar la BD").
            $table->foreignId('categoria_padre_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('slug', 100)->unique();
            // Clave de color para la UI (paletFor en Flutter); no es lógica de negocio.
            $table->string('clave_color', 50);
            $table->string('icono', 50)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
