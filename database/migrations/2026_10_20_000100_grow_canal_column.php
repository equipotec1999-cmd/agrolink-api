<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Ampliar `canal` para que acepte 'password_reset' (14 caracteres). */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE codigos_verificacion_contacto ALTER COLUMN canal TYPE varchar(30)");
        }
    }

    public function down(): void
    {
    }
};
