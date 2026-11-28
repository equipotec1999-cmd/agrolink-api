<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Solicitud de destacado pagado (publicación o perfil). Moderación confirma el pago y la activa. */
class FeaturedRequest extends Model
{
    protected $table = 'solicitudes_destacado';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'usuario_id', 'tipo', 'publicacion_id', 'dias', 'monto', 'moneda', 'estatus', 'revisado_por', 'nota', 'activada_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'activada_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'publicacion_id');
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->tipo === 'perfil' ? 'profile' : 'listing',
            'listing_id' => $this->publicacion_id,
            'listing_title' => $this->listing?->titulo,
            'user_id' => $this->usuario_id,
            'user_name' => trim(($this->user?->nombre ?? '').' '.($this->user?->apellidos ?? '')),
            'days' => $this->dias,
            'amount' => (float) $this->monto,
            'currency' => $this->moneda,
            'status' => $this->estatus,
            'note' => $this->nota,
            'created_at' => $this->creado_en,
            'activated_at' => $this->activada_en,
        ];
    }
}
