<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceRule extends Modelo
{
    protected $table = 'reglas_cumplimiento';

    protected $fillable = [
        'tipo_producto_id',
        'categoria_id',
        'titulo',
        'descripcion',
        'documento_sugerido',
        'documento_requerido',
        'nombre_fuente',
        'url_fuente',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'documento_sugerido' => 'boolean',
            'documento_requerido' => 'boolean',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'tipo_producto_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }
}
