<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fincas/ranchos del usuario: guarda ubicaciones reutilizables para precargar
        // al publicar. Un usuario puede tener varias (confirmado 25/sep).
        Schema::create('predios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('estado', 100);
            $table->string('municipio', 100);
            $table->string('codigo_postal', 10)->nullable();
            // Punto exacto: nunca se expone por la API pública (Fase 1 §6).
            $table->geography('ubicacion_exacta', 'point', 4326)->nullable();
            // Punto aproximado: el único que devuelve el Resource al público.
            $table->geography('ubicacion_aproximada', 'point', 4326)->nullable();
            $table->boolean('es_predeterminado')->default(false);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('usuario_id');
        });

        DB::statement('CREATE INDEX predios_ubicacion_exacta_gix ON predios USING GIST(ubicacion_exacta)');
    }

    public function down(): void
    {
        Schema::dropIfExists('predios');
    }
};
