<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->foreignId('remitente_id')->constrained('usuarios')->cascadeOnDelete();
            $table->decimal('monto', 14, 2);
            $table->decimal('cantidad', 12, 2)->default(1);
            $table->enum('estatus', ['enviada', 'aceptada', 'rechazada', 'contraoferta', 'cancelled', 'vencida'])
                ->default('enviada');
            $table->timestamp('vence_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('conversacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas');
    }
};
