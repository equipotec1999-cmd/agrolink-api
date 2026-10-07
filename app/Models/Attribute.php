<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Modelo
{
    protected $table = 'atributos';

    protected $fillable = [
        'clave',
        'etiqueta',
        'tipo_dato',
        'unidad',
        'grupo',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'atributo_id')->orderBy('orden');
    }

    public function productTypes(): BelongsToMany
    {
        return $this->belongsToMany(ProductType::class, 'tipo_producto_atributos', 'atributo_id', 'tipo_producto_id')
            ->withPivot(['es_obligatorio', 'es_filtrable', 'orden', 'valor_minimo', 'valor_maximo'])
            ->withTimestamps('creado_en', 'actualizado_en');
    }
}
