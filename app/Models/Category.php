<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Modelo
{
    protected $table = 'categorias';

    protected $fillable = [
        'categoria_padre_id',
        'nombre',
        'slug',
        'clave_color',
        'icono',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_padre_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'categoria_padre_id');
    }

    public function productTypes(): HasMany
    {
        return $this->hasMany(ProductType::class, 'categoria_id');
    }
}
