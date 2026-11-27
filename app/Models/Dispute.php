<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Modelo
{
    protected $table = 'disputas';

    protected $fillable = [
        'operacion_id',
        'abierta_por_id',
        'motivo',
        'descripcion',
        'respuesta',
        'respondida_en',
        'estatus_operacion',
        'estatus',
        'decision',
        'nota_resolucion',
        'resuelta_por_id',
        'resuelta_en',
    ];

    /** Motivos que la app ofrece (valor => texto). */
    public const MOTIVOS = [
        'no_llego' => 'El pedido no llegó',
        'producto_distinto' => 'No es lo que se acordó',
        'cantidad_incompleta' => 'Llegó incompleto',
        'producto_danado' => 'Llegó en mal estado',
        'pago_no_recibido' => 'No recibí el pago',
        'otro' => 'Otro problema',
    ];

    public const DECISIONES = ['favor_comprador', 'favor_vendedor', 'reanudar'];

    /** Estados en los que se puede abrir (antes de eso basta con cancelar). */
    public const ESTADOS_OPERACION = [
        'pendiente_pago', 'pagado', 'preparando_entrega', 'en_transito', 'entregado', 'completado',
    ];

    /** Días después de completada en los que aún se puede abrir. */
    public const DIAS_TRAS_COMPLETAR = 7;

    protected function casts(): array
    {
        return [
            'respondida_en' => 'datetime',
            'resuelta_en' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->estatus === 'abierta';
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'operacion_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abierta_por_id');
    }
}
