<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un token FCM identifica una instalación de la app. Si otra cuenta inicia sesión
        // en el mismo celular, el token se reasigna (el token es único).
        Schema::create('tokens_dispositivo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->text('token')->unique();
            $table->string('plataforma', 20)->default('android');
            $table->timestamp('ultimo_uso_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens_dispositivo');
    }
};
