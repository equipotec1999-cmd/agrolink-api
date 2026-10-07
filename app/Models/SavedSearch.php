<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Modelo
{
    protected $table = 'busquedas_guardadas';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'consulta',
        'avisar_coincidencia',
        'avisar_cambio_precio',
        'ultimo_aviso_en',
    ];

    protected function casts(): array
    {
        return [
            'consulta' => 'array',
            'avisar_coincidencia' => 'boolean',
            'avisar_cambio_precio' => 'boolean',
            'ultimo_aviso_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
