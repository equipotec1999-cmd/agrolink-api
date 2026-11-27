<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubicación compartida en el chat: el usuario decide mandarla para concretar
 * la entrega. Se guarda tal cual la mandó (no se desplaza como la de la
 * publicación) y solo la ven los dos participantes de la conversación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->decimal('ubicacion_lat', 9, 6)->nullable();
            $table->decimal('ubicacion_lng', 9, 6)->nullable();
            $table->string('ubicacion_etiqueta', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropColumn(['ubicacion_lat', 'ubicacion_lng', 'ubicacion_etiqueta']);
        });
    }
};
