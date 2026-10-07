<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Modelo
{
    protected $table = 'bitacora_auditoria';

    const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'accion',
        'auditable_tipo',
        'auditable_id',
        'cambios',
        'direccion_ip',
        'agente_usuario',
    ];

    protected function casts(): array
    {
        return [
            'cambios' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
