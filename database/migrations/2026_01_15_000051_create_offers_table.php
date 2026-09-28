<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->enum('status', ['sent', 'accepted', 'rejected', 'countered', 'cancelled', 'expired'])
                ->default('sent');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
