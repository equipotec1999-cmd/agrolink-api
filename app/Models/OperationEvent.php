<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationEvent extends Modelo
{
    protected $table = 'eventos_operacion';

    const UPDATED_AT = null;

    protected $fillable = [
        'operacion_id',
        'estatus_anterior',
        'estatus_nuevo',
        'actor_id',
        'nota',
    ];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'operacion_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
