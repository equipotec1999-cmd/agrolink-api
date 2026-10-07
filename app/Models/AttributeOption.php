<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeOption extends Modelo
{
    protected $table = 'opciones_atributo';

    protected $fillable = [
        'atributo_id',
        'valor',
        'orden',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'atributo_id');
    }
}
