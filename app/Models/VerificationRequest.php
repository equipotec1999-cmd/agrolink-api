<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationRequest extends Modelo
{
    protected $table = 'solicitudes_verificacion';

    protected $fillable = ['usuario_id', 'estatus', 'nombre_negocio', 'motivo_rechazo', 'revisado_por', 'revisado_en'];

    protected function casts(): array
    {
        return ['revisado_en' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class, 'solicitud_id');
    }
}
