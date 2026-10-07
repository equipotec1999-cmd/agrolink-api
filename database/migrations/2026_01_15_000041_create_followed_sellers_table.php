<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Guardar vendedores" (Fase 1 §10), separado de favoritos de publicaciones.
        Schema::create('vendedores_seguidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamp('creado_en')->nullable();

            $table->unique(['usuario_id', 'vendedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendedores_seguidos');
    }
};
