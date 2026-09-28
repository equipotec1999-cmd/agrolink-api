<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
            // Una sola columna se usa según attributes.data_type; el resto queda NULL.
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 14, 4)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->date('value_date')->nullable();
            // Nivel de confianza del dato (Fase 1 §8): nunca se asume válido un documento subido.
            $table->enum('verification_level', ['declared', 'documented', 'professional'])
                ->default('declared');
            $table->timestamps();

            $table->unique(['listing_id', 'attribute_id']);
        });

        DB::statement('CREATE INDEX lav_attribute_number_idx ON listing_attribute_values(attribute_id, value_number)');
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_attribute_values');
    }
};
