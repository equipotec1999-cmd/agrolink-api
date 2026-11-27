<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Código que una persona genera en SU pantalla para que la dueña de AgroLink la agregue como
 * moderadora. Así nadie se vuelve moderador solo con su correo: hace falta el código de su celular.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitaciones_moderador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->string('codigo_hash');
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->timestamp('expira_en');
            $table->timestamp('creado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitaciones_moderador');
    }
};
