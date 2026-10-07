<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Modelo
{
    protected $table = 'mensajes';

    protected $fillable = [
        'conversacion_id',
        'remitente_id',
        'oferta_id',
        'cuerpo',
        'leido_en',
    ];

    protected function casts(): array
    {
        return [
            'leido_en' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversacion_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'oferta_id');
    }
}
