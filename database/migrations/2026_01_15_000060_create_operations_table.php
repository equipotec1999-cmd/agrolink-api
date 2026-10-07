<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla lista desde ahora (Fase 1 §12) aunque su lógica se activa en Fase 5.
        // Nace siempre en OFERTA_ACEPTADA: NEGOCIANDO/OFERTA_ENVIADA ya son estados de `offers`.
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_id')->unique()->constrained('ofertas')->restrictOnDelete();
            $table->foreignId('publicacion_id')->constrained('publicaciones')->restrictOnDelete();
            $table->foreignId('comprador_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('vendedor_id')->constrained('usuarios')->restrictOnDelete();
            $table->decimal('monto', 14, 2);
            $table->decimal('cantidad', 12, 2);
            $table->enum('estatus', [
                'oferta_aceptada', 'pendiente_pago', 'pagado', 'preparando_entrega',
                'en_transito', 'entregado', 'confirmado', 'completado', 'cancelado', 'disputa',
            ])->default('oferta_aceptada');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operaciones');
    }
};
