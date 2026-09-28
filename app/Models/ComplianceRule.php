<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceRule extends Model
{
    protected $fillable = [
        'product_type_id',
        'category_id',
        'title',
        'description',
        'document_suggested',
        'document_required',
        'source_name',
        'source_url',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'document_suggested' => 'boolean',
            'document_required' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
