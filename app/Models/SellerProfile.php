<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'business_name',
        'is_verified',
        'verified_at',
        'completed_operations',
        'cancelled_operations',
        'rating_accuracy',
        'rating_fulfillment',
        'rating_communication',
        'avg_response_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'rating_accuracy' => 'float',
            'rating_fulfillment' => 'float',
            'rating_communication' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
