<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas que la app usa pero no existían en las migraciones del repo:
 *  - grupo.codigo_aula  (CU11 - recursos físicos / asignar aula a grupo)
 *  - asistencia.estado  (CU13 - asistencia: Presente/Ausente/Justificado)
 * Y un arreglo de dato: el grupo G3-FIS-2026 apuntaba a Matemáticas en vez de Física.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('grupo', 'codigo_aula')) {
            Schema::table('grupo', function (Blueprint $table) {
                $table->unsignedBigInteger('codigo_aula')->nullable();
            });
        }

        if (!Schema::hasColumn('asistencia', 'estado')) {
            Schema::table('asistencia', function (Blueprint $table) {
                $table->string('estado', 20)->nullable();
            });
        }

        // Dato: G3-FIS-2026 debe ser Física (materia 1), no Matemáticas (2)
        DB::table('grupo')
            ->where('nombre_grupo', 'like', 'G3-FIS%')
            ->where('codigo_materia', 2)
            ->update(['codigo_materia' => 1]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('grupo', 'codigo_aula')) {
            Schema::table('grupo', function (Blueprint $table) {
                $table->dropColumn('codigo_aula');
            });
        }
        if (Schema::hasColumn('asistencia', 'estado')) {
            Schema::table('asistencia', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }
    }
};
