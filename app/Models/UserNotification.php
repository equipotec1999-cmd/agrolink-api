<?php

namespace App\Models;

/**
 * Notificación dentro de la app (tabla `notificaciones`, canal "database" propio:
 * las columnas están en español, así que no usa el DatabaseNotification de Laravel).
 */
class UserNotification extends Modelo
{
    protected $table = 'notificaciones';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tipo',
        'notificable_tipo',
        'notificable_id',
        'datos',
        'creado_en',
        'leido_en',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'leido_en' => 'datetime',
        ];
    }
}
