<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // pgcrypto: gen_random_uuid() para notifications.id.
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');
        // postgis: geography(point,4326) en properties/listing_locations y búsqueda por distancia.
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        // unaccent: para que "habanero" encuentre "habanéro" en la búsqueda de texto.
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

        // unaccent() de la extensión es STABLE, no IMMUTABLE: Postgres no permite usarla
        // directamente en una expresión de índice. Este wrapper la vuelve IMMUTABLE
        // (comprobado contra Postgres real: sin esto, CREATE INDEX falla).
        // El segundo argumento debe convertirse explícitamente a regdictionary, y tanto
        // la función unaccent() como el diccionario deben ir calificados con el esquema
        // (public.unaccent): esta función SQL resuelve nombres no calificados con el
        // search_path de quien la EJECUTA, no de quien la crea, así que si esa conexión
        // no tiene "public" en su search_path, "unaccent(...)" simplemente no se
        // encuentra (confirmado con un migrate real contra Postgres 17).
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION immutable_unaccent(text)
            RETURNS text AS $$
              SELECT public.unaccent('public.unaccent'::regdictionary, $1)
            $$ LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS immutable_unaccent(text)');
        // Las extensiones no se desinstalan en down(): otras tablas dependen de ellas.
    }
};
