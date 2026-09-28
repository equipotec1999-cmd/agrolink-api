<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['photo', 'video'])->default('photo');
            // Solo la clave del archivo en S3/R2; la BD nunca guarda binarios (Fase 1 §16).
            $table->string('storage_path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_media');
    }
};
