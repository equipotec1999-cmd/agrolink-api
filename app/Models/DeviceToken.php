<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Modelo
{
    protected $table = 'tokens_dispositivo';

    protected $fillable = ['usuario_id', 'token', 'plataforma', 'ultimo_uso_en'];

    protected function casts(): array
    {
        return ['ultimo_uso_en' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
