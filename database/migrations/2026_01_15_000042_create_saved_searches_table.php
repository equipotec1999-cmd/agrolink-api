<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            // Filtros serializados tal como los arma SearchFilters en Flutter (mismo shape).
            $table->jsonb('query')->default('{}');
            $table->boolean('notify_on_match')->default(true);
            $table->boolean('notify_on_price_change')->default(false);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
