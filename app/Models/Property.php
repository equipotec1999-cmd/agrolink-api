<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'state',
        'municipality',
        'postal_code',
        'exact_location',
        'approx_location',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    // NOTA: exact_location/approx_location son `geography(point,4326)`. No se castean a un
    // value object todavía (over-engineering para el MVP); las consultas espaciales se hacen
    // con DB::raw/ST_* en los Services que las necesiten (Fase 4 en adelante).

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}
