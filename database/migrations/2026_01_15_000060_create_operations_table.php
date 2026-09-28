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
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->decimal('quantity', 12, 2);
            $table->enum('status', [
                'oferta_aceptada', 'pendiente_pago', 'pagado', 'preparando_entrega',
                'en_transito', 'entregado', 'confirmado', 'completado', 'cancelado', 'disputa',
            ])->default('oferta_aceptada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
