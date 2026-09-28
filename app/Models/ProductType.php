<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'icon',
        'default_expiry_days',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function complianceRules(): HasMany
    {
        return $this->hasMany(ComplianceRule::class);
    }

    /**
     * Atributos dinámicos que aplican a este tipo de producto, con las reglas del pivot
     * (product_type_attributes: is_required, is_filterable, sort_order, min/max_value).
     * Este es el corazón del catálogo EAV (Fase 1 §3).
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_type_attributes')
            ->withPivot(['is_required', 'is_filterable', 'sort_order', 'min_value', 'max_value'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
