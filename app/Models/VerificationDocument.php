<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationDocument extends Modelo
{
    protected $table = 'documentos_verificacion';

    protected $fillable = ['solicitud_id', 'tipo', 'ruta_almacenamiento', 'nombre_original', 'tipo_mime', 'tamano', 'disco'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(VerificationRequest::class, 'solicitud_id');
    }
}
