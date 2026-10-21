<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capa analítica tipo CONSTELACIÓN (galaxy schema): varias tablas de hechos que comparten
 * las mismas tablas de dimensiones.
 *
 *   Dimensiones (conformadas, compartidas):
 *     dim_tiempo · dim_usuario · dim_producto · dim_ubicacion · dim_estatus
 *
 *   Hechos:
 *     hecho_publicaciones  (grano: una publicación)
 *     hecho_ofertas        (grano: una oferta)
 *     hecho_operaciones    (grano: una operación)
 *     hecho_reportes       (grano: un reporte)
 *
 * Las tablas transaccionales (publicaciones, ofertas, chat...) NO cambian: la app sigue
 * escribiendo ahí. Esta capa se llena con `php artisan agrolink:cargar-constelacion` y sirve
 * para estadísticas y precios de referencia sin tocar las tablas pesadas.
 * Privacidad: ninguna dimensión guarda nombre, correo ni teléfono.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ───────── Dimensiones ─────────

        Schema::create('dim_tiempo', function (Blueprint $table) {
            $table->unsignedInteger('fecha_id')->primary(); // AAAAMMDD, p. ej. 20261021
            $table->date('fecha')->unique();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('trimestre');
            $table->unsignedTinyInteger('mes');
            $table->string('nombre_mes', 12);
            $table->unsignedTinyInteger('semana_anio');
            $table->unsignedTinyInteger('dia');
            $table->unsignedTinyInteger('dia_semana'); // 1 = lunes … 7 = domingo
            $table->boolean('es_fin_de_semana');
        });

        Schema::create('dim_usuario', function (Blueprint $table) {
            $table->id('usuario_sk');
            $table->unsignedBigInteger('usuario_id')->unique(); // clave natural
            $table->boolean('es_vendedor_verificado')->default(false);
            $table->unsignedInteger('operaciones_completadas')->default(0);
            $table->decimal('calificacion_promedio', 3, 2)->nullable();
            $table->unsignedSmallInteger('anio_registro')->nullable();
        });

        Schema::create('dim_producto', function (Blueprint $table) {
            $table->id('producto_sk');
            $table->unsignedBigInteger('tipo_producto_id')->unique();
            $table->string('tipo_nombre', 100);
            $table->unsignedBigInteger('categoria_id');
            $table->string('categoria_nombre', 100);
        });

        Schema::create('dim_ubicacion', function (Blueprint $table) {
            $table->id('ubicacion_sk');
            $table->string('estado', 100);
            $table->string('municipio', 100);

            $table->unique(['estado', 'municipio']);
        });

        Schema::create('dim_estatus', function (Blueprint $table) {
            $table->id('estatus_sk');
            $table->string('entidad', 30); // publicacion | oferta | operacion | reporte
            $table->string('codigo', 40);

            $table->unique(['entidad', 'codigo']);
        });

        // ───────── Hechos ─────────

        Schema::create('hecho_publicaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('publicacion_id')->primary(); // grano = una publicación
            $table->unsignedInteger('fecha_creacion_id');
            $table->unsignedInteger('fecha_publicacion_id')->nullable();
            $table->unsignedBigInteger('vendedor_sk');
            $table->unsignedBigInteger('producto_sk');
            $table->unsignedBigInteger('ubicacion_sk');
            $table->unsignedBigInteger('estatus_sk');
            $table->string('tipo_precio', 20); // dimensión degenerada
            $table->decimal('precio', 14, 2)->nullable();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 14, 2)->nullable();
            $table->boolean('negociable');
            $table->boolean('eliminada')->default(false);
            $table->unsignedInteger('num_fotos')->default(0);
            $table->unsignedInteger('num_favoritos')->default(0);
            $table->unsignedInteger('num_conversaciones')->default(0);
            $table->unsignedInteger('num_ofertas')->default(0);

            $table->foreign('fecha_creacion_id')->references('fecha_id')->on('dim_tiempo');
            $table->foreign('fecha_publicacion_id')->references('fecha_id')->on('dim_tiempo');
            $table->foreign('vendedor_sk')->references('usuario_sk')->on('dim_usuario');
            $table->foreign('producto_sk')->references('producto_sk')->on('dim_producto');
            $table->foreign('ubicacion_sk')->references('ubicacion_sk')->on('dim_ubicacion');
            $table->foreign('estatus_sk')->references('estatus_sk')->on('dim_estatus');

            $table->index(['producto_sk', 'ubicacion_sk']);
            $table->index('fecha_publicacion_id');
        });

        Schema::create('hecho_ofertas', function (Blueprint $table) {
            $table->unsignedBigInteger('oferta_id')->primary(); // grano = una oferta
            $table->unsignedBigInteger('publicacion_id');
            $table->unsignedInteger('fecha_id');
            $table->unsignedBigInteger('remitente_sk');
            $table->unsignedBigInteger('vendedor_sk');
            $table->unsignedBigInteger('producto_sk');
            $table->unsignedBigInteger('ubicacion_sk');
            $table->unsignedBigInteger('estatus_sk');
            $table->decimal('monto', 14, 2);
            $table->decimal('cantidad', 12, 2);
            // Qué tan lejos del precio de lista quedó la oferta (negativo = por debajo). Nulo si no hay precio de lista.
            $table->decimal('diferencia_pct_vs_lista', 7, 2)->nullable();

            $table->foreign('fecha_id')->references('fecha_id')->on('dim_tiempo');
            $table->foreign('remitente_sk')->references('usuario_sk')->on('dim_usuario');
            $table->foreign('vendedor_sk')->references('usuario_sk')->on('dim_usuario');
            $table->foreign('producto_sk')->references('producto_sk')->on('dim_producto');
            $table->foreign('ubicacion_sk')->references('ubicacion_sk')->on('dim_ubicacion');
            $table->foreign('estatus_sk')->references('estatus_sk')->on('dim_estatus');

            $table->index(['producto_sk', 'fecha_id']);
        });

        Schema::create('hecho_operaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('operacion_id')->primary(); // grano = una operación
            $table->unsignedBigInteger('publicacion_id');
            $table->unsignedInteger('fecha_id');
            $table->unsignedBigInteger('comprador_sk');
            $table->unsignedBigInteger('vendedor_sk');
            $table->unsignedBigInteger('producto_sk');
            $table->unsignedBigInteger('ubicacion_sk');
            $table->unsignedBigInteger('estatus_sk');
            $table->decimal('monto', 14, 2);
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 14, 2)->nullable();

            $table->foreign('fecha_id')->references('fecha_id')->on('dim_tiempo');
            $table->foreign('comprador_sk')->references('usuario_sk')->on('dim_usuario');
            $table->foreign('vendedor_sk')->references('usuario_sk')->on('dim_usuario');
            $table->foreign('producto_sk')->references('producto_sk')->on('dim_producto');
            $table->foreign('ubicacion_sk')->references('ubicacion_sk')->on('dim_ubicacion');
            $table->foreign('estatus_sk')->references('estatus_sk')->on('dim_estatus');

            $table->index(['producto_sk', 'fecha_id']);
        });

        Schema::create('hecho_reportes', function (Blueprint $table) {
            $table->unsignedBigInteger('reporte_id')->primary(); // grano = un reporte
            $table->unsignedInteger('fecha_id');
            $table->unsignedBigInteger('reportante_sk');
            $table->unsignedBigInteger('estatus_sk');
            $table->string('reportable_tipo', 60); // dimensión degenerada
            $table->string('motivo', 40);          // dimensión degenerada
            $table->unsignedInteger('horas_a_resolver')->nullable();

            $table->foreign('fecha_id')->references('fecha_id')->on('dim_tiempo');
            $table->foreign('reportante_sk')->references('usuario_sk')->on('dim_usuario');
            $table->foreign('estatus_sk')->references('estatus_sk')->on('dim_estatus');

            $table->index(['motivo', 'fecha_id']);
        });
    }

    public function down(): void
    {
        foreach (['hecho_reportes', 'hecho_operaciones', 'hecho_ofertas', 'hecho_publicaciones',
            'dim_estatus', 'dim_ubicacion', 'dim_producto', 'dim_usuario', 'dim_tiempo'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
