<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Completa la traducción: tipo de medio y motivo de reporte (CHECK en inglés en bases viejas). */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE medios_publicacion DROP CONSTRAINT IF EXISTS medios_publicacion_tipo_check');
        DB::table('medios_publicacion')->where('tipo', 'photo')->update(['tipo' => 'foto']);
        DB::statement("ALTER TABLE medios_publicacion ALTER COLUMN tipo SET DEFAULT 'foto'");
        DB::statement("ALTER TABLE medios_publicacion ADD CONSTRAINT medios_publicacion_tipo_check CHECK (tipo IN ('foto','video'))");

        $map = [
            'fraud' => 'fraude', 'false_information' => 'informacion_falsa',
            'nonexistent_product' => 'producto_inexistente', 'suspicious_documentation' => 'documentacion_sospechosa',
            'duplicate_listing' => 'publicacion_duplicada', 'inappropriate_conduct' => 'conducta_inapropiada',
            'prohibited_product' => 'producto_no_permitido', 'other' => 'otro',
        ];
        DB::statement('ALTER TABLE reportes DROP CONSTRAINT IF EXISTS reportes_motivo_check');
        foreach ($map as $old => $new) {
            DB::table('reportes')->where('motivo', $old)->update(['motivo' => $new]);
        }
        $list = "'" . implode("','", array_values($map)) . "'";
        DB::statement("ALTER TABLE reportes ADD CONSTRAINT reportes_motivo_check CHECK (motivo IN ({$list}))");
    }

    public function down(): void
    {
    }
};
