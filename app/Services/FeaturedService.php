<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/** Activa o quita el destacado de una publicación (destacada_hasta) o un perfil (destacado_hasta). */
final class FeaturedService
{
    /** Suma días: si ya estaba destacado, se alarga desde la fecha en que vencía. */
    public static function extend(Model $model, string $column, int $days): CarbonInterface
    {
        $current = $model->{$column};
        $from = ($current && now()->lessThan($current)) ? \Carbon\Carbon::parse($current) : now();
        $until = $from->copy()->addDays($days);
        $model->forceFill([$column => $until])->save();

        return $until;
    }

    public static function clear(Model $model, string $column): void
    {
        $model->forceFill([$column => null])->save();
    }
}
