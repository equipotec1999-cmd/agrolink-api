<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Destacados: publicaciones y perfiles que salen primero (los activa moderación) y las
 * solicitudes de destacado pagado (precio en App\Support\FeaturedPricing).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publicaciones', function (Blueprint $table) {
            $table->timestamp('destacada_hasta')->nullable();
        });
        Schema::table('usuarios', function (Blueprint $table) {
            $table->timestamp('destacado_hasta')->nullable();
        });
        DB::statement('CREATE INDEX publicaciones_destacada_hasta_idx ON publicaciones (destacada_hasta) WHERE destacada_hasta IS NOT NULL');
        DB::statement('CREATE INDEX usuarios_destacado_hasta_idx ON usuarios (destacado_hasta) WHERE destacado_hasta IS NOT NULL');

        Schema::create('solicitudes_destacado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo', 20); // publicacion | perfil
            $table->foreignId('publicacion_id')->nullable()->constrained('publicaciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('dias');
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('MXN');
            $table->string('estatus', 20)->default('pendiente_pago'); // pendiente_pago | activa | rechazada | cancelada
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nota', 300)->nullable();
            $table->timestamp('activada_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->index(['estatus', 'creado_en']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_destacado');
        DB::statement('DROP INDEX IF EXISTS publicaciones_destacada_hasta_idx');
        DB::statement('DROP INDEX IF EXISTS usuarios_destacado_hasta_idx');
        Schema::table('usuarios', fn (Blueprint $t) => $t->dropColumn('destacado_hasta'));
        Schema::table('publicaciones', fn (Blueprint $t) => $t->dropColumn('destacada_hasta'));
    }
};
