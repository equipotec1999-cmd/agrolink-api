<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'avatar_path',
        'bio',
        'state',
        'municipality',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
