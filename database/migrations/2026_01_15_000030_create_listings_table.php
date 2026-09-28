<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // RESTRICT: no se puede borrar un tipo de producto si tiene publicaciones (se desactiva, no se borra).
            $table->foreignId('product_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->enum('price_type', ['fixed', 'per_unit', 'per_kg', 'per_animal', 'per_lot', 'quote'])
                ->default('fixed');
            // Solo MXN por ahora (confirmado 25/sep); la columna queda lista para más monedas.
            $table->char('currency', 3)->default('MXN');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 30)->default('unidad');
            $table->enum('sale_mode', ['individual', 'lot'])->default('individual');
            $table->boolean('negotiable')->default(false);
            // Estado de publicación (visibilidad) — independiente de moderation_status.
            $table->enum('status', [
                'draft', 'pending_review', 'published', 'rejected',
                'suspended', 'sold', 'expired', 'archived',
            ])->default('draft');
            // Moderación (confianza) — confirmado 25/sep: SE PUBLICA primero y se revisa
            // después ("no es lo mismo revisar que aprobar"); 'rejected' aquí no oculta
            // la publicación por sí solo, pero dispara alerta y puede llevar a status=rejected.
            $table->enum('moderation_status', ['pending', 'approved', 'rejected'])->default('pending');
            // Copia desnormalizada de listing_attribute_values para filtrar rápido sin JOIN.
            $table->jsonb('attributes_cache')->default('{}');
            $table->timestamp('published_at')->nullable();
            // Vencimiento automático (confirmado 25/sep); NULL = no vence sola, solo se archiva a mano.
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // MXN-only mientras no se soporten más monedas (Fase 1 §18, "no inventar" -> aquí sí lo pidió el cliente).
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_currency_mxn_chk CHECK (currency = 'MXN')");
        DB::statement('CREATE INDEX listings_status_type_pub_idx ON listings(status, product_type_id, published_at)');
        DB::statement('CREATE INDEX listings_attributes_cache_gin ON listings USING GIN(attributes_cache)');
        // Búsqueda de texto en español, sin acentos (Fase 1 §9: "borregos", "habanero"...).
        // Usa immutable_unaccent() (creada en 2026_01_15_000000) porque unaccent() de la
        // extensión no es IMMUTABLE y Postgres rechaza usarla directo en un índice.
        DB::statement(<<<'SQL'
            CREATE INDEX listings_search_tsv_idx ON listings
            USING GIN (to_tsvector('spanish', immutable_unaccent(title) || ' ' || immutable_unaccent(coalesce(description, ''))))
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
