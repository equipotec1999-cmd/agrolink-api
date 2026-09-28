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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('state', 100);
            $table->string('municipality', 100);
            $table->string('postal_code', 10)->nullable();
            // Punto exacto: nunca se expone por la API pública (Fase 1 §6).
            $table->geography('exact_location', 'point', 4326)->nullable();
            // Punto aproximado: el único que devuelve el Resource al público.
            $table->geography('approx_location', 'point', 4326)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement('CREATE INDEX properties_exact_location_gix ON properties USING GIST(exact_location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
