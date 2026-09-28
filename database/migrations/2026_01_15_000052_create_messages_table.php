<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            // Un mensaje puede SER una oferta (referenciarla) en vez de traer texto.
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->text('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        DB::statement('ALTER TABLE messages ADD CONSTRAINT messages_body_or_offer_chk CHECK (body IS NOT NULL OR offer_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
