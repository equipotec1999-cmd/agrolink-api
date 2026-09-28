<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reputación de 3+ ejes, no una sola estrella (Fase 1 §13).
        // Cada participante de la operación califica una sola vez (unique operation_id+reviewer_id);
        // qué columnas llena depende de si reviewer es comprador o vendedor (se valida en el Service).
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('accuracy')->nullable();
            $table->unsignedTinyInteger('fulfillment')->nullable();
            $table->unsignedTinyInteger('communication')->nullable();
            $table->unsignedTinyInteger('payment')->nullable();
            $table->unsignedTinyInteger('reception')->nullable();
            $table->string('comment', 500)->nullable();
            $table->timestamps();

            $table->unique(['operation_id', 'reviewer_id']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE reviews ADD CONSTRAINT reviews_scores_range_chk CHECK (
              (accuracy IS NULL OR accuracy BETWEEN 1 AND 5) AND
              (fulfillment IS NULL OR fulfillment BETWEEN 1 AND 5) AND
              (communication IS NULL OR communication BETWEEN 1 AND 5) AND
              (payment IS NULL OR payment BETWEEN 1 AND 5) AND
              (reception IS NULL OR reception BETWEEN 1 AND 5)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
