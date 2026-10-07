<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un usuario puede registrarse con correo O con teléfono: ambos son opcionales
        // en la BD; el controlador exige al menos uno. Teléfono también único.
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('correo')->nullable()->change();
        });
        // Para que el índice único de teléfono no se queje con los valores existentes:
        // Postgres permite múltiples NULL por default en UNIQUE, así que esto está bien
        // mientras `telefono` sea nullable (ya lo es).
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS usuarios_telefono_unique ON usuarios (telefono) WHERE telefono IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS usuarios_telefono_unique');
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('correo')->nullable(false)->change();
        });
    }
};
