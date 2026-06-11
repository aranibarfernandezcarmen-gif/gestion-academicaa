<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Arreglo de Procedimientos Almacenados (auditoría):
 *  - sp_recalcular_promedios_gestion (lo llama CU06 "Recalcular"): filtraba por
 *    examen.codigo_gestion_academica (columna inexistente). Se filtra por la gestión
 *    de la inscripción del postulante (calificacion -> postulante -> inscripcion).
 *  - sp_resetear_cupos_gestion: usaba cupo_carrera.id / codigo_carrera (no existen);
 *    la PK es 'codigo'. Se corrige.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_recalcular_promedios_gestion(
                p_gestion_academica_id BIGINT,
                OUT p_total_calificaciones INTEGER,
                OUT p_aprobadas INTEGER,
                OUT p_reprobadas INTEGER
            ) AS $$
            DECLARE
                v_calificacion RECORD;
                v_promedio DECIMAL;
                v_estado VARCHAR(20);
            BEGIN
                p_total_calificaciones := 0;
                p_aprobadas := 0;
                p_reprobadas := 0;

                FOR v_calificacion IN
                    SELECT c.id, c.nota1, c.nota2, c.nota3
                    FROM calificacion c
                    JOIN postulante p ON p.id = c.registro_postulante
                    JOIN inscripcion i ON i.id = p.codigo_inscripcion
                    WHERE i.codigo_gestion_academica = p_gestion_academica_id
                LOOP
                    v_promedio := fn_calcular_promedio(
                        v_calificacion.nota1, v_calificacion.nota2, v_calificacion.nota3
                    );

                    IF v_promedio >= 51 THEN
                        v_estado := 'APROBADO';
                        p_aprobadas := p_aprobadas + 1;
                    ELSE
                        v_estado := 'REPROBADO';
                        p_reprobadas := p_reprobadas + 1;
                    END IF;

                    UPDATE calificacion
                    SET promedio = v_promedio, estado = v_estado
                    WHERE id = v_calificacion.id;

                    p_total_calificaciones := p_total_calificaciones + 1;
                END LOOP;

                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[PA] Promedios recalculados - Total: ' || p_total_calificaciones || ', Aprobadas: ' || p_aprobadas || ', Reprobadas: ' || p_reprobadas,
                    NOW(), '0.0.0.0', NULL
                );
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_resetear_cupos_gestion(
                p_gestion_academica_id BIGINT,
                OUT p_total_carreras INTEGER
            ) AS $$
            DECLARE
                v_cupo RECORD;
            BEGIN
                p_total_carreras := 0;

                FOR v_cupo IN
                    SELECT codigo, cupo_maximo
                    FROM cupo_carrera
                    WHERE gestion_academica_id = p_gestion_academica_id
                LOOP
                    UPDATE cupo_carrera
                    SET cupos_disponibles = v_cupo.cupo_maximo
                    WHERE codigo = v_cupo.codigo;

                    p_total_carreras := p_total_carreras + 1;
                END LOOP;

                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[PA] Cupos reseteados para gestión: ' || p_gestion_academica_id || ', Total carreras: ' || p_total_carreras,
                    NOW(), '0.0.0.0', NULL
                );
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        // No revertir.
    }
};
