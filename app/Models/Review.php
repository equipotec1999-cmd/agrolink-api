<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Modelo
{
    protected $table = 'resenas';

    protected $fillable = [
        'operacion_id',
        'autor_id',
        'evaluado_id',
        'exactitud',
        'cumplimiento',
        'comunicacion',
        'pago',
        'recepcion',
        'comentario',
    ];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'operacion_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluado_id');
    }
}
