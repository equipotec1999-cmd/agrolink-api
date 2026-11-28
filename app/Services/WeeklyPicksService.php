<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * «Destacados de la semana» gratuitos: 4 publicaciones que se ganan el lugar por mérito y
 * rotan cada 2 días. Requisitos (todos):
 *  - publicada y sin destacado pagado vigente (esas ya salen aparte);
 *  - vendedor sin sanción ni advertencias y SIN reportes (ni a su cuenta ni a sus publicaciones),
 *    salvo los que moderación descartó;
 *  - si el vendedor tiene reseñas, su promedio es de 4 o más;
 *  - algo de actividad: favoritos o conversaciones en la publicación, o ventas del vendedor.
 * Puntaje: conversaciones ×3 + favoritos ×2 + ventas ×4 + (promedio − 3) ×5. De los 20 mejores
 * (uno por vendedor) se toman 4 distintos en cada ventana de 2 días, en rotación.
 */
final class WeeklyPicksService
{
    public const PICKS = 4;

    public const WINDOW_SECONDS = 2 * 24 * 3600;

    private const POOL = 20;

    public static function window(): int
    {
        return intdiv(now()->getTimestamp(), self::WINDOW_SECONDS);
    }

    /** @return list<int> ids de las publicaciones elegidas en la ventana actual */
    public static function currentIds(): array
    {
        $window = self::window();
        $ttl = max(60, ($window + 1) * self::WINDOW_SECONDS - now()->getTimestamp());

        return Cache::remember("weekly-picks:$window", $ttl, fn () => self::pick($window));
    }

    /** @return list<int> */
    public static function pick(int $window): array
    {
        $pool = self::candidates();
        if (count($pool) <= self::PICKS) {
            return $pool;
        }
        $offset = ($window * self::PICKS) % count($pool);
        $ids = [];
        for ($i = 0; $i < self::PICKS; $i++) {
            $ids[] = $pool[($offset + $i) % count($pool)];
        }

        return $ids;
    }

    /** @return list<int> los mejores candidatos que cumplen los requisitos, uno por vendedor */
    public static function candidates(): array
    {
        $rows = DB::select(<<<'SQL'
            WITH reportados AS (
                SELECT r.reportable_id AS usuario_id FROM reportes r
                WHERE r.reportable_tipo = 'user' AND r.estatus <> 'dismissed' AND COALESCE(r.accion, '') <> 'descartar'
                UNION
                SELECT p.usuario_id FROM reportes r JOIN publicaciones p ON p.id = r.reportable_id
                WHERE r.reportable_tipo = 'listing' AND r.estatus <> 'dismissed' AND COALESCE(r.accion, '') <> 'descartar'
            ),
            calif AS (
                SELECT evaluado_id AS usuario_id,
                       COUNT(*) AS total,
                       AVG((exactitud + cumplimiento + comunicacion) / 3.0) AS promedio
                FROM resenas WHERE exactitud IS NOT NULL
                GROUP BY evaluado_id
            ),
            puntajes AS (
                SELECT p.id, p.usuario_id,
                       (SELECT COUNT(*) FROM conversaciones c WHERE c.publicacion_id = p.id) AS conversaciones,
                       (SELECT COUNT(*) FROM favoritos f WHERE f.publicacion_id = p.id) AS favoritos,
                       COALESCE(pv.operaciones_completadas, 0) AS ventas,
                       ca.total AS resenas, ca.promedio
                FROM publicaciones p
                JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN perfiles_vendedor pv ON pv.usuario_id = p.usuario_id
                LEFT JOIN calif ca ON ca.usuario_id = p.usuario_id
                WHERE p.estatus = 'publicada'
                  AND p.eliminado_en IS NULL
                  AND (p.destacada_hasta IS NULL OR p.destacada_hasta <= NOW())
                  AND u.eliminado_en IS NULL
                  AND (u.suspendido_hasta IS NULL OR u.suspendido_hasta <= NOW())
                  AND COALESCE(u.advertencias, 0) = 0
                  AND p.usuario_id NOT IN (SELECT usuario_id FROM reportados)
                  AND (ca.total IS NULL OR ca.promedio >= 4)
            ),
            elegibles AS (
                SELECT *, conversaciones * 3 + favoritos * 2 + ventas * 4
                          + CASE WHEN resenas IS NULL THEN 0 ELSE (promedio - 3) * 5 END AS puntaje
                FROM puntajes
                WHERE conversaciones > 0 OR favoritos > 0 OR ventas > 0
            ),
            mejor_por_vendedor AS (
                SELECT DISTINCT ON (usuario_id) id, puntaje
                FROM elegibles
                ORDER BY usuario_id, puntaje DESC, id DESC
            )
            SELECT id FROM mejor_por_vendedor ORDER BY puntaje DESC, id DESC LIMIT 20
        SQL);

        return array_map(fn ($r) => (int) $r->id, $rows);
    }
}
