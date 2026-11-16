<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operation extends Modelo
{
    protected $table = 'operaciones';

    protected $fillable = [
        'oferta_id',
        'publicacion_id',
        'comprador_id',
        'vendedor_id',
        'monto',
        'cantidad',
        'estatus',
    ];

    /**
     * Ciclo de vida con pago manual (sin pasarela): estado actual => rol => estados a los que ese rol puede pasarla.
     *
     *   oferta_aceptada ─(comprador avisa que pagó)→ pendiente_pago ─(vendedor confirma)→ pagado
     *   pagado → preparando_entrega → en_transito → entregado ─(comprador confirma)→ completado
     *
     * El vendedor puede saltarse pasos de entrega (p. ej. entrega en persona: de `pagado` a `entregado`) y
     * confirmar un pago hecho en mano desde `oferta_aceptada`. Cancelar solo antes de que el pago esté
     * confirmado; después ya hay dinero de por medio y el caso se resuelve por reporte a moderación.
     */
    public const FLUJO = [
        'oferta_aceptada' => [
            'buyer' => ['pendiente_pago', 'cancelado'],
            'seller' => ['pagado', 'cancelado'],
        ],
        'pendiente_pago' => [
            'buyer' => ['cancelado'],
            'seller' => ['pagado', 'cancelado'],
        ],
        'pagado' => ['seller' => ['preparando_entrega', 'en_transito', 'entregado']],
        'preparando_entrega' => ['seller' => ['en_transito', 'entregado']],
        'en_transito' => ['seller' => ['entregado']],
        'entregado' => ['buyer' => ['completado']],
    ];

    /** Estados a los que `$role` ('buyer'|'seller') puede mover una operación que está en `$estatus`. */
    public static function transicionesPara(string $estatus, string $role): array
    {
        return self::FLUJO[$estatus][$role] ?? [];
    }

    /** 'buyer' | 'seller' según quién es el usuario en esta operación (null si no participa). */
    public function roleOf(int $userId): ?string
    {
        return match ($userId) {
            $this->comprador_id => 'buyer',
            $this->vendedor_id => 'seller',
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'cantidad' => 'decimal:2',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'oferta_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'publicacion_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comprador_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OperationEvent::class, 'operacion_id')->orderBy('creado_en');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'operacion_id');
    }
}
