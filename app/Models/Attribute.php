<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'attr_key',
        'label',
        'data_type',
        'unit',
        'attr_group',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('sort_order');
    }

    public function productTypes(): BelongsToMany
    {
        return $this->belongsToMany(ProductType::class, 'product_type_attributes')
            ->withPivot(['is_required', 'is_filterable', 'sort_order', 'min_value', 'max_value'])
            ->withTimestamps();
    }
}
