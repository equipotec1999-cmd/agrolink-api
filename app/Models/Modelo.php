<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base de los modelos de dominio: las columnas de fecha de toda la base de datos
 * están en español (creado_en / actualizado_en), en vez de created_at / updated_at.
 * Los modelos sin updated_at (favoritos, bitácora...) sobreescriben UPDATED_AT = null.
 */
abstract class Modelo extends Model
{
    const CREATED_AT = 'creado_en';

    const UPDATED_AT = 'actualizado_en';
}
