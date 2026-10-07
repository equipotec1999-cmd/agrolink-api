<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Modelo
{
    protected $table = 'perfiles';

    protected $primaryKey = 'usuario_id';

    public $incrementing = false;

    protected $fillable = [
        'usuario_id',
        'ruta_avatar',
        'biografia',
        'estado',
        'municipio',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
