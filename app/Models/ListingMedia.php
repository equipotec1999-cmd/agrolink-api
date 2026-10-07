<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingMedia extends Modelo
{
    protected $table = 'medios_publicacion';

    protected $fillable = [
        'publicacion_id',
        'tipo',
        'ruta_almacenamiento',
        'posicion',
        'ancho',
        'alto',
        'duracion_segundos',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'publicacion_id');
    }
}
