<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separar nombre y apellidos: al mostrar al usuario se muestran juntos,
        // pero las compras/ventas y los documentos de verificación necesitan la
        // forma legal del nombre (nombre + apellidos).
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('apellidos', 150)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', fn (Blueprint $table) => $table->dropColumn('apellidos'));
    }
};
