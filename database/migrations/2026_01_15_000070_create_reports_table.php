<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            // Polimórfico simple: por ahora solo 'listing' y 'user', pero deja espacio a más.
            $table->string('reportable_type', 60);
            $table->unsignedBigInteger('reportable_id');
            $table->enum('reason', [
                'fraude', 'informacion_falsa', 'producto_inexistente', 'documentacion_sospechosa',
                'publicacion_duplicada', 'conducta_inapropiada', 'producto_no_permitido', 'otro',
            ]);
            $table->string('description', 500)->nullable();
            $table->enum('status', ['open', 'investigating', 'resolved', 'dismissed'])->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_note', 500)->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
