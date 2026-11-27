<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A partir de aquí toda cuenta nueva confirma su correo con un código. Las cuentas que ya
 * existían se dan por confirmadas para no dejarlas fuera al iniciar sesión.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('usuarios')
            ->whereNull('correo_verificado_en')
            ->update(['correo_verificado_en' => DB::raw('COALESCE(creado_en, NOW())')]);
    }

    public function down(): void
    {
        // Sin vuelta atrás: no sabemos cuáles estaban sin confirmar.
    }
};
