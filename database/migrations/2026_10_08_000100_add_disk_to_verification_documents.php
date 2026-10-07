<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disco donde quedó cada documento (permite mover los nuevos a un bucket privado
        // sin perder los que ya estaban en el disco anterior). Null = disco por defecto.
        Schema::table('documentos_verificacion', function (Blueprint $table) {
            $table->string('disco', 40)->nullable()->after('tamano');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_verificacion', function (Blueprint $table) {
            $table->dropColumn('disco');
        });
    }
};
