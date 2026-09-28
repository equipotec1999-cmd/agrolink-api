<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingLocation extends Model
{
    protected $primaryKey = 'listing_id';

    public $incrementing = false;

    protected $fillable = [
        'listing_id',
        'property_id',
        'state',
        'municipality',
        'postal_code',
        'exact_location',
        'approx_location',
    ];

    // exact_location NUNCA debe salir en un Resource público (Fase 1 §6): eso se filtra
    // explícitamente en ListingResource, no aquí en el modelo.

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
