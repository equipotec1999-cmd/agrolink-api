<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valores_atributo_publicacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->foreignId('atributo_id')->constrained('atributos')->cascadeOnDelete();
            $table->foreignId('opcion_id')->nullable()->constrained('opciones_atributo')->nullOnDelete();
            // Una sola columna se usa según attributes.data_type; el resto queda NULL.
            $table->text('valor_texto')->nullable();
            $table->decimal('valor_numero', 14, 4)->nullable();
            $table->boolean('valor_booleano')->nullable();
            $table->date('valor_fecha')->nullable();
            // Nivel de confianza del dato (Fase 1 §8): nunca se asume válido un documento subido.
            $table->enum('nivel_verificacion', ['declared', 'documented', 'professional'])
                ->default('declared');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->unique(['publicacion_id', 'atributo_id']);
        });

        DB::statement('CREATE INDEX vap_atributo_numero_idx ON valores_atributo_publicacion(atributo_id, valor_numero)');
    }

    public function down(): void
    {
        Schema::dropIfExists('valores_atributo_publicacion');
    }
};
