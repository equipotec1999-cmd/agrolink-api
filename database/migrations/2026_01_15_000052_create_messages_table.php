<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->foreignId('remitente_id')->constrained('usuarios')->cascadeOnDelete();
            // Un mensaje puede SER una oferta (referenciarla) en vez de traer texto.
            $table->foreignId('oferta_id')->nullable()->constrained('ofertas')->nullOnDelete();
            $table->text('cuerpo')->nullable();
            $table->timestamp('leido_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index(['conversacion_id', 'creado_en']);
        });

        DB::statement('ALTER TABLE mensajes ADD CONSTRAINT mensajes_cuerpo_u_oferta_chk CHECK (cuerpo IS NOT NULL OR oferta_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
