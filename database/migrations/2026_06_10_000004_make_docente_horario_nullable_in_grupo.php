<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CU09 "Calcular y Crear Grupos" crea los grupos automáticamente SIN docente ni
 * horario (esos se asignan después), pero grupo.codigo_docente y grupo.codigo_horario
 * eran NOT NULL, causando SQLSTATE[23502] al insertar.
 *
 * En PostgreSQL basta quitar el NOT NULL; la FK existente sigue válida y admite NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE grupo ALTER COLUMN codigo_docente DROP NOT NULL');
        DB::statement('ALTER TABLE grupo ALTER COLUMN codigo_horario DROP NOT NULL');
    }

    public function down(): void
    {
        // No revertir automáticamente: volver a NOT NULL fallaría si hay grupos
        // con docente/horario en null.
    }
};
