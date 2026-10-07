<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bitácora de solo inserción (Fase 1 §21): quién hizo qué, sobre qué modelo.
        Schema::create('bitacora_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('accion', 100);
            $table->string('auditable_tipo', 60)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->jsonb('cambios')->nullable();
            $table->string('direccion_ip', 45)->nullable();
            $table->string('agente_usuario')->nullable();
            $table->timestamp('creado_en')->nullable();

            $table->index(['auditable_tipo', 'auditable_id']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_auditoria');
    }
};
