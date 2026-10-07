<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publicaciones', function (Blueprint $table) {
            // Motivo del último rechazo/suspensión; el vendedor lo ve en "Mis publicaciones".
            $table->string('motivo_moderacion', 300)->nullable()->after('estatus_moderacion');
        });

        Schema::table('usuarios', function (Blueprint $table) {
            // Último intervalo TOTP aceptado: impide reusar el mismo código (replay).
            $table->unsignedBigInteger('dos_factores_ultimo_paso')->nullable()->after('dos_factores_confirmado_en');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', fn (Blueprint $table) => $table->dropColumn('dos_factores_ultimo_paso'));
        Schema::table('publicaciones', fn (Blueprint $table) => $table->dropColumn('motivo_moderacion'));
    }
};
