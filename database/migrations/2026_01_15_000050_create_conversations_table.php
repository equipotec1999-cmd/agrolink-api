<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Toda conversación pertenece a UNA publicación (Fase 1 §11, requisito explícito).
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->foreignId('comprador_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamp('ultimo_mensaje_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            // Un mismo comprador no abre dos conversaciones para la misma publicación.
            $table->unique(['publicacion_id', 'comprador_id']);
            $table->index('vendedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversaciones');
    }
};
