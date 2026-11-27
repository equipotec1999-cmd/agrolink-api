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
        'pin_entrega',
        'pin_intentos',
        'entregado_en',
        'motivo_cancelacion',
    ];

    /** Nunca sale en JSON por accidente: el Resource decide quién lo ve (solo el comprador). */
    protected $hidden = ['pin_entrega'];

    public const PIN_LENGTH = 4;

    public const PIN_MAX_ATTEMPTS = 5;

    /** Estados en los que el vendedor puede cerrar la entrega con el PIN del comprador. */
    public const PIN_ESTADOS = [
        'oferta_aceptada', 'pendiente_pago', 'pagado', 'preparando_entrega', 'en_transito', 'entregado',
    ];

    /**
     * Ciclo de vida con pago manual (sin pasarela): estado actual => rol => estados a los que ese rol puede pasarla.
     *
     *   oferta_aceptada ─(comprador avisa que pagó)→ pendiente_pago ─(vendedor confirma)→ pagado
     *   pagado → preparando_entrega → en_transito → (entrega) → completado
     *
     * La entrega se confirma de dos formas:
     *   - El vendedor escribe el PIN que tiene el comprador (POST /operations/{id}/deliver). Prueba que se
     *     vieron en persona; cierra la operación aunque el pago se haya hecho en mano al entregar.
     *   - El comprador toca «Ya lo recibí» (→ completado), para envíos donde no se ven.
     * El vendedor NO puede marcar «entregado» sin PIN.
     *
     * Cancelar (con motivo): el comprador antes de que el pago se confirme; el vendedor mientras no haya
     * salido el pedido (si ya cobró, él devuelve el dinero). En camino ya no se cancela: se reporta.
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
        'pagado' => [
            'buyer' => ['completado'],
            'seller' => ['preparando_entrega', 'en_transito', 'cancelado'],
        ],
        'preparando_entrega' => [
            'buyer' => ['completado'],
            'seller' => ['en_transito', 'cancelado'],
        ],
        'en_transito' => ['buyer' => ['completado']],
        // Operaciones viejas que quedaron en «entregado» antes del PIN.
        'entregado' => ['buyer' => ['completado']],
    ];

    /** PIN nuevo de 4 dígitos (reinicia los intentos). */
    public function regeneratePin(): string
    {
        $pin = str_pad((string) random_int(0, 9999), self::PIN_LENGTH, '0', STR_PAD_LEFT);
        $this->forceFill(['pin_entrega' => $pin, 'pin_intentos' => 0])->save();

        return $pin;
    }

    /** Asegura que la operación activa tenga PIN (las creadas antes de esta función no lo tienen). */
    public function ensurePin(): void
    {
        if (! $this->pin_entrega && in_array($this->estatus, self::PIN_ESTADOS, true)) {
            $this->regeneratePin();
        }
    }

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
            'pin_entrega' => 'encrypted',
            'entregado_en' => 'datetime',
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
