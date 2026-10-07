<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Modelo
{
    use SoftDeletes;

    protected $table = 'publicaciones';

    const DELETED_AT = 'eliminado_en';

    protected $fillable = [
        'usuario_id',
        'tipo_producto_id',
        'predio_id',
        'titulo',
        'slug',
        'descripcion',
        'precio',
        'tipo_precio',
        'moneda',
        'cantidad',
        'unidad',
        'modalidad_venta',
        'negociable',
        'estatus',
        'estatus_moderacion',
        'motivo_moderacion',
        'atributos_cache',
        'publicado_en',
        'vence_en',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'cantidad' => 'decimal:2',
            'negociable' => 'boolean',
            'atributos_cache' => 'array',
            'publicado_en' => 'datetime',
            'vence_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'tipo_producto_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'predio_id');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ListingAttributeValue::class, 'publicacion_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class, 'publicacion_id')->orderBy('posicion');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ListingDocument::class, 'publicacion_id');
    }

    public function location(): HasOne
    {
        return $this->hasOne(ListingLocation::class, 'publicacion_id');
    }

    public function favoritedBy(): HasMany
    {
        return $this->hasMany(Favorite::class, 'publicacion_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'publicacion_id');
    }
}
