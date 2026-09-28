<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_locations', function (Blueprint $table) {
            // listing_id es la PK: relación 1 a 1 con la publicación.
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('state', 100);
            $table->string('municipality', 100);
            $table->string('postal_code', 10)->nullable();
            // Exacto: nunca se expone por la API pública (Fase 1 §6).
            $table->geography('exact_location', 'point', 4326);
            // Aproximado: desplazado ~2 km al copiarlo desde properties; es lo único público.
            $table->geography('approx_location', 'point', 4326);
            $table->timestamps();
        });

        DB::statement('CREATE INDEX listing_locations_approx_gix ON listing_locations USING GIST(approx_location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_locations');
    }
};
