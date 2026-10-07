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
