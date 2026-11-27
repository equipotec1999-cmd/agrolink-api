<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIN de entrega (estilo DiDi): el comprador lo ve en su app y el vendedor lo escribe al entregar.
 * Se guarda cifrado (cast `encrypted`) porque el comprador debe poder verlo; los intentos fallidos
 * se cuentan para frenar a quien intente adivinarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->text('pin_entrega')->nullable();
            $table->unsignedSmallInteger('pin_intentos')->default(0);
            $table->timestamp('entregado_en')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropColumn(['pin_entrega', 'pin_intentos', 'entregado_en', 'motivo_cancelacion']);
        });
    }
};
