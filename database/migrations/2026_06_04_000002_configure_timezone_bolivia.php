<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Configurar zona horaria de Bolivia en PostgreSQL
     * Bolivia usa UTC-4 (America/La_Paz)
     */
    public function up(): void
    {
        // Crear función para obtener hora actual de Bolivia
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION get_now_bolivia()
            RETURNS TIMESTAMP WITH TIME ZONE AS $$
            BEGIN
                RETURN NOW() AT TIME ZONE 'America/La_Paz';
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Configurar zona horaria de la base de datos usando el nombre REAL de la BD
        // (en Render la base tiene un nombre con sufijo, no "gestion_academica").
        $database = DB::connection()->getDatabaseName();
        DB::unprepared("ALTER DATABASE \"{$database}\" SET timezone = 'America/La_Paz'");

        // Log opcional: solo si existe la persona 1 (evita violar la FK en una BD recién creada)
        if (DB::table('persona')->where('id', 1)->exists()) {
            DB::unprepared(
                "INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                 VALUES ('[SISTEMA] Zona horaria configurada a Bolivia (America/La_Paz)', NOW(), '0.0.0.0', 1)"
            );
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS get_now_bolivia() CASCADE');
        $database = DB::connection()->getDatabaseName();
        DB::unprepared("ALTER DATABASE \"{$database}\" RESET timezone");
    }
};
