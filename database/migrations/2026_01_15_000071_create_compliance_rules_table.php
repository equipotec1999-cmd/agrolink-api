<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Capa de reglas regulatorias, independiente del código (Fase 1 §22).
        // Se edita desde administración; el backend nunca decide requisitos legales a mano.
        Schema::create('compliance_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->boolean('document_suggested')->default(false);
            $table->boolean('document_required')->default(false);
            // Siempre citar la fuente oficial (SENASICA, SAT, PROFECO...), nunca inventarla.
            $table->string('source_name', 120);
            $table->string('source_url')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE compliance_rules ADD CONSTRAINT compliance_rules_scope_chk CHECK (product_type_id IS NOT NULL OR category_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_rules');
    }
};
