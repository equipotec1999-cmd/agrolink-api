<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingAttributeValue extends Model
{
    protected $fillable = [
        'listing_id',
        'attribute_id',
        'option_id',
        'value_text',
        'value_number',
        'value_bool',
        'value_date',
        'verification_level',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:4',
            'value_bool' => 'boolean',
            'value_date' => 'date',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'option_id');
    }

    /**
     * El valor "crudo" en la columna correcta según attributes.data_type, sin que quien
     * llame tenga que saber cuál de las 4 columnas usar.
     */
    public function getValueAttribute(): mixed
    {
        return match ($this->attribute?->data_type) {
            'number' => $this->value_number,
            'boolean' => $this->value_bool,
            'date' => $this->value_date,
            'select' => $this->option?->value ?? $this->value_text,
            default => $this->value_text,
        };
    }
}
