<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->string('slug', 100)->unique();
            $table->string('icono', 50)->nullable();
            // Vencimiento automático por tipo (confirmado 25/sep: vence solo O se archiva).
            // Animales vivos y producción fresca no deberían tener el mismo default.
            $table->unsignedSmallInteger('dias_vigencia_predeterminados')->default(60);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_producto');
    }
};
