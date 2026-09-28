<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingMedia extends Model
{
    protected $fillable = [
        'listing_id',
        'type',
        'storage_path',
        'position',
        'width',
        'height',
        'duration_seconds',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
