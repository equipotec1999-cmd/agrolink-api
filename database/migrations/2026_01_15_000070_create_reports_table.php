<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reportante_id')->constrained('usuarios')->cascadeOnDelete();
            // Polimórfico simple: por ahora solo 'listing' y 'user', pero deja espacio a más.
            $table->string('reportable_tipo', 60);
            $table->unsignedBigInteger('reportable_id');
            $table->enum('motivo', [
                'fraude', 'informacion_falsa', 'producto_inexistente', 'documentacion_sospechosa',
                'publicacion_duplicada', 'conducta_inapropiada', 'producto_no_permitido', 'otro',
            ]);
            $table->string('descripcion', 500)->nullable();
            $table->enum('estatus', ['abierto', 'investigating', 'resuelto', 'dismissed'])->default('abierto');
            $table->foreignId('resuelto_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('resuelto_en')->nullable();
            $table->string('nota_resolucion', 500)->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index(['reportable_tipo', 'reportable_id']);
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
