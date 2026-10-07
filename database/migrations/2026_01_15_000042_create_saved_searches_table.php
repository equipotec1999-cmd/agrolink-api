<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('busquedas_guardadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('nombre', 150);
            // Filtros serializados tal como los arma SearchFilters en Flutter (mismo shape).
            $table->jsonb('consulta')->default('{}');
            $table->boolean('avisar_coincidencia')->default(true);
            $table->boolean('avisar_cambio_precio')->default(false);
            $table->timestamp('ultimo_aviso_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('busquedas_guardadas');
    }
};
