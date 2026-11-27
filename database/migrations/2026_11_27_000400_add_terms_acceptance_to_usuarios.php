<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Qué versión del aviso de privacidad y los términos aceptó cada cuenta, y cuándo. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('terminos_version', 20)->nullable();
            $table->timestamp('terminos_aceptados_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['terminos_version', 'terminos_aceptados_en']);
        });
    }
};
