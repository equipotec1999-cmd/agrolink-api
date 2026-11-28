<?php

namespace App\Support;

/**
 * Precio de destacar (publicación o perfil): el primer día cuesta 20 MXN y cada día extra
 * un peso menos que el anterior, sin bajar de 8 MXN por día.
 *   1 día = 20 · 2 días = 20+19 = 39 · 3 días = 20+19+18 = 57 · ... · del día 13 en adelante, 8 c/u.
 */
final class FeaturedPricing
{
    public const FIRST_DAY = 20;

    public const MIN_DAY = 8;

    public const MAX_DAYS = 60;

    /** Precio del día número $n (1 = primer día). */
    public static function dayPrice(int $n): int
    {
        return max(self::MIN_DAY, self::FIRST_DAY - ($n - 1));
    }

    /** @return list<int> precio de cada día */
    public static function breakdown(int $days): array
    {
        $days = max(1, min(self::MAX_DAYS, $days));

        return array_map(fn ($n) => self::dayPrice($n), range(1, $days));
    }

    public static function total(int $days): int
    {
        return array_sum(self::breakdown($days));
    }

    public static function quote(int $days): array
    {
        $days = max(1, min(self::MAX_DAYS, $days));
        $total = self::total($days);
        $full = self::FIRST_DAY * $days;

        return [
            'days' => $days,
            'per_day' => self::breakdown($days),
            'total' => $total,
            'savings' => $full - $total,
            'currency' => 'MXN',
            'max_days' => self::MAX_DAYS,
        ];
    }
}
