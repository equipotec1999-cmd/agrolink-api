<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las bases creadas antes de traducir los estatus conservan restricciones CHECK y valores
 * por omisión en inglés. Esta migración las alinea con los valores en español sin perder datos.
 * Es idempotente: se puede correr en bases nuevas (ya en español) sin efecto.
 */
return new class extends Migration
{
    private const MAP = [
        'pending' => 'pendiente', 'approved' => 'aprobada', 'rejected' => 'rechazada',
        'draft' => 'borrador', 'in_review' => 'en_revision', 'published' => 'publicada',
        'expired' => 'vencida', 'archived' => 'archivada',
        'sent' => 'enviada', 'accepted' => 'aceptada', 'countered' => 'contraoferta',
        'counter_offer' => 'contraoferta', 'open' => 'abierto', 'resolved' => 'resuelto',
        'offer_accepted' => 'oferta_aceptada', 'pending_payment' => 'pendiente_pago',
        'paid' => 'pagado', 'preparing_delivery' => 'preparando_entrega',
        'in_transit' => 'en_transito', 'delivered' => 'entregado', 'confirmed' => 'confirmado',
        'completed' => 'completado', 'dispute' => 'disputa', 'photo' => 'foto',
    ];

    /** tabla => [columna => [valores nuevos, default]] */
    private function columns(): array
    {
        return [
            'publicaciones' => [
                'estatus' => [['borrador', 'en_revision', 'publicada', 'rechazada', 'suspended', 'sold', 'vencida', 'archivada'], 'borrador'],
                'estatus_moderacion' => [['pendiente', 'aprobada', 'rechazada'], 'pendiente'],
            ],
            'documentos_publicacion' => [
                'estatus' => [['pendiente', 'verified', 'rechazada', 'vencida', 'not_applicable'], 'pendiente'],
            ],
            'ofertas' => [
                'estatus' => [['enviada', 'aceptada', 'rechazada', 'contraoferta', 'cancelled', 'vencida'], 'enviada'],
            ],
            'reportes' => [
                'estatus' => [['abierto', 'investigating', 'resuelto', 'dismissed'], 'abierto'],
            ],
            'operaciones' => [
                'estatus' => [['oferta_aceptada', 'pendiente_pago', 'pagado', 'preparando_entrega', 'en_transito', 'entregado', 'confirmado', 'completado', 'cancelado', 'disputa'], 'oferta_aceptada'],
            ],
        ];
    }

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS solicitudes_verificacion_una_pendiente');
        $this->translate('solicitudes_verificacion', 'estatus', 'pendiente');
        DB::statement("CREATE UNIQUE INDEX solicitudes_verificacion_una_pendiente ON solicitudes_verificacion (usuario_id) WHERE estatus = 'pendiente'");

        foreach ($this->columns() as $table => $cols) {
            foreach ($cols as $col => [$values, $default]) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_{$col}_check");
                $this->translate($table, $col, $default);
                $list = "'" . implode("','", $values) . "'";
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_{$col}_check CHECK ({$col} IN ({$list}))");
            }
        }
    }

    private function translate(string $table, string $col, string $default): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table($table)->where($col, $old)->update([$col => $new]);
        }
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$col} SET DEFAULT '{$default}'");
    }

    public function down(): void
    {
        // Sin reversa: los valores en español son los definitivos.
    }
};
