<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {
            // user_id es la PK: relación 1 a 1 estricta, sin id propio.
            $table->foreignId('usuario_id')->primary()->constrained('usuarios')->cascadeOnDelete();
            $table->string('ruta_avatar')->nullable();
            $table->text('biografia')->nullable();
            $table->string('estado', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
