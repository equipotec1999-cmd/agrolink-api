<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Formato estándar de Laravel Notifications (canal database); compatible con
        // ->notify() de cualquier modelo sin código adicional.
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo');
            $table->string('notificable_tipo');
            $table->unsignedBigInteger('notificable_id');
            $table->jsonb('datos');
            $table->timestamp('leido_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index(['notificable_tipo', 'notificable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
