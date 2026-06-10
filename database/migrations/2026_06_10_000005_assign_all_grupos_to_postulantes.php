<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cada postulante del preuniversitario cursa TODAS las materias (4 grupos, uno por
 * materia), pero el backfill anterior asignó solo 1 grupo por postulante, así que
 * en su panel veían 1 solo curso. Esto asigna a cada postulante todos los grupos
 * existentes (producto cartesiano), sin duplicar pares ya presentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asignacion_grupo')) {
            DB::statement("
                INSERT INTO asignacion_grupo (postulante_id, grupo_codigo)
                SELECT p.id, g.codigo
                FROM postulante p
                CROSS JOIN grupo g
                WHERE NOT EXISTS (
                    SELECT 1 FROM asignacion_grupo a
                    WHERE a.postulante_id = p.id AND a.grupo_codigo = g.codigo
                )
            ");
        }
    }

    public function down(): void
    {
        // No revertir.
    }
};
