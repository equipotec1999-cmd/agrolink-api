<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Guardar vendedores" (Fase 1 §10), separado de favoritos de publicaciones.
        Schema::create('followed_sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'seller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followed_sellers');
    }
};
