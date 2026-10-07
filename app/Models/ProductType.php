<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Modelo
{
    protected $table = 'tipos_producto';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'slug',
        'icono',
        'dias_vigencia_predeterminados',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'tipo_producto_id');
    }

    public function complianceRules(): HasMany
    {
        return $this->hasMany(ComplianceRule::class, 'tipo_producto_id');
    }

    /**
     * Atributos dinámicos que aplican a este tipo de producto, con las reglas del pivot
     * (product_type_attributes: is_required, is_filterable, sort_order, min/max_value).
     * Este es el corazón del catálogo EAV (Fase 1 §3).
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'tipo_producto_atributos', 'tipo_producto_id', 'atributo_id')
            ->withPivot(['es_obligatorio', 'es_filtrable', 'orden', 'valor_minimo', 'valor_maximo'])
            ->withTimestamps('creado_en', 'actualizado_en')
            ->orderByPivot('orden');
    }
}
