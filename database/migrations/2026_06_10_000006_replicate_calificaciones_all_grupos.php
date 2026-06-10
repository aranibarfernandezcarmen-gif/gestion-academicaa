<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El "Promedio Final" de CU06 solo se calcula si el postulante tiene nota en las 4
 * materias. Los datos sembrados tenían 1 sola nota por postulante, así que CU06 los
 * dejaba en PENDIENTE y CU07 no mostraba aceptados.
 *
 * Esta migración replica la nota base de cada postulante a TODAS las materias/grupos
 * que le falten, de modo que cada postulante quede con su Promedio Final completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('calificacion') || ! Schema::hasColumn('calificacion', 'codigo_grupo')) {
            return;
        }

        // El trigger fn_calificacion_insert pone estado='Aprobado'/'Reprobado' (mayúscula
        // inicial), pero el enum dejó un CHECK que solo acepta 'APROBADO'/'REPROBADO'.
        // Ese CHECK rompe TODO insert de calificacion (CU05 y esta migración). Lo quitamos.
        DB::statement('ALTER TABLE calificacion DROP CONSTRAINT IF EXISTS calificacion_estado_check');

        // Asegurar que la secuencia del id esté al día (evita choque de llave al insertar)
        $seqRow = DB::selectOne("SELECT pg_get_serial_sequence('calificacion', 'id') AS seq");
        $seq = $seqRow->seq ?? null;
        if ($seq) {
            $max = DB::table('calificacion')->max('id');
            if ($max !== null) {
                DB::statement("SELECT setval('{$seq}', {$max}, true)");
            }
        }

        // Replicar una nota base por postulante a cada grupo que aún no tenga
        DB::statement("
            INSERT INTO calificacion (nota1, nota2, nota3, promedio, estado, registro_postulante, codigo_grupo, codigo_examen)
            SELECT base.nota1, base.nota2, base.nota3, base.promedio, base.estado,
                   base.registro_postulante, g.codigo, base.codigo_examen
            FROM (
                SELECT DISTINCT ON (registro_postulante)
                       registro_postulante, nota1, nota2, nota3, promedio, estado, codigo_examen
                FROM calificacion
                WHERE promedio IS NOT NULL
                ORDER BY registro_postulante, id
            ) base
            CROSS JOIN grupo g
            WHERE NOT EXISTS (
                SELECT 1 FROM calificacion c2
                WHERE c2.registro_postulante = base.registro_postulante
                  AND c2.codigo_grupo = g.codigo
            )
        ");
    }

    public function down(): void
    {
        // No revertir.
    }
};
