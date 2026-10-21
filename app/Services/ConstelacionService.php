<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Carga la constelación (dimensiones + hechos) desde las tablas transaccionales.
 *
 * Es IDEMPOTENTE: se puede correr cuantas veces se quiera; usa UPSERT, así que las claves
 * sustitutas (`*_sk`) no cambian entre corridas. Todo es SQL por conjuntos (sin recorrer
 * filas en PHP), dentro de una sola transacción: si algo falla no queda a medias.
 */
class ConstelacionService
{
    private const CLAVE_ULTIMA_CARGA = 'constelacion.ultima_carga';

    /** @return array<string,int> filas por tabla tras la carga */
    public function cargar(): array
    {
        $conteos = DB::transaction(function () {
            $this->cargarDimTiempo();
            $this->cargarDimEstatus();
            $this->cargarDimProducto();
            $this->cargarDimUbicacion();
            $this->cargarDimUsuario();

            $this->cargarHechoPublicaciones();
            $this->cargarHechoOfertas();
            $this->cargarHechoOperaciones();
            $this->cargarHechoReportes();

            $conteos = [];
            foreach (['dim_tiempo', 'dim_usuario', 'dim_producto', 'dim_ubicacion', 'dim_estatus',
                'hecho_publicaciones', 'hecho_ofertas', 'hecho_operaciones', 'hecho_reportes'] as $t) {
                $conteos[$t] = (int) DB::table($t)->count();
            }

            return $conteos;
        });

        Cache::put(self::CLAVE_ULTIMA_CARGA, now()->toIso8601String(), now()->addDay());
        Cache::forget('mercado.resumen');

        return $conteos;
    }

    /**
     * Recarga solo si pasó más de $minutos desde la última vez (o nunca se cargó).
     * El candado evita que dos peticiones simultáneas carguen a la vez. Sirve en hostings
     * gratuitos sin planificador de tareas (Render free): la primera consulta de estadísticas
     * refresca los datos.
     */
    public function refrescarSiHaceFalta(int $minutos = 30): void
    {
        // Sin diffInMinutes: en Carbon 3 es con signo y según el orden da negativo. Aquí se
        // pregunta directo si "última carga + N minutos" todavía está en el futuro.
        $ultima = Cache::get(self::CLAVE_ULTIMA_CARGA);
        if ($ultima && Carbon::parse($ultima)->addMinutes($minutos)->isFuture()) {
            return;
        }

        $candado = Cache::lock('constelacion.cargando', 120);
        if ($candado->get()) {
            try {
                $this->cargar();
            } finally {
                $candado->release();
            }
        }
    }

    public function ultimaCarga(): ?string
    {
        return Cache::get(self::CLAVE_ULTIMA_CARGA);
    }

    // ───────── Dimensiones ─────────

    private function cargarDimTiempo(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO dim_tiempo (fecha_id, fecha, anio, trimestre, mes, nombre_mes, semana_anio, dia, dia_semana, es_fin_de_semana)
            SELECT to_char(d, 'YYYYMMDD')::int,
                   d::date,
                   EXTRACT(YEAR FROM d)::int,
                   EXTRACT(QUARTER FROM d)::int,
                   EXTRACT(MONTH FROM d)::int,
                   (ARRAY['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'])[EXTRACT(MONTH FROM d)::int],
                   EXTRACT(WEEK FROM d)::int,
                   EXTRACT(DAY FROM d)::int,
                   EXTRACT(ISODOW FROM d)::int,
                   EXTRACT(ISODOW FROM d) >= 6
            FROM generate_series(
                COALESCE((
                    SELECT MIN(f) FROM (
                        SELECT MIN(creado_en)::date AS f FROM publicaciones
                        UNION ALL SELECT MIN(creado_en)::date FROM ofertas
                        UNION ALL SELECT MIN(creado_en)::date FROM operaciones
                        UNION ALL SELECT MIN(creado_en)::date FROM reportes
                    ) x
                ), CURRENT_DATE) - 30,
                CURRENT_DATE + 365,
                interval '1 day'
            ) AS d
            ON CONFLICT (fecha_id) DO NOTHING
        SQL);
    }

    private function cargarDimEstatus(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO dim_estatus (entidad, codigo)
            SELECT entidad, codigo FROM (
                SELECT DISTINCT 'publicacion' AS entidad, estatus::text AS codigo FROM publicaciones
                UNION SELECT DISTINCT 'oferta', estatus::text FROM ofertas
                UNION SELECT DISTINCT 'operacion', estatus::text FROM operaciones
                UNION SELECT DISTINCT 'reporte', estatus::text FROM reportes
            ) e
            ON CONFLICT (entidad, codigo) DO NOTHING
        SQL);
    }

    private function cargarDimProducto(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO dim_producto (tipo_producto_id, tipo_nombre, categoria_id, categoria_nombre)
            SELECT t.id, t.nombre, c.id, c.nombre
            FROM tipos_producto t
            JOIN categorias c ON c.id = t.categoria_id
            ON CONFLICT (tipo_producto_id) DO UPDATE SET
                tipo_nombre = EXCLUDED.tipo_nombre,
                categoria_id = EXCLUDED.categoria_id,
                categoria_nombre = EXCLUDED.categoria_nombre
        SQL);
    }

    private function cargarDimUbicacion(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO dim_ubicacion (estado, municipio)
            SELECT estado, municipio FROM (
                SELECT 'Sin definir' AS estado, 'Sin definir' AS municipio
                UNION SELECT DISTINCT estado, municipio FROM ubicaciones_publicacion
            ) u
            ON CONFLICT (estado, municipio) DO NOTHING
        SQL);
    }

    private function cargarDimUsuario(): void
    {
        // Sin nombre, correo ni teléfono: la dimensión solo guarda lo necesario para analizar.
        DB::statement(<<<'SQL'
            INSERT INTO dim_usuario (usuario_id, es_vendedor_verificado, operaciones_completadas, calificacion_promedio, anio_registro)
            SELECT u.id,
                   COALESCE(p.verificado, false),
                   COALESCE(p.operaciones_completadas, 0),
                   ROUND((SELECT AVG(v) FROM (VALUES
                        (p.calificacion_exactitud), (p.calificacion_cumplimiento), (p.calificacion_comunicacion)
                   ) t(v)), 2),
                   EXTRACT(YEAR FROM u.creado_en)::int
            FROM usuarios u
            LEFT JOIN perfiles_vendedor p ON p.usuario_id = u.id
            ON CONFLICT (usuario_id) DO UPDATE SET
                es_vendedor_verificado = EXCLUDED.es_vendedor_verificado,
                operaciones_completadas = EXCLUDED.operaciones_completadas,
                calificacion_promedio = EXCLUDED.calificacion_promedio,
                anio_registro = EXCLUDED.anio_registro
        SQL);
    }

    // ───────── Hechos ─────────

    private function cargarHechoPublicaciones(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO hecho_publicaciones (
                publicacion_id, fecha_creacion_id, fecha_publicacion_id, vendedor_sk, producto_sk, ubicacion_sk,
                estatus_sk, tipo_precio, precio, cantidad, precio_unitario, negociable, eliminada,
                num_fotos, num_favoritos, num_conversaciones, num_ofertas
            )
            SELECT p.id,
                   to_char(COALESCE(p.creado_en, now()), 'YYYYMMDD')::int,
                   CASE WHEN p.publicado_en IS NULL THEN NULL ELSE to_char(p.publicado_en, 'YYYYMMDD')::int END,
                   du.usuario_sk,
                   dp.producto_sk,
                   COALESCE(dl.ubicacion_sk, ds.ubicacion_sk),
                   de.estatus_sk,
                   p.tipo_precio::text,
                   p.precio,
                   p.cantidad,
                   CASE
                       WHEN p.precio IS NULL THEN NULL
                       WHEN p.tipo_precio::text IN ('fixed', 'per_lot') AND p.cantidad > 0 THEN ROUND(p.precio / p.cantidad, 2)
                       ELSE p.precio
                   END,
                   p.negociable,
                   p.eliminado_en IS NOT NULL,
                   COALESCE(m.n, 0), COALESCE(f.n, 0), COALESCE(c.n, 0), COALESCE(o.n, 0)
            FROM publicaciones p
            JOIN dim_usuario du ON du.usuario_id = p.usuario_id
            JOIN dim_producto dp ON dp.tipo_producto_id = p.tipo_producto_id
            JOIN dim_estatus de ON de.entidad = 'publicacion' AND de.codigo = p.estatus::text
            LEFT JOIN ubicaciones_publicacion up ON up.publicacion_id = p.id
            LEFT JOIN dim_ubicacion dl ON dl.estado = up.estado AND dl.municipio = up.municipio
            CROSS JOIN (SELECT ubicacion_sk FROM dim_ubicacion WHERE estado = 'Sin definir' AND municipio = 'Sin definir') ds
            LEFT JOIN (SELECT publicacion_id, COUNT(*) AS n FROM medios_publicacion GROUP BY publicacion_id) m ON m.publicacion_id = p.id
            LEFT JOIN (SELECT publicacion_id, COUNT(*) AS n FROM favoritos GROUP BY publicacion_id) f ON f.publicacion_id = p.id
            LEFT JOIN (SELECT publicacion_id, COUNT(*) AS n FROM conversaciones GROUP BY publicacion_id) c ON c.publicacion_id = p.id
            LEFT JOIN (
                SELECT cv.publicacion_id, COUNT(*) AS n
                FROM ofertas ofe JOIN conversaciones cv ON cv.id = ofe.conversacion_id
                GROUP BY cv.publicacion_id
            ) o ON o.publicacion_id = p.id
            ON CONFLICT (publicacion_id) DO UPDATE SET
                fecha_creacion_id = EXCLUDED.fecha_creacion_id,
                fecha_publicacion_id = EXCLUDED.fecha_publicacion_id,
                vendedor_sk = EXCLUDED.vendedor_sk,
                producto_sk = EXCLUDED.producto_sk,
                ubicacion_sk = EXCLUDED.ubicacion_sk,
                estatus_sk = EXCLUDED.estatus_sk,
                tipo_precio = EXCLUDED.tipo_precio,
                precio = EXCLUDED.precio,
                cantidad = EXCLUDED.cantidad,
                precio_unitario = EXCLUDED.precio_unitario,
                negociable = EXCLUDED.negociable,
                eliminada = EXCLUDED.eliminada,
                num_fotos = EXCLUDED.num_fotos,
                num_favoritos = EXCLUDED.num_favoritos,
                num_conversaciones = EXCLUDED.num_conversaciones,
                num_ofertas = EXCLUDED.num_ofertas
        SQL);
    }

    private function cargarHechoOfertas(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO hecho_ofertas (
                oferta_id, publicacion_id, fecha_id, remitente_sk, vendedor_sk, producto_sk, ubicacion_sk,
                estatus_sk, monto, cantidad, diferencia_pct_vs_lista
            )
            SELECT o.id,
                   cv.publicacion_id,
                   to_char(COALESCE(o.creado_en, now()), 'YYYYMMDD')::int,
                   dr.usuario_sk,
                   dv.usuario_sk,
                   hp.producto_sk,
                   hp.ubicacion_sk,
                   de.estatus_sk,
                   o.monto,
                   o.cantidad,
                   CASE
                       WHEN hp.precio_unitario IS NULL OR hp.precio_unitario = 0 OR o.cantidad <= 0 THEN NULL
                       ELSE GREATEST(-99999, LEAST(99999,
                            ROUND(((o.monto / o.cantidad) - hp.precio_unitario) / hp.precio_unitario * 100, 2)))
                   END
            FROM ofertas o
            JOIN conversaciones cv ON cv.id = o.conversacion_id
            JOIN hecho_publicaciones hp ON hp.publicacion_id = cv.publicacion_id
            JOIN dim_usuario dr ON dr.usuario_id = o.remitente_id
            JOIN dim_usuario dv ON dv.usuario_id = cv.vendedor_id
            JOIN dim_estatus de ON de.entidad = 'oferta' AND de.codigo = o.estatus::text
            ON CONFLICT (oferta_id) DO UPDATE SET
                publicacion_id = EXCLUDED.publicacion_id,
                fecha_id = EXCLUDED.fecha_id,
                remitente_sk = EXCLUDED.remitente_sk,
                vendedor_sk = EXCLUDED.vendedor_sk,
                producto_sk = EXCLUDED.producto_sk,
                ubicacion_sk = EXCLUDED.ubicacion_sk,
                estatus_sk = EXCLUDED.estatus_sk,
                monto = EXCLUDED.monto,
                cantidad = EXCLUDED.cantidad,
                diferencia_pct_vs_lista = EXCLUDED.diferencia_pct_vs_lista
        SQL);
    }

    private function cargarHechoOperaciones(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO hecho_operaciones (
                operacion_id, publicacion_id, fecha_id, comprador_sk, vendedor_sk, producto_sk, ubicacion_sk,
                estatus_sk, monto, cantidad, precio_unitario
            )
            SELECT op.id,
                   op.publicacion_id,
                   to_char(COALESCE(op.creado_en, now()), 'YYYYMMDD')::int,
                   dc.usuario_sk,
                   dv.usuario_sk,
                   hp.producto_sk,
                   hp.ubicacion_sk,
                   de.estatus_sk,
                   op.monto,
                   op.cantidad,
                   CASE WHEN op.cantidad > 0 THEN ROUND(op.monto / op.cantidad, 2) END
            FROM operaciones op
            JOIN hecho_publicaciones hp ON hp.publicacion_id = op.publicacion_id
            JOIN dim_usuario dc ON dc.usuario_id = op.comprador_id
            JOIN dim_usuario dv ON dv.usuario_id = op.vendedor_id
            JOIN dim_estatus de ON de.entidad = 'operacion' AND de.codigo = op.estatus::text
            ON CONFLICT (operacion_id) DO UPDATE SET
                publicacion_id = EXCLUDED.publicacion_id,
                fecha_id = EXCLUDED.fecha_id,
                comprador_sk = EXCLUDED.comprador_sk,
                vendedor_sk = EXCLUDED.vendedor_sk,
                producto_sk = EXCLUDED.producto_sk,
                ubicacion_sk = EXCLUDED.ubicacion_sk,
                estatus_sk = EXCLUDED.estatus_sk,
                monto = EXCLUDED.monto,
                cantidad = EXCLUDED.cantidad,
                precio_unitario = EXCLUDED.precio_unitario
        SQL);
    }

    private function cargarHechoReportes(): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO hecho_reportes (reporte_id, fecha_id, reportante_sk, estatus_sk, reportable_tipo, motivo, horas_a_resolver)
            SELECT r.id,
                   to_char(COALESCE(r.creado_en, now()), 'YYYYMMDD')::int,
                   du.usuario_sk,
                   de.estatus_sk,
                   r.reportable_tipo,
                   r.motivo::text,
                   CASE WHEN r.resuelto_en IS NOT NULL AND r.creado_en IS NOT NULL
                        THEN GREATEST(0, (EXTRACT(EPOCH FROM (r.resuelto_en - r.creado_en)) / 3600)::int)
                   END
            FROM reportes r
            JOIN dim_usuario du ON du.usuario_id = r.reportante_id
            JOIN dim_estatus de ON de.entidad = 'reporte' AND de.codigo = r.estatus::text
            ON CONFLICT (reporte_id) DO UPDATE SET
                fecha_id = EXCLUDED.fecha_id,
                reportante_sk = EXCLUDED.reportante_sk,
                estatus_sk = EXCLUDED.estatus_sk,
                reportable_tipo = EXCLUDED.reportable_tipo,
                motivo = EXCLUDED.motivo,
                horas_a_resolver = EXCLUDED.horas_a_resolver
        SQL);
    }
}
