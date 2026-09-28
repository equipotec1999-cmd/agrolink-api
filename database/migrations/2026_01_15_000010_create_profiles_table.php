<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            // user_id es la PK: relación 1 a 1 estricta, sin id propio.
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
