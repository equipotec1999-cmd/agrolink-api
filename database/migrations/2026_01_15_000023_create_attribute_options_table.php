<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opciones de un atributo `select` (p.ej. raza: Cuarto de Milla, Azteca...).
        Schema::create('opciones_atributo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atributo_id')->constrained('atributos')->cascadeOnDelete();
            $table->string('valor', 150);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->unique(['atributo_id', 'valor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones_atributo');
    }
};
