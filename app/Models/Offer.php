<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Offer extends Modelo
{
    protected $table = 'ofertas';

    protected $fillable = [
        'conversacion_id',
        'remitente_id',
        'monto',
        'cantidad',
        'estatus',
        'vence_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'cantidad' => 'decimal:2',
            'vence_en' => 'datetime',
        ];
    }

    /** Una oferta abierta que nadie respondió en este plazo pasa a `expired`. */
    public const VIGENCIA_HORAS = 48;

    /** Marca como vencidas las ofertas abiertas cuyo plazo ya pasó (opcionalmente de una conversación). */
    public static function expireStale(?int $conversationId = null): void
    {
        static::query()
            ->where('estatus', 'enviada')
            ->where('vence_en', '<', now())
            ->when($conversationId, fn ($q) => $q->where('conversacion_id', $conversationId))
            ->update(['estatus' => 'vencida']);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversacion_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    public function operation(): HasOne
    {
        return $this->hasOne(Operation::class, 'oferta_id');
    }
}
