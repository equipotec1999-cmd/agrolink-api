<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solicitud para ser "vendedor verificado": la revisa un moderador con permiso sobre documentos.
        Schema::create('solicitudes_verificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('estatus', 20)->default('pending'); // pending | approved | rejected
            $table->string('nombre_negocio', 150)->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index(['usuario_id', 'estatus']);
        });

        // Una sola solicitud pendiente por persona (aunque se toque dos veces "enviar").
        DB::statement("CREATE UNIQUE INDEX solicitudes_verificacion_una_pendiente ON solicitudes_verificacion (usuario_id) WHERE estatus = 'pending'");

        // Los archivos viven en el almacenamiento (nunca en la base); aquí solo su ruta.
        Schema::create('documentos_verificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_verificacion')->cascadeOnDelete();
            $table->string('tipo', 30); // identificacion | comprobante_actividad | otro
            $table->string('ruta_almacenamiento');
            $table->string('nombre_original')->nullable();
            $table->string('tipo_mime', 100);
            $table->unsignedInteger('tamano');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('solicitud_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_verificacion');
        Schema::dropIfExists('solicitudes_verificacion');
    }
};
