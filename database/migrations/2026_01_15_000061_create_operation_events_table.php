<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historial append-only de cambios de estado de una operación (auditoría de negocio).
        Schema::create('eventos_operacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones')->cascadeOnDelete();
            $table->string('estatus_anterior', 30)->nullable();
            $table->string('estatus_nuevo', 30);
            $table->foreignId('actor_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nota', 255)->nullable();
            $table->timestamp('creado_en')->nullable();

            $table->index('operacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_operacion');
    }
};
