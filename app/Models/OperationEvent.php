<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'operation_id',
        'from_status',
        'to_status',
        'actor_id',
        'note',
    ];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
