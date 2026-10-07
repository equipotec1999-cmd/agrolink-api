<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Modelo
{
    protected $table = 'conversaciones';

    protected $fillable = [
        'publicacion_id',
        'comprador_id',
        'vendedor_id',
        'ultimo_mensaje_en',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_mensaje_en' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'publicacion_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comprador_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversacion_id')->orderBy('creado_en');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversacion_id')->latestOfMany('id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'conversacion_id');
    }
}
