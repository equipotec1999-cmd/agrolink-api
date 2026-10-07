<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingAttributeValue extends Modelo
{
    protected $table = 'valores_atributo_publicacion';

    protected $fillable = [
        'publicacion_id',
        'atributo_id',
        'opcion_id',
        'valor_texto',
        'valor_numero',
        'valor_booleano',
        'valor_fecha',
        'nivel_verificacion',
    ];

    protected function casts(): array
    {
        return [
            'valor_numero' => 'decimal:4',
            'valor_booleano' => 'boolean',
            'valor_fecha' => 'date',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'publicacion_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'atributo_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'opcion_id');
    }

    /**
     * El valor "crudo" en la columna correcta según attributes.data_type, sin que quien
     * llame tenga que saber cuál de las 4 columnas usar.
     */
    public function getValueAttribute(): mixed
    {
        return match ($this->attribute?->tipo_dato) {
            'number' => $this->valor_numero,
            'boolean' => $this->valor_booleano,
            'date' => $this->valor_fecha,
            'select' => $this->option?->valor ?? $this->valor_texto,
            default => $this->valor_texto,
        };
    }
}
