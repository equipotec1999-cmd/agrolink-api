<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            // Métricas de reputación cacheadas: se recalculan con cada review/operación
            // (evita agregar sobre todo el historial en cada lectura del perfil).
            $table->unsignedInteger('completed_operations')->default(0);
            $table->unsignedInteger('cancelled_operations')->default(0);
            $table->decimal('rating_accuracy', 3, 2)->nullable();
            $table->decimal('rating_fulfillment', 3, 2)->nullable();
            $table->decimal('rating_communication', 3, 2)->nullable();
            $table->unsignedInteger('avg_response_minutes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
