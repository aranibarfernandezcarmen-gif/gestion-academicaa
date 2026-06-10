<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ========== CARRERA ==========
        
        // Función: Validar nombre de carrera único
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_validar_carrera_nombre_unico(nombre_val VARCHAR)
            RETURNS BOOLEAN AS $$
            BEGIN
                IF EXISTS(SELECT 1 FROM carrera WHERE nombre_carrera = nombre_val) THEN
                    RETURN FALSE;
                END IF;
                RETURN TRUE;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Función: Validar sigla única
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_validar_sigla_carrera_unica(sigla_val VARCHAR)
            RETURNS BOOLEAN AS $$
            BEGIN
                IF EXISTS(SELECT 1 FROM carrera WHERE sigla = sigla_val) THEN
                    RETURN FALSE;
                END IF;
                RETURN TRUE;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Trigger: BEFORE INSERT en CARRERA
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_carrera_insert()
            RETURNS TRIGGER AS $$
            DECLARE
                id_user INT;
            BEGIN
                IF NOT fn_validar_carrera_nombre_unico(NEW.nombre_carrera) THEN
                    RAISE EXCEPTION 'El nombre de carrera ya existe';
                END IF;
                IF NOT fn_validar_sigla_carrera_unica(NEW.sigla) THEN
                    RAISE EXCEPTION 'La sigla de carrera ya existe';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_carrera_insert
            BEFORE INSERT ON carrera
            FOR EACH ROW EXECUTE FUNCTION fn_carrera_insert()
        ");

        // Trigger: BEFORE UPDATE en CARRERA
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_carrera_update()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.nombre_carrera != OLD.nombre_carrera THEN
                    IF NOT fn_validar_carrera_nombre_unico(NEW.nombre_carrera) THEN
                        RAISE EXCEPTION 'El nuevo nombre de carrera ya existe';
                    END IF;
                END IF;
                IF NEW.sigla != OLD.sigla THEN
                    IF NOT fn_validar_sigla_carrera_unica(NEW.sigla) THEN
                        RAISE EXCEPTION 'La nueva sigla ya existe';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_carrera_update
            BEFORE UPDATE ON carrera
            FOR EACH ROW EXECUTE FUNCTION fn_carrera_update()
        ");

        // Trigger: BEFORE DELETE en CARRERA
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_carrera_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'carrera', 'DELETE', OLD.codigo::TEXT, OLD.nombre_carrera, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_carrera_delete
            BEFORE DELETE ON carrera
            FOR EACH ROW EXECUTE FUNCTION fn_carrera_delete()
        ");

        // ========== MATERIA ==========

        // Función: Validar nombre de materia único por carrera
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_validar_materia_nombre_unico(nombre_val VARCHAR, id_carrera_val BIGINT)
            RETURNS BOOLEAN AS $$
            BEGIN
                IF EXISTS(SELECT 1 FROM materia WHERE nombre_materia = nombre_val AND id_carrera = id_carrera_val) THEN
                    RETURN FALSE;
                END IF;
                RETURN TRUE;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Trigger: BEFORE INSERT en MATERIA
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_materia_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NOT fn_validar_materia_nombre_unico(NEW.nombre_materia, NEW.id_carrera) THEN
                    RAISE EXCEPTION 'La materia ya existe en esta carrera';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_materia_insert
            BEFORE INSERT ON materia
            FOR EACH ROW EXECUTE FUNCTION fn_materia_insert()
        ");

        // Trigger: BEFORE UPDATE en MATERIA
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_materia_update()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.nombre_materia != OLD.nombre_materia OR NEW.id_carrera != OLD.id_carrera THEN
                    IF NOT fn_validar_materia_nombre_unico(NEW.nombre_materia, NEW.id_carrera) THEN
                        RAISE EXCEPTION 'La nueva materia ya existe en esta carrera';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_materia_update
            BEFORE UPDATE ON materia
            FOR EACH ROW EXECUTE FUNCTION fn_materia_update()
        ");

        // Trigger: BEFORE DELETE en MATERIA
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_materia_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'materia', 'DELETE', OLD.codigo::TEXT, OLD.nombre_materia, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_materia_delete
            BEFORE DELETE ON materia
            FOR EACH ROW EXECUTE FUNCTION fn_materia_delete()
        ");

        // ========== EXAMEN ==========

        // Función: Validar examen no duplicado
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_validar_examen_duplicado(registro_postulante_val BIGINT, id_materia_val BIGINT)
            RETURNS BOOLEAN AS $$
            BEGIN
                IF EXISTS(SELECT 1 FROM examen WHERE registro_postulante = registro_postulante_val AND id_materia = id_materia_val AND estado != 'cancelado') THEN
                    RETURN FALSE;
                END IF;
                RETURN TRUE;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Trigger: AFTER INSERT en EXAMEN
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_examen_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.id_materia IS NOT NULL THEN
                    IF NOT fn_validar_examen_duplicado(NEW.registro_postulante, NEW.id_materia) THEN
                        RAISE EXCEPTION 'El postulante ya tiene un examen activo en esta materia';
                    END IF;
                END IF;
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'INSERT', NEW.codigo::TEXT, NULL, NEW.tipo_examen);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_examen_insert
            AFTER INSERT ON examen
            FOR EACH ROW EXECUTE FUNCTION fn_examen_insert()
        ");

        // Trigger: AFTER UPDATE en EXAMEN
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_examen_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'UPDATE', NEW.codigo::TEXT, OLD.estado, NEW.estado);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_examen_update
            AFTER UPDATE ON examen
            FOR EACH ROW EXECUTE FUNCTION fn_examen_update()
        ");

        // Trigger: BEFORE DELETE en EXAMEN
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_examen_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'DELETE', OLD.codigo::TEXT, OLD.tipo_examen, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_examen_delete
            BEFORE DELETE ON examen
            FOR EACH ROW EXECUTE FUNCTION fn_examen_delete()
        ");

        // ========== ESTADISTICA ==========

        // Función: Calcular estadísticas de una carrera
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_calcular_estadisticas_carrera(id_carrera_val BIGINT, periodo_val VARCHAR)
            RETURNS TABLE(total_inscritos INT, total_aprobados INT, total_reprobados INT, promedio NUMERIC) AS $$
            BEGIN
                RETURN QUERY
                SELECT 
                    COALESCE(COUNT(DISTINCT i.id), 0)::INT,
                    COALESCE(COUNT(DISTINCT CASE WHEN cal.promedio >= 51 THEN i.id END), 0)::INT,
                    COALESCE(COUNT(DISTINCT CASE WHEN cal.promedio < 51 THEN i.id END), 0)::INT,
                    COALESCE(AVG(cal.promedio), 0)::NUMERIC
                FROM inscripcion i
                JOIN grupo g ON i.id_grupo = g.codigo
                JOIN materia m ON g.id_materia = m.codigo
                LEFT JOIN calificacion cal ON i.id = cal.id_inscripcion
                WHERE m.id_carrera = id_carrera_val;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Trigger: AFTER INSERT en ESTADISTICA
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_estadistica_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'estadistica', 'INSERT', NEW.codigo::TEXT, NULL, 'Estadísticas calculadas');
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_estadistica_insert
            AFTER INSERT ON estadistica
            FOR EACH ROW EXECUTE FUNCTION fn_estadistica_insert()
        ");

        // Trigger: AFTER UPDATE en ESTADISTICA
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_estadistica_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'estadistica', 'UPDATE', NEW.codigo::TEXT, NEW.porcentaje_aprobacion::TEXT, NEW.porcentaje_aprobacion::TEXT);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_estadistica_update
            AFTER UPDATE ON estadistica
            FOR EACH ROW EXECUTE FUNCTION fn_estadistica_update()
        ");

        // ========== REPORTE ==========

        // Función: Generar ruta del reporte
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_generar_ruta_reporte(tipo_val VARCHAR, formato_val VARCHAR)
            RETURNS VARCHAR AS $$
            BEGIN
                RETURN CONCAT('reportes/', tipo_val, '_', TO_CHAR(NOW(), 'YYYYMMDD_HH24MISS'), '.', formato_val);
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Trigger: BEFORE INSERT en REPORTE
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_reporte_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.ruta_archivo IS NULL THEN
                    NEW.ruta_archivo := fn_generar_ruta_reporte(NEW.tipo_reporte, NEW.formato);
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_reporte_insert
            BEFORE INSERT ON reporte
            FOR EACH ROW EXECUTE FUNCTION fn_reporte_insert()
        ");

        // Trigger: BEFORE UPDATE en REPORTE
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_reporte_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE(NEW.id_persona, 1), 'reporte', 'UPDATE', NEW.codigo::TEXT, OLD.estado, NEW.estado);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_reporte_update
            BEFORE UPDATE ON reporte
            FOR EACH ROW EXECUTE FUNCTION fn_reporte_update()
        ");

        // Trigger: BEFORE DELETE en REPORTE
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_reporte_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (COALESCE(OLD.id_persona, 1), 'reporte', 'DELETE', OLD.codigo::TEXT, OLD.tipo_reporte, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trg_reporte_delete
            BEFORE DELETE ON reporte
            FOR EACH ROW EXECUTE FUNCTION fn_reporte_delete()
        ");

        // ========== PROCEDIMIENTOS ALMACENADOS ==========

        // PA: Crear carreras masivas
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_crear_carreras_masivas(
                json_carreras JSON
            )
            AS $$
            DECLARE
                v_carrera JSON;
                v_total INT := 0;
                v_exitosos INT := 0;
                v_fallidos INT := 0;
            BEGIN
                FOR v_carrera IN SELECT * FROM json_array_elements(json_carreras)
                LOOP
                    BEGIN
                        v_total := v_total + 1;
                        INSERT INTO carrera (sigla, nombre_carrera, facultad_sigla, descripcion, estado)
                        VALUES (
                            v_carrera->>'sigla',
                            v_carrera->>'nombre_carrera',
                            v_carrera->>'facultad_sigla',
                            v_carrera->>'descripcion',
                            COALESCE(v_carrera->>'estado', 'activa')
                        );
                        v_exitosos := v_exitosos + 1;
                    EXCEPTION WHEN OTHERS THEN
                        v_fallidos := v_fallidos + 1;
                    END;
                END LOOP;
                
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (1, 'carrera', 'BULK_INSERT', v_total::TEXT, NULL, CONCAT(v_exitosos, ' exitosos, ', v_fallidos, ' fallidos'));
            END;
            $$ LANGUAGE plpgsql;
        ");

        // PA: Crear materias masivas
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_crear_materias_masivas(
                json_materias JSON
            )
            AS $$
            DECLARE
                v_materia JSON;
                v_total INT := 0;
                v_exitosos INT := 0;
                v_fallidos INT := 0;
            BEGIN
                FOR v_materia IN SELECT * FROM json_array_elements(json_materias)
                LOOP
                    BEGIN
                        v_total := v_total + 1;
                        INSERT INTO materia (sigla, nombre_materia, id_carrera, descripcion, creditos, horas_teorica, horas_practica, estado)
                        VALUES (
                            v_materia->>'sigla',
                            v_materia->>'nombre_materia',
                            (v_materia->>'id_carrera')::BIGINT,
                            v_materia->>'descripcion',
                            COALESCE((v_materia->>'creditos')::INT, 0),
                            COALESCE((v_materia->>'horas_teorica')::INT, 0),
                            COALESCE((v_materia->>'horas_practica')::INT, 0),
                            COALESCE(v_materia->>'estado', 'activa')
                        );
                        v_exitosos := v_exitosos + 1;
                    EXCEPTION WHEN OTHERS THEN
                        v_fallidos := v_fallidos + 1;
                    END;
                END LOOP;
                
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (1, 'materia', 'BULK_INSERT', v_total::TEXT, NULL, CONCAT(v_exitosos, ' exitosos, ', v_fallidos, ' fallidos'));
            END;
            $$ LANGUAGE plpgsql;
        ");

        // PA: Crear exámenes masivos
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_crear_examenes_masivos(
                json_examenes JSON
            )
            AS $$
            DECLARE
                v_examen JSON;
                v_total INT := 0;
                v_exitosos INT := 0;
                v_fallidos INT := 0;
            BEGIN
                FOR v_examen IN SELECT * FROM json_array_elements(json_examenes)
                LOOP
                    BEGIN
                        v_total := v_total + 1;
                        INSERT INTO examen (fecha_examen, id_materia, registro_postulante, tipo_examen, aula_examen, hora_inicio, hora_fin, puntaje_maximo, estado)
                        VALUES (
                            (v_examen->>'fecha_examen')::DATE,
                            (v_examen->>'id_materia')::BIGINT,
                            (v_examen->>'registro_postulante')::BIGINT,
                            COALESCE(v_examen->>'tipo_examen', 'parcial'),
                            v_examen->>'aula_examen',
                            (v_examen->>'hora_inicio')::TIME,
                            (v_examen->>'hora_fin')::TIME,
                            COALESCE((v_examen->>'puntaje_maximo')::NUMERIC, 100),
                            COALESCE(v_examen->>'estado', 'programado')
                        );
                        v_exitosos := v_exitosos + 1;
                    EXCEPTION WHEN OTHERS THEN
                        v_fallidos := v_fallidos + 1;
                    END;
                END LOOP;
                
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (1, 'examen', 'BULK_INSERT', v_total::TEXT, NULL, CONCAT(v_exitosos, ' exitosos, ', v_fallidos, ' fallidos'));
            END;
            $$ LANGUAGE plpgsql;
        ");

        // PA: Calcular estadísticas de período
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_calcular_estadisticas_periodo(
                periodo_inicio DATE,
                periodo_fin DATE
            )
            AS $$
            DECLARE
                v_carrera RECORD;
                v_stats RECORD;
            BEGIN
                FOR v_carrera IN SELECT DISTINCT codigo FROM carrera WHERE estado = 'activa'
                LOOP
                    FOR v_stats IN SELECT * FROM fn_calcular_estadisticas_carrera(v_carrera.codigo, TO_CHAR(NOW(), 'YYYY-MM'))
                    LOOP
                        INSERT INTO estadistica (id_carrera, periodo_academico, total_inscritos, total_aprobados, total_reprobados, promedio_ponderado)
                        VALUES (v_carrera.codigo, TO_CHAR(NOW(), 'YYYY-MM'), v_stats.total_inscritos, v_stats.total_aprobados, v_stats.total_reprobados, v_stats.promedio)
                        ON CONFLICT DO NOTHING;
                    END LOOP;
                END LOOP;
                
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (1, 'estadistica', 'BULK_UPDATE', TO_CHAR(NOW(), 'YYYY-MM')::TEXT, NULL, 'Estadísticas calculadas para período');
            END;
            $$ LANGUAGE plpgsql;
        ");

        // PA: Generar reporte
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_generar_reporte(
                p_tipo_reporte VARCHAR,
                p_id_persona BIGINT,
                p_filtros JSON DEFAULT '{}'::JSON
            )
            AS $$
            DECLARE
                v_count INT;
            BEGIN
                SELECT COUNT(*) INTO v_count FROM persona WHERE id = p_id_persona;
                
                IF v_count = 0 THEN
                    RAISE EXCEPTION 'La persona no existe';
                END IF;
                
                INSERT INTO reporte (tipo_reporte, id_persona, formato, estado, filtros, cantidad_registros)
                VALUES (
                    p_tipo_reporte,
                    p_id_persona,
                    'pdf',
                    'generado',
                    p_filtros,
                    0
                );
                
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES (p_id_persona, 'reporte', 'INSERT', p_tipo_reporte, NULL, 'Reporte generado');
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        // Eliminar Triggers
        DB::statement("DROP TRIGGER IF EXISTS trg_carrera_insert ON carrera");
        DB::statement("DROP TRIGGER IF EXISTS trg_carrera_update ON carrera");
        DB::statement("DROP TRIGGER IF EXISTS trg_carrera_delete ON carrera");
        DB::statement("DROP TRIGGER IF EXISTS trg_materia_insert ON materia");
        DB::statement("DROP TRIGGER IF EXISTS trg_materia_update ON materia");
        DB::statement("DROP TRIGGER IF EXISTS trg_materia_delete ON materia");
        DB::statement("DROP TRIGGER IF EXISTS trg_examen_insert ON examen");
        DB::statement("DROP TRIGGER IF EXISTS trg_examen_update ON examen");
        DB::statement("DROP TRIGGER IF EXISTS trg_examen_delete ON examen");
        DB::statement("DROP TRIGGER IF EXISTS trg_estadistica_insert ON estadistica");
        DB::statement("DROP TRIGGER IF EXISTS trg_estadistica_update ON estadistica");
        DB::statement("DROP TRIGGER IF EXISTS trg_reporte_insert ON reporte");
        DB::statement("DROP TRIGGER IF EXISTS trg_reporte_update ON reporte");
        DB::statement("DROP TRIGGER IF EXISTS trg_reporte_delete ON reporte");

        // Eliminar Funciones
        DB::statement("DROP FUNCTION IF EXISTS fn_carrera_insert()");
        DB::statement("DROP FUNCTION IF EXISTS fn_carrera_update()");
        DB::statement("DROP FUNCTION IF EXISTS fn_carrera_delete()");
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_carrera_nombre_unico(VARCHAR)");
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_sigla_carrera_unica(VARCHAR)");
        DB::statement("DROP FUNCTION IF EXISTS fn_materia_insert()");
        DB::statement("DROP FUNCTION IF EXISTS fn_materia_update()");
        DB::statement("DROP FUNCTION IF EXISTS fn_materia_delete()");
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_materia_nombre_unico(VARCHAR, BIGINT)");
        DB::statement("DROP FUNCTION IF EXISTS fn_examen_insert()");
        DB::statement("DROP FUNCTION IF EXISTS fn_examen_update()");
        DB::statement("DROP FUNCTION IF EXISTS fn_examen_delete()");
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_examen_duplicado(BIGINT, BIGINT)");
        DB::statement("DROP FUNCTION IF EXISTS fn_estadistica_insert()");
        DB::statement("DROP FUNCTION IF EXISTS fn_estadistica_update()");
        DB::statement("DROP FUNCTION IF EXISTS fn_calcular_estadisticas_carrera(BIGINT, VARCHAR)");
        DB::statement("DROP FUNCTION IF EXISTS fn_reporte_insert()");
        DB::statement("DROP FUNCTION IF EXISTS fn_reporte_update()");
        DB::statement("DROP FUNCTION IF EXISTS fn_reporte_delete()");
        DB::statement("DROP FUNCTION IF EXISTS fn_generar_ruta_reporte(VARCHAR, VARCHAR)");

        // Eliminar Procedimientos
        DB::statement("DROP PROCEDURE IF EXISTS sp_crear_carreras_masivas(JSON)");
        DB::statement("DROP PROCEDURE IF EXISTS sp_crear_materias_masivas(JSON)");
        DB::statement("DROP PROCEDURE IF EXISTS sp_crear_examenes_masivos(JSON)");
        DB::statement("DROP PROCEDURE IF EXISTS sp_calcular_estadisticas_periodo(DATE, DATE)");
        DB::statement("DROP PROCEDURE IF EXISTS sp_generar_reporte(VARCHAR, BIGINT, JSON)");
    }
};
