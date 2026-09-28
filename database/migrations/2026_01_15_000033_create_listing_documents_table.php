<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('storage_path');
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired', 'not_applicable'])
                ->default('pending');
            $table->string('note', 255)->nullable();
            // Quién lo revisó: nunca se auto-aprueba un documento subido (Fase 1 §8).
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_documents');
    }
};
