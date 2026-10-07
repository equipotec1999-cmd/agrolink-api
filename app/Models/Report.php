<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Modelo
{
    protected $table = 'reportes';

    protected $fillable = [
        'reportante_id',
        'reportable_tipo',
        'reportable_id',
        'motivo',
        'descripcion',
        'estatus',
        'resuelto_por',
        'resuelto_en',
        'nota_resolucion',
    ];

    protected function casts(): array
    {
        return [
            'resuelto_en' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportante_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo('reportable', 'reportable_tipo', 'reportable_id');
    }
}
