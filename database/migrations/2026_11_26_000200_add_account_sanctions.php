<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sanciones de moderación sobre cuentas:
 *  - suspendido_hasta / motivo_suspension: sanción temporal (solo lectura, publicaciones ocultas).
 *  - eliminado_en (ya existía): cuenta eliminada por moderación; no puede iniciar sesión.
 *  - advertencias: contador de avisos recibidos (el detalle queda en reportes y bitácora).
 *  - reportes.accion: qué medida se tomó al resolver (para avisarle al que reportó).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->timestamp('suspendido_hasta')->nullable();
            $table->string('motivo_suspension', 500)->nullable();
            $table->string('motivo_eliminacion', 500)->nullable();
            $table->unsignedSmallInteger('advertencias')->default(0);
        });
        Schema::table('reportes', function (Blueprint $table) {
            $table->string('accion', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['suspendido_hasta', 'motivo_suspension', 'motivo_eliminacion', 'advertencias']);
        });
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn('accion');
        });
    }
};
