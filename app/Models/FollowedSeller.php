<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowedSeller extends Modelo
{
    protected $table = 'vendedores_seguidos';

    const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'vendedor_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }
}
