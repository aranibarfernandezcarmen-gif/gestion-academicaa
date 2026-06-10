<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // FASE 1 - TRIGGERS, FUNCIONES Y PA
        // =====================================================

        // ====================
        // 1. INSCRIPCION - Control de Cupos
        // ====================

        // Función: Validar y decrementar cupo
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_validar_decrementar_cupo(
                p_carrera_id BIGINT,
                p_gestion_id BIGINT
            ) RETURNS BOOLEAN AS $$
            DECLARE
                v_cupos_disponibles INTEGER;
            BEGIN
                SELECT cupos_disponibles INTO v_cupos_disponibles
                FROM cupo_carrera
                WHERE carrera_id = p_carrera_id
                  AND gestion_academica_id = p_gestion_id
                FOR UPDATE;

                IF v_cupos_disponibles IS NULL THEN
                    RETURN FALSE;
                END IF;

                IF v_cupos_disponibles <= 0 THEN
                    RETURN FALSE;
                END IF;

                UPDATE cupo_carrera
                SET cupos_disponibles = cupos_disponibles - 1
                WHERE carrera_id = p_carrera_id
                  AND gestion_academica_id = p_gestion_id;

                RETURN TRUE;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Trigger INSERT inscripción: Validar cupo y actualizar postulante
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_inscripcion_insert()
            RETURNS TRIGGER AS $$
            DECLARE
                v_carrera_id BIGINT;
            BEGIN
                SELECT carrera_asignada_id INTO v_carrera_id
                FROM postulante
                WHERE codigo_inscripcion = NEW.id;

                IF v_carrera_id IS NOT NULL THEN
                    IF fn_validar_decrementar_cupo(v_carrera_id, NEW.codigo_gestion_academica) THEN
                        INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                        VALUES (
                            '[TRIGGER] Inscripción registrada - Carrera: ' || v_carrera_id || ', Gestión: ' || NEW.codigo_gestion_academica,
                            NOW(),
                            '0.0.0.0',
                            1
                        );
                    ELSE
                        RAISE EXCEPTION 'Cupos disponibles insuficientes para la carrera';
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS inscripcion_insert ON inscripcion;
            CREATE TRIGGER inscripcion_insert
            BEFORE INSERT ON inscripcion
            FOR EACH ROW
            EXECUTE FUNCTION fn_inscripcion_insert();
        SQL);

        // Trigger DELETE inscripción: Incrementar cupo
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_inscripcion_delete()
            RETURNS TRIGGER AS $$
            DECLARE
                v_carrera_id BIGINT;
            BEGIN
                SELECT carrera_asignada_id INTO v_carrera_id
                FROM postulante
                WHERE codigo_inscripcion = OLD.id;

                IF v_carrera_id IS NOT NULL THEN
                    UPDATE cupo_carrera
                    SET cupos_disponibles = cupos_disponibles + 1
                    WHERE carrera_id = v_carrera_id
                      AND gestion_academica_id = OLD.codigo_gestion_academica;

                    INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                    VALUES (
                        '[TRIGGER] Inscripción eliminada - Cupo incrementado para carrera: ' || v_carrera_id,
                        NOW(),
                        '0.0.0.0',
                        1
                    );
                END IF;

                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS inscripcion_delete ON inscripcion;
            CREATE TRIGGER inscripcion_delete
            BEFORE DELETE ON inscripcion
            FOR EACH ROW
            EXECUTE FUNCTION fn_inscripcion_delete();
        SQL);

        // ====================
        // 2. CALIFICACION - Cálculo Automático de Promedio
        // ====================

        // Función: Calcular promedio
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_calcular_promedio(
                p_nota1 INTEGER,
                p_nota2 INTEGER,
                p_nota3 INTEGER
            ) RETURNS DECIMAL AS $$
            BEGIN
                RETURN (p_nota1 + p_nota2 + p_nota3)::DECIMAL / 3;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Trigger INSERT calificación: Calcular promedio y registrar en bitácora
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_calificacion_insert()
            RETURNS TRIGGER AS $$
            DECLARE
                v_promedio DECIMAL;
                v_estado VARCHAR(20);
            BEGIN
                v_promedio := fn_calcular_promedio(NEW.nota1, NEW.nota2, NEW.nota3);

                IF v_promedio >= 51 THEN
                    v_estado := 'Aprobado';
                ELSE
                    v_estado := 'Reprobado';
                END IF;

                UPDATE calificacion
                SET promedio = v_promedio, estado = v_estado
                WHERE id = NEW.id;

                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Calificación registrada - Promedio: ' || ROUND(v_promedio, 2) || ', Estado: ' || v_estado,
                    NOW(),
                    '0.0.0.0',
                    1
                );

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS calificacion_insert ON calificacion;
            CREATE TRIGGER calificacion_insert
            AFTER INSERT ON calificacion
            FOR EACH ROW
            EXECUTE FUNCTION fn_calificacion_insert();
        SQL);

        // Trigger UPDATE calificación: Recalcular promedio
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_calificacion_update()
            RETURNS TRIGGER AS $$
            DECLARE
                v_promedio DECIMAL;
                v_estado VARCHAR(20);
            BEGIN
                IF OLD.nota1 IS DISTINCT FROM NEW.nota1 
                   OR OLD.nota2 IS DISTINCT FROM NEW.nota2 
                   OR OLD.nota3 IS DISTINCT FROM NEW.nota3 THEN

                    v_promedio := fn_calcular_promedio(NEW.nota1, NEW.nota2, NEW.nota3);

                    IF v_promedio >= 51 THEN
                        v_estado := 'Aprobado';
                    ELSE
                        v_estado := 'Reprobado';
                    END IF;

                    NEW.promedio := v_promedio;
                    NEW.estado := v_estado;

                    INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                    VALUES (
                        '[TRIGGER] Calificación actualizada - Notas: (' || OLD.nota1 || ',' || OLD.nota2 || ',' || OLD.nota3 || ') → (' || NEW.nota1 || ',' || NEW.nota2 || ',' || NEW.nota3 || '), Promedio: ' || ROUND(v_promedio, 2),
                        NOW(),
                        '0.0.0.0',
                        1
                    );
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS calificacion_update ON calificacion;
            CREATE TRIGGER calificacion_update
            BEFORE UPDATE ON calificacion
            FOR EACH ROW
            EXECUTE FUNCTION fn_calificacion_update();
        SQL);

        // ====================
        // 3. CUPO_CARRERA - Validación y Control
        // ====================

        // Trigger UPDATE: Validar no sea negativo
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_cupo_carrera_update()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.cupos_disponibles < 0 THEN
                    RAISE EXCEPTION 'Los cupos disponibles no pueden ser negativos';
                END IF;

                IF NEW.cupos_disponibles > NEW.cupo_maximo THEN
                    RAISE EXCEPTION 'Los cupos disponibles no pueden exceder el cupo máximo';
                END IF;

                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Cupo carrera actualizado - Carrera: ' || NEW.carrera_id || ', Disponibles: ' || NEW.cupos_disponibles || '/' || NEW.cupo_maximo,
                    NOW(),
                    '0.0.0.0',
                    1
                );

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS cupo_carrera_update ON cupo_carrera;
            CREATE TRIGGER cupo_carrera_update
            BEFORE UPDATE ON cupo_carrera
            FOR EACH ROW
            EXECUTE FUNCTION fn_cupo_carrera_update();
        SQL);

        // ====================
        // 4. GRUPO - Validación de Conflictos
        // ====================

        // Función: Detectar conflictos de horario/aula
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_detectar_conflicto_grupo(
                p_codigo_horario BIGINT,
                p_codigo_aula_id BIGINT DEFAULT NULL
            ) RETURNS BOOLEAN AS $$
            DECLARE
                v_existe BOOLEAN;
            BEGIN
                SELECT EXISTS(
                    SELECT 1 FROM grupo g
                    WHERE g.codigo_horario = p_codigo_horario
                ) INTO v_existe;

                RETURN v_existe;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Trigger INSERT grupo: Validar conflictos
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_grupo_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                IF fn_detectar_conflicto_grupo(NEW.codigo_horario) THEN
                    RAISE EXCEPTION 'Conflicto de horario: Ya existe un grupo en este horario';
                END IF;

                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Nuevo grupo creado - ' || NEW.nombre_grupo || ', Capacidad: ' || NEW.capacidad_maxima,
                    NOW(),
                    '0.0.0.0',
                    NEW.codigo_docente
                );

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS grupo_insert ON grupo;
            CREATE TRIGGER grupo_insert
            BEFORE INSERT ON grupo
            FOR EACH ROW
            EXECUTE FUNCTION fn_grupo_insert();
        SQL);

        // ====================
        // 5. PAGO - Actualización de Estado de Postulante
        // ====================

        // Función: Validar pago
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_validar_pago(
                p_monto DECIMAL,
                p_fecha_pago DATE
            ) RETURNS BOOLEAN AS $$
            BEGIN
                IF p_monto <= 0 THEN
                    RETURN FALSE;
                END IF;

                IF p_fecha_pago > CURRENT_DATE THEN
                    RETURN FALSE;
                END IF;

                RETURN TRUE;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Trigger UPDATE pago: Actualizar estado de postulante
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_pago_update()
            RETURNS TRIGGER AS $$
            BEGIN
                IF OLD.estado_pago IS DISTINCT FROM NEW.estado_pago THEN
                    IF NEW.estado_pago = 'COMPLETADO' THEN
                        UPDATE postulante
                        SET estado_asignacion = 'Aceptado'
                        WHERE codigo_inscripcion IN (
                            SELECT id FROM inscripcion
                            WHERE codigo_pago = NEW.id
                        );

                        INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                        VALUES (
                            '[TRIGGER] Pago completado - Monto: ' || NEW.monto || ', Estado postulantes: Actualizado a Aceptado',
                            NOW(),
                            '0.0.0.0',
                            1
                        );
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS pago_update ON pago;
            CREATE TRIGGER pago_update
            AFTER UPDATE ON pago
            FOR EACH ROW
            EXECUTE FUNCTION fn_pago_update();
        SQL);

        // ====================
        // PROCEDIMIENTOS ALMACENADOS (PA)
        // ====================

        // PA 1: Inscripciones Masivas
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_procesar_inscripciones_masivas(
                p_gestion_academica_id BIGINT,
                OUT p_total_procesadas INTEGER,
                OUT p_exitosas INTEGER,
                OUT p_fallidas INTEGER
            ) AS $$
            DECLARE
                v_postulante RECORD;
            BEGIN
                p_total_procesadas := 0;
                p_exitosas := 0;
                p_fallidas := 0;

                FOR v_postulante IN
                    SELECT p.id, p.carrera_asignada_id
                    FROM postulante p
                    WHERE p.estado_asignacion = 'Pendiente'
                      AND p.carrera_asignada_id IS NOT NULL
                LOOP
                    BEGIN
                        INSERT INTO inscripcion (fecha_inscripcion, estado_pago, codigo_gestion_academica)
                        VALUES (CURRENT_DATE, 'PENDIENTE', p_gestion_academica_id);

                        UPDATE postulante SET codigo_inscripcion = LASTVAL()
                        WHERE id = v_postulante.id;

                        p_exitosas := p_exitosas + 1;
                    EXCEPTION WHEN OTHERS THEN
                        p_fallidas := p_fallidas + 1;
                    END;

                    p_total_procesadas := p_total_procesadas + 1;
                END LOOP;

                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[PA] Inscripciones masivas procesadas - Total: ' || p_total_procesadas || ', Exitosas: ' || p_exitosas || ', Fallidas: ' || p_fallidas,
                    NOW(),
                    '0.0.0.0',
                    1
                );
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // PA 2: Recalcular Promedios de Gestión
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
                    WHERE c.codigo_examen IN (
                        SELECT e.codigo FROM examen e
                        WHERE e.codigo_gestion_academica = p_gestion_academica_id
                    )
                LOOP
                    v_promedio := fn_calcular_promedio(
                        v_calificacion.nota1,
                        v_calificacion.nota2,
                        v_calificacion.nota3
                    );

                    IF v_promedio >= 51 THEN
                        v_estado := 'Aprobado';
                        p_aprobadas := p_aprobadas + 1;
                    ELSE
                        v_estado := 'Reprobado';
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
                    NOW(),
                    '0.0.0.0',
                    1
                );
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // PA 3: Procesar Pagos Pendientes
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_procesar_pagos_pendientes(
                OUT p_total_procesados INTEGER,
                OUT p_completados INTEGER,
                OUT p_fallidos INTEGER
            ) AS $$
            DECLARE
                v_pago RECORD;
            BEGIN
                p_total_procesados := 0;
                p_completados := 0;
                p_fallidos := 0;

                FOR v_pago IN
                    SELECT id, monto, fecha_pago
                    FROM pago
                    WHERE estado_pago = 'PENDIENTE'
                LOOP
                    BEGIN
                        IF fn_validar_pago(v_pago.monto, v_pago.fecha_pago) THEN
                            UPDATE pago
                            SET estado_pago = 'COMPLETADO'
                            WHERE id = v_pago.id;

                            p_completados := p_completados + 1;
                        ELSE
                            p_fallidos := p_fallidos + 1;
                        END IF;
                    EXCEPTION WHEN OTHERS THEN
                        p_fallidos := p_fallidos + 1;
                    END;

                    p_total_procesados := p_total_procesados + 1;
                END LOOP;

                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[PA] Pagos pendientes procesados - Total: ' || p_total_procesados || ', Completados: ' || p_completados || ', Fallidos: ' || p_fallidos,
                    NOW(),
                    '0.0.0.0',
                    1
                );
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // PA 4: Resetear Cupos por Gestión
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
                    SELECT id, codigo_carrera, cupo_maximo
                    FROM cupo_carrera
                    WHERE gestion_academica_id = p_gestion_academica_id
                LOOP
                    UPDATE cupo_carrera
                    SET cupos_disponibles = cupo_maximo
                    WHERE id = v_cupo.id;

                    p_total_carreras := p_total_carreras + 1;
                END LOOP;

                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[PA] Cupos reseteados para gestión: ' || p_gestion_academica_id || ', Total carreras: ' || p_total_carreras,
                    NOW(),
                    '0.0.0.0',
                    1
                );
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        // Eliminar triggers
        DB::unprepared('DROP TRIGGER IF EXISTS inscripcion_insert ON inscripcion CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS inscripcion_delete ON inscripcion CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS calificacion_insert ON calificacion CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS calificacion_update ON calificacion CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS cupo_carrera_update ON cupo_carrera CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS grupo_insert ON grupo CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS pago_update ON pago CASCADE');

        // Eliminar funciones
        DB::unprepared('DROP FUNCTION IF EXISTS fn_validar_decrementar_cupo(BIGINT, BIGINT) CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_inscripcion_insert() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_inscripcion_delete() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_calcular_promedio(INTEGER, INTEGER, INTEGER) CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_calificacion_insert() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_calificacion_update() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_cupo_carrera_update() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_detectar_conflicto_grupo(BIGINT, BIGINT) CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_grupo_insert() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_validar_pago(DECIMAL, DATE) CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_pago_update() CASCADE');

        // Eliminar procedimientos almacenados
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_procesar_inscripciones_masivas(BIGINT) CASCADE');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_recalcular_promedios_gestion(BIGINT) CASCADE');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_procesar_pagos_pendientes() CASCADE');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_resetear_cupos_gestion(BIGINT) CASCADE');
    }
};
