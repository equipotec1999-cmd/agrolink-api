<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingLocation extends Modelo
{
    protected $table = 'ubicaciones_publicacion';

    protected $primaryKey = 'publicacion_id';

    public $incrementing = false;

    protected $fillable = [
        'publicacion_id',
        'predio_id',
        'estado',
        'municipio',
        'codigo_postal',
        'ubicacion_exacta',
        'ubicacion_aproximada',
    ];

    // exact_location NUNCA debe salir en un Resource público (Fase 1 §6): eso se filtra
    // explícitamente en ListingResource, no aquí en el modelo.

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'publicacion_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'predio_id');
    }
}
