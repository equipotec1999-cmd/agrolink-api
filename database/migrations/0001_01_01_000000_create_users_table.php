<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo')->unique();
            $table->string('telefono', 20)->nullable();
            $table->timestamp('correo_verificado_en')->nullable();
            $table->string('contrasena');
            // 2FA obligatorio para permisos administrativos (requisito de seguridad, Fase 1 §21).
            $table->text('secreto_dos_factores')->nullable();
            $table->text('codigos_recuperacion_dos_factores')->nullable();
            $table->timestamp('dos_factores_confirmado_en')->nullable();
            $table->string('token_recordar', 100)->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            // Soft delete: un usuario dado de baja no borra sus publicaciones/operaciones históricas.
            $table->softDeletes('eliminado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
