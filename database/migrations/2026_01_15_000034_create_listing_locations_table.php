<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones_publicacion', function (Blueprint $table) {
            // listing_id es la PK: relación 1 a 1 con la publicación.
            $table->foreignId('publicacion_id')->primary()->constrained('publicaciones')->cascadeOnDelete();
            $table->foreignId('predio_id')->nullable()->constrained('predios')->nullOnDelete();
            $table->string('estado', 100);
            $table->string('municipio', 100);
            $table->string('codigo_postal', 10)->nullable();
            // Exacto: nunca se expone por la API pública (Fase 1 §6).
            $table->geography('ubicacion_exacta', 'point', 4326);
            // Aproximado: desplazado ~2 km al copiarlo desde properties; es lo único público.
            $table->geography('ubicacion_aproximada', 'point', 4326);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        DB::statement('CREATE INDEX ubicaciones_publicacion_aprox_gix ON ubicaciones_publicacion USING GIST(ubicacion_aproximada)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_publicacion');
    }
};
