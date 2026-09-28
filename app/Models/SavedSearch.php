<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'query',
        'notify_on_match',
        'notify_on_price_change',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'query' => 'array',
            'notify_on_match' => 'boolean',
            'notify_on_price_change' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
