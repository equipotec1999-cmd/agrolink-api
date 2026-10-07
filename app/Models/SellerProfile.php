<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Modelo
{
    protected $table = 'perfiles_vendedor';

    protected $primaryKey = 'usuario_id';

    public $incrementing = false;

    protected $fillable = [
        'usuario_id',
        'nombre_negocio',
        'verificado',
        'verificado_en',
        'operaciones_completadas',
        'operaciones_canceladas',
        'calificacion_exactitud',
        'calificacion_cumplimiento',
        'calificacion_comunicacion',
        'minutos_respuesta_promedio',
    ];

    protected function casts(): array
    {
        return [
            'verificado' => 'boolean',
            'verificado_en' => 'datetime',
            'calificacion_exactitud' => 'float',
            'calificacion_cumplimiento' => 'float',
            'calificacion_comunicacion' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
