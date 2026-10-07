<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            // RESTRICT: no se puede borrar un tipo de producto si tiene publicaciones (se desactiva, no se borra).
            $table->foreignId('tipo_producto_id')->constrained('tipos_producto')->restrictOnDelete();
            $table->foreignId('predio_id')->nullable()->constrained('predios')->nullOnDelete();
            $table->string('titulo', 180);
            $table->string('slug', 220)->unique();
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 14, 2)->nullable();
            $table->enum('tipo_precio', ['fixed', 'per_unit', 'per_kg', 'per_animal', 'per_lot', 'quote'])
                ->default('fixed');
            // Solo MXN por ahora (confirmado 25/sep); la columna queda lista para más monedas.
            $table->char('moneda', 3)->default('MXN');
            $table->decimal('cantidad', 12, 2)->default(1);
            $table->string('unidad', 30)->default('unidad');
            $table->enum('modalidad_venta', ['individual', 'lot'])->default('individual');
            $table->boolean('negociable')->default(false);
            // Estado de publicación (visibilidad) — independiente de moderation_status.
            $table->enum('estatus', [
                'draft', 'pending_review', 'published', 'rejected',
                'suspended', 'sold', 'expired', 'archived',
            ])->default('draft');
            // Moderación (confianza) — confirmado 25/sep: SE PUBLICA primero y se revisa
            // después ("no es lo mismo revisar que aprobar"); 'rejected' aquí no oculta
            // la publicación por sí solo, pero dispara alerta y puede llevar a status=rejected.
            $table->enum('estatus_moderacion', ['pending', 'approved', 'rejected'])->default('pending');
            // Copia desnormalizada de listing_attribute_values para filtrar rápido sin JOIN.
            $table->jsonb('atributos_cache')->default('{}');
            $table->timestamp('publicado_en')->nullable();
            // Vencimiento automático (confirmado 25/sep); NULL = no vence sola, solo se archiva a mano.
            $table->timestamp('vence_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->softDeletes('eliminado_en');
        });

        // MXN-only mientras no se soporten más monedas (Fase 1 §18, "no inventar" -> aquí sí lo pidió el cliente).
        DB::statement("ALTER TABLE publicaciones ADD CONSTRAINT publicaciones_moneda_mxn_chk CHECK (moneda = 'MXN')");
        DB::statement('CREATE INDEX publicaciones_estatus_tipo_pub_idx ON publicaciones(estatus, tipo_producto_id, publicado_en)');
        DB::statement('CREATE INDEX publicaciones_atributos_cache_gin ON publicaciones USING GIN(atributos_cache)');
        // Búsqueda de texto en español, sin acentos (Fase 1 §9: "borregos", "habanero"...).
        // Usa immutable_unaccent() (creada en 2026_01_15_000000) porque unaccent() de la
        // extensión no es IMMUTABLE y Postgres rechaza usarla directo en un índice.
        DB::statement(<<<'SQL'
            CREATE INDEX publicaciones_busqueda_tsv_idx ON publicaciones
            USING GIN (to_tsvector('spanish', immutable_unaccent(titulo) || ' ' || immutable_unaccent(coalesce(descripcion, ''))))
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('publicaciones');
    }
};
