<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Cuándo vio la persona el aviso «¡Felicidades, ya eres usuario verificado!» (se muestra una sola vez). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_verificacion', function (Blueprint $table) {
            $table->timestamp('felicitacion_vista_en')->nullable();
        });
        // Las aprobadas antes de este cambio no muestran el aviso de golpe.
        DB::table('solicitudes_verificacion')->where('estatus', 'aprobada')->update(['felicitacion_vista_en' => now()]);
    }

    public function down(): void
    {
        Schema::table('solicitudes_verificacion', function (Blueprint $table) {
            $table->dropColumn('felicitacion_vista_en');
        });
    }
};
