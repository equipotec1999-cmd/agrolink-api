<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medios_publicacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->enum('tipo', ['photo', 'video'])->default('photo');
            // Solo la clave del archivo en S3/R2; la BD nunca guarda binarios (Fase 1 §16).
            $table->string('ruta_almacenamiento');
            $table->unsignedSmallInteger('posicion')->default(0);
            $table->unsignedSmallInteger('ancho')->nullable();
            $table->unsignedSmallInteger('alto')->nullable();
            $table->unsignedSmallInteger('duracion_segundos')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index(['publicacion_id', 'posicion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medios_publicacion');
    }
};
