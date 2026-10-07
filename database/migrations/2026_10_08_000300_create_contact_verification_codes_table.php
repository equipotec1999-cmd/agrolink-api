<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Códigos de confirmación de contacto (correo o SMS). Se guardan como hash
        // para que un dump de la BD no revele códigos activos; expiran en 15 minutos.
        Schema::create('codigos_verificacion_contacto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('canal', 10); // 'email' | 'sms'
            $table->string('destino', 180); // correo o teléfono
            $table->string('codigo_hash', 255);
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('expira_en');
            $table->timestamp('creado_en')->useCurrent();
            $table->index(['usuario_id', 'canal']);
        });

        // Marca de verificación de teléfono (ya existía la de correo).
        Schema::table('usuarios', function (Blueprint $table) {
            $table->timestamp('telefono_verificado_en')->nullable()->after('correo_verificado_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_verificacion_contacto');
        Schema::table('usuarios', fn (Blueprint $table) => $table->dropColumn('telefono_verificado_en'));
    }
};
