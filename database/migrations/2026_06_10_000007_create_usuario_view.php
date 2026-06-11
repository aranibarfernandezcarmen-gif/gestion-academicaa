<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Varios triggers de auditoría (fn_examen_insert, fn_calificacion_*, etc.) hacen
 * `SELECT id_persona FROM usuario LIMIT 1`, pero en este esquema NO existe la tabla
 * "usuario" (la tabla de personas es "persona"). Eso rompe inserts en examen,
 * calificacion, etc. (p.ej. registrar notas en CU05).
 *
 * En vez de reescribir decenas de funciones, creamos una VISTA "usuario" que expone
 * id_persona desde persona. Así todos esos triggers funcionan sin tocarlos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Solo crear la vista si no existe ya una tabla/vista llamada "usuario".
        DB::statement('CREATE OR REPLACE VIEW usuario AS SELECT id AS id_persona FROM persona');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS usuario');
    }
};
