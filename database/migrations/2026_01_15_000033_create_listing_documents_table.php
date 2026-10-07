<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_publicacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->string('nombre', 180);
            $table->string('ruta_almacenamiento');
            $table->enum('estatus', ['pendiente', 'verified', 'rechazada', 'vencida', 'not_applicable'])
                ->default('pendiente');
            $table->string('nota', 255)->nullable();
            // Quién lo revisó: nunca se auto-aprueba un documento subido (Fase 1 §8).
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('publicacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_publicacion');
    }
};
