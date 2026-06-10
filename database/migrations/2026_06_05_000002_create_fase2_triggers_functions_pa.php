<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fase 2: USUARIO, PERSONA, ASISTENCIA, ROL_GRUPO_PRIVILEGIO
     * Triggers, Funciones y Procedimientos Almacenados
     */
    public function up(): void
    {
        // =========================================================================
        // FUNCIONES AUXILIARES
        // =========================================================================

        /**
         * fn_validar_email_unico(email_val)
         * Verifica si un email ya existe en la tabla users
         */
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_email_unico(VARCHAR) CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_email_unico(TEXT) CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_email_unico(CHARACTER VARYING) CASCADE");
        DB::statement("
            CREATE FUNCTION fn_validar_email_unico(email_val VARCHAR)
            RETURNS BOOLEAN AS $$
            DECLARE
                count_email INT;
            BEGIN
                SELECT COUNT(*) INTO count_email FROM users WHERE email = email_val;
                RETURN count_email = 0;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_validar_ci_no_duplicado(ci_val)
         * Verifica si un CI ya existe en la tabla persona
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_validar_ci_no_duplicado(ci_val VARCHAR)
            RETURNS BOOLEAN AS $$
            DECLARE
                count_ci INT;
            BEGIN
                SELECT COUNT(*) INTO count_ci FROM persona WHERE ci = ci_val;
                RETURN count_ci = 0;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_calcular_porcentaje_asistencia(postulante_id, periodo_inicio DATE, periodo_fin DATE)
         * Calcula el porcentaje de asistencia de un postulante en un período
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_calcular_porcentaje_asistencia(
                postulante_id BIGINT,
                periodo_inicio DATE,
                periodo_fin DATE
            )
            RETURNS DECIMAL(5,2) AS $$
            DECLARE
                total_clases INT;
                total_asistencias INT;
                porcentaje DECIMAL(5,2);
            BEGIN
                -- Contar total de clases en el período (asumiendo clases diarias)
                SELECT CAST(EXTRACT(DAY FROM (periodo_fin - periodo_inicio)) AS INT) + 1
                INTO total_clases;

                -- Contar asistencias del postulante en el período
                SELECT COUNT(*) INTO total_asistencias
                FROM asistencia
                WHERE registro_postulante = postulante_id
                  AND fecha >= periodo_inicio
                  AND fecha <= periodo_fin;

                -- Calcular porcentaje
                IF total_clases > 0 THEN
                    porcentaje := (CAST(total_asistencias AS DECIMAL) / CAST(total_clases AS DECIMAL)) * 100;
                ELSE
                    porcentaje := 0;
                END IF;

                RETURN porcentaje;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_validar_permiso_valido(codigo_cu_val VARCHAR)
         * Verifica si un código de CU es válido (CU01-CU50)
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_validar_permiso_valido(codigo_cu_val VARCHAR)
            RETURNS BOOLEAN AS $$
            BEGIN
                -- Validar formato CU + 2 dígitos
                RETURN codigo_cu_val ~ '^CU[0-9]{2}$';
            END;
            $$ LANGUAGE plpgsql;
        ");

        // =========================================================================
        // FUNCIONES TRIGGER - USUARIO (users)
        // =========================================================================

        /**
         * fn_usuario_insert()
         * Trigger BEFORE INSERT en tabla users
         * - Valida email único
         * - Hash password (Laravel maneja esto normalmente, pero añadimos validación)
         * - Registra en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Validar email único
                IF NOT fn_validar_email_unico(NEW.email) THEN
                    RAISE EXCEPTION 'Email % ya existe en el sistema', NEW.email;
                END IF;

                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario creado', COALESCE(NEW.id, 0), 'users', 'INSERT', NEW.id, NULL, NEW.email);

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_usuario_update()
         * Trigger BEFORE UPDATE en tabla users
         * - Valida cambios de email
         * - Registra cambios en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_update()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Si el email cambió, validar que sea único
                IF OLD.email != NEW.email AND NOT fn_validar_email_unico(NEW.email) THEN
                    RAISE EXCEPTION 'Email % ya existe en el sistema', NEW.email;
                END IF;

                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario actualizado', COALESCE(NEW.id, 0), 'users', 'UPDATE', NEW.id, OLD.email, NEW.email);

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_usuario_delete()
         * Trigger BEFORE DELETE en tabla users
         * - Registra eliminación en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario eliminado', COALESCE(OLD.id, 0), 'users', 'DELETE', OLD.id, OLD.email, NULL);

                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // =========================================================================
        // FUNCIONES TRIGGER - PERSONA
        // =========================================================================

        /**
         * fn_persona_insert()
         * Trigger BEFORE INSERT en tabla persona
         * - Valida CI no duplicado
         * - Registra en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_persona_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Validar CI no duplicado
                IF NOT fn_validar_ci_no_duplicado(NEW.ci) THEN
                    RAISE EXCEPTION 'CI % ya existe en el sistema', NEW.ci;
                END IF;

                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Persona creada', 0, 'persona', 'INSERT', NEW.id, NULL, 
                        CONCAT('CI: ', NEW.ci, ', Nombre: ', NEW.nombre, ' ', NEW.apellido));

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_persona_update()
         * Trigger BEFORE UPDATE en tabla persona
         * - Valida cambios de CI
         * - Registra cambios en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_persona_update()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Si el CI cambió, validar que sea único
                IF OLD.ci != NEW.ci AND NOT fn_validar_ci_no_duplicado(NEW.ci) THEN
                    RAISE EXCEPTION 'CI % ya existe en el sistema', NEW.ci;
                END IF;

                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Persona actualizada', 0, 'persona', 'UPDATE', NEW.id, OLD.ci, NEW.ci);

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_persona_delete()
         * Trigger BEFORE DELETE en tabla persona
         * - Registra eliminación en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_persona_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Persona eliminada', 0, 'persona', 'DELETE', OLD.id, 
                        CONCAT('CI: ', OLD.ci), NULL);

                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // =========================================================================
        // FUNCIONES TRIGGER - ASISTENCIA
        // =========================================================================

        /**
         * fn_asistencia_insert()
         * Trigger AFTER INSERT en tabla asistencia
         * - Calcula porcentaje de asistencia automático
         * - Registra en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_asistencia_insert()
            RETURNS TRIGGER AS $$
            DECLARE
                porcentaje DECIMAL(5,2);
            BEGIN
                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Asistencia registrada', 0, 'asistencia', 'INSERT', NEW.codigo,
                        NULL, CONCAT('Postulante: ', NEW.registro_postulante, ', Fecha: ', NEW.fecha));

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_asistencia_delete()
         * Trigger BEFORE DELETE en tabla asistencia
         * - Registra eliminación en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_asistencia_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Asistencia eliminada', 0, 'asistencia', 'DELETE', OLD.codigo,
                        CONCAT('Postulante: ', OLD.registro_postulante, ', Fecha: ', OLD.fecha), NULL);

                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // =========================================================================
        // FUNCIONES TRIGGER - ROL_GRUPO_PRIVILEGIO
        // =========================================================================

        /**
         * fn_rol_grupo_privilegio_insert()
         * Trigger BEFORE INSERT en tabla rol_grupo_privilegio
         * - Valida permiso válido (formato CU)
         * - Verifica rol_grupo existe
         * - Registra en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_rol_grupo_privilegio_insert()
            RETURNS TRIGGER AS $$
            DECLARE
                count_rol_grupo INT;
            BEGIN
                -- Validar formato de código CU
                IF NOT fn_validar_permiso_valido(NEW.codigo_cu) THEN
                    RAISE EXCEPTION 'Código de permiso % inválido (debe ser formato CU01-CU50)', NEW.codigo_cu;
                END IF;

                -- Verificar que el rol_grupo existe
                SELECT COUNT(*) INTO count_rol_grupo FROM rol_grupo WHERE codigo = NEW.codigo_rol_grupo;
                IF count_rol_grupo = 0 THEN
                    RAISE EXCEPTION 'Rol grupo % no existe', NEW.codigo_rol_grupo;
                END IF;

                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Privilegio asignado', 0, 'rol_grupo_privilegio', 'INSERT', NEW.codigo,
                        NULL, CONCAT('Rol: ', NEW.codigo_rol_grupo, ', CU: ', NEW.codigo_cu));

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * fn_rol_grupo_privilegio_delete()
         * Trigger BEFORE DELETE en tabla rol_grupo_privilegio
         * - Registra eliminación en bitácora
         */
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_rol_grupo_privilegio_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Registrar en bitácora
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Privilegio eliminado', 0, 'rol_grupo_privilegio', 'DELETE', OLD.codigo,
                        CONCAT('Rol: ', OLD.codigo_rol_grupo, ', CU: ', OLD.codigo_cu), NULL);

                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // =========================================================================
        // TRIGGERS - CREACIÓN
        // =========================================================================

        // Trigger usuario_insert
        DB::statement("DROP TRIGGER IF EXISTS trg_usuario_insert ON users CASCADE");
        DB::statement("
            CREATE TRIGGER trg_usuario_insert
            BEFORE INSERT ON users
            FOR EACH ROW
            EXECUTE FUNCTION fn_usuario_insert()
        ");

        // Trigger usuario_update
        DB::statement("DROP TRIGGER IF EXISTS trg_usuario_update ON users CASCADE");
        DB::statement("
            CREATE TRIGGER trg_usuario_update
            BEFORE UPDATE ON users
            FOR EACH ROW
            EXECUTE FUNCTION fn_usuario_update()
        ");

        // Trigger usuario_delete
        DB::statement("DROP TRIGGER IF EXISTS trg_usuario_delete ON users CASCADE");
        DB::statement("
            CREATE TRIGGER trg_usuario_delete
            BEFORE DELETE ON users
            FOR EACH ROW
            EXECUTE FUNCTION fn_usuario_delete()
        ");

        // Trigger persona_insert
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_insert ON persona CASCADE");
        DB::statement("
            CREATE TRIGGER trg_persona_insert
            BEFORE INSERT ON persona
            FOR EACH ROW
            EXECUTE FUNCTION fn_persona_insert()
        ");

        // Trigger persona_update
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_update ON persona CASCADE");
        DB::statement("
            CREATE TRIGGER trg_persona_update
            BEFORE UPDATE ON persona
            FOR EACH ROW
            EXECUTE FUNCTION fn_persona_update()
        ");

        // Trigger persona_delete
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_delete ON persona CASCADE");
        DB::statement("
            CREATE TRIGGER trg_persona_delete
            BEFORE DELETE ON persona
            FOR EACH ROW
            EXECUTE FUNCTION fn_persona_delete()
        ");

        // Trigger asistencia_insert
        DB::statement("DROP TRIGGER IF EXISTS trg_asistencia_insert ON asistencia CASCADE");
        DB::statement("
            CREATE TRIGGER trg_asistencia_insert
            BEFORE INSERT ON asistencia
            FOR EACH ROW
            EXECUTE FUNCTION fn_asistencia_insert()
        ");

        // Trigger asistencia_delete
        DB::statement("DROP TRIGGER IF EXISTS trg_asistencia_delete ON asistencia CASCADE");
        DB::statement("
            CREATE TRIGGER trg_asistencia_delete
            BEFORE DELETE ON asistencia
            FOR EACH ROW
            EXECUTE FUNCTION fn_asistencia_delete()
        ");

        // Trigger rol_grupo_privilegio_insert
        DB::statement("DROP TRIGGER IF EXISTS trg_rol_grupo_privilegio_insert ON rol_grupo_privilegio CASCADE");
        DB::statement("
            CREATE TRIGGER trg_rol_grupo_privilegio_insert
            BEFORE INSERT ON rol_grupo_privilegio
            FOR EACH ROW
            EXECUTE FUNCTION fn_rol_grupo_privilegio_insert()
        ");

        // Trigger rol_grupo_privilegio_delete
        DB::statement("DROP TRIGGER IF EXISTS trg_rol_grupo_privilegio_delete ON rol_grupo_privilegio CASCADE");
        DB::statement("
            CREATE TRIGGER trg_rol_grupo_privilegio_delete
            BEFORE DELETE ON rol_grupo_privilegio
            FOR EACH ROW
            EXECUTE FUNCTION fn_rol_grupo_privilegio_delete()
        ");

        // =========================================================================
        // PROCEDIMIENTOS ALMACENADOS
        // =========================================================================

        /**
         * sp_crear_usuarios_masivos(json_usuarios TEXT)
         * Crea múltiples usuarios desde JSON
         * Entrada: [{"name":"Juan","email":"juan@example.com","password":"hash123"}]
         * Retorna: total_insertados, exitosos, fallidos
         */
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_crear_usuarios_masivos(
                IN json_usuarios TEXT,
                OUT total_insertados INT,
                OUT exitosos INT,
                OUT fallidos INT
            ) AS $$
            DECLARE
                usuario_obj JSONB;
                user_name VARCHAR;
                user_email VARCHAR;
                user_password VARCHAR;
                v_error TEXT;
            BEGIN
                total_insertados := 0;
                exitosos := 0;
                fallidos := 0;

                -- Recorrer cada usuario en el JSON
                FOR usuario_obj IN SELECT jsonb_array_elements(json_usuarios::JSONB)
                LOOP
                    BEGIN
                        user_name := usuario_obj->>'name';
                        user_email := usuario_obj->>'email';
                        user_password := usuario_obj->>'password';

                        -- Validar datos requeridos
                        IF user_name IS NULL OR user_email IS NULL OR user_password IS NULL THEN
                            RAISE EXCEPTION 'Faltan campos requeridos en usuario';
                        END IF;

                        -- Intentar insertar
                        INSERT INTO users (name, email, password, created_at, updated_at)
                        VALUES (user_name, user_email, user_password, NOW(), NOW());

                        exitosos := exitosos + 1;
                        total_insertados := total_insertados + 1;

                    EXCEPTION WHEN OTHERS THEN
                        fallidos := fallidos + 1;
                        total_insertados := total_insertados + 1;
                        -- Continuar con el siguiente usuario
                    END;
                END LOOP;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * sp_importar_personas_masivas(json_personas TEXT)
         * Importa múltiples personas desde JSON
         * Entrada: [{"ci":"1234567","nombre":"Juan","apellido":"Pérez",...}]
         * Retorna: total_importados, exitosos, fallidos
         */
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_importar_personas_masivas(
                IN json_personas TEXT,
                OUT total_importados INT,
                OUT exitosos INT,
                OUT fallidos INT
            ) AS $$
            DECLARE
                persona_obj JSONB;
                p_ci VARCHAR;
                p_nombre VARCHAR;
                p_apellido VARCHAR;
                p_fecha_nacimiento DATE;
                p_sexo VARCHAR;
                p_direccion VARCHAR;
                p_telefono VARCHAR;
                p_correo VARCHAR;
                p_ciudad VARCHAR;
            BEGIN
                total_importados := 0;
                exitosos := 0;
                fallidos := 0;

                -- Recorrer cada persona en el JSON
                FOR persona_obj IN SELECT jsonb_array_elements(json_personas::JSONB)
                LOOP
                    BEGIN
                        p_ci := persona_obj->>'ci';
                        p_nombre := persona_obj->>'nombre';
                        p_apellido := persona_obj->>'apellido';
                        p_fecha_nacimiento := (persona_obj->>'fecha_nacimiento')::DATE;
                        p_sexo := persona_obj->>'sexo';
                        p_direccion := persona_obj->>'direccion';
                        p_telefono := persona_obj->>'telefono';
                        p_correo := persona_obj->>'correo_electronico';
                        p_ciudad := persona_obj->>'ciudad';

                        -- Validar datos requeridos
                        IF p_ci IS NULL OR p_nombre IS NULL OR p_apellido IS NULL THEN
                            RAISE EXCEPTION 'Faltan campos requeridos en persona';
                        END IF;

                        -- Intentar insertar
                        INSERT INTO persona (ci, nombre, apellido, fecha_nacimiento, sexo, 
                                           direccion, telefono, correo_electronico, ciudad, 
                                           created_at, updated_at)
                        VALUES (p_ci, p_nombre, p_apellido, p_fecha_nacimiento, p_sexo,
                               p_direccion, p_telefono, p_correo, p_ciudad, NOW(), NOW());

                        exitosos := exitosos + 1;
                        total_importados := total_importados + 1;

                    EXCEPTION WHEN OTHERS THEN
                        fallidos := fallidos + 1;
                        total_importados := total_importados + 1;
                        -- Continuar con la siguiente persona
                    END;
                END LOOP;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * sp_calcular_asistencia_periodo(periodo_inicio DATE, periodo_fin DATE)
         * Calcula porcentaje de asistencia para todos los postulantes en un período
         * Retorna tabla con postulante_id, porcentaje_asistencia
         */
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_calcular_asistencia_periodo(
                IN periodo_inicio DATE,
                IN periodo_fin DATE
            ) AS $$
            BEGIN
                -- Crear tabla temporal si no existe
                CREATE TEMP TABLE IF NOT EXISTS temp_asistencia AS
                SELECT 
                    a.registro_postulante,
                    fn_calcular_porcentaje_asistencia(
                        a.registro_postulante,
                        periodo_inicio,
                        periodo_fin
                    ) AS porcentaje_asistencia
                FROM asistencia a
                WHERE a.fecha >= periodo_inicio
                  AND a.fecha <= periodo_fin
                GROUP BY a.registro_postulante;
            END;
            $$ LANGUAGE plpgsql;
        ");

        /**
         * sp_asignar_permisos_masivos(json_asignaciones TEXT)
         * Asigna múltiples permisos masivamente
         * Entrada: [{"codigo_rol_grupo":1,"codigo_cu":"CU01","descripcion_cu":"Gestionar Usuarios"}]
         * Retorna: total_asignados, exitosos, fallidos
         */
        DB::statement("
            CREATE OR REPLACE PROCEDURE sp_asignar_permisos_masivos(
                IN json_asignaciones TEXT,
                OUT total_asignados INT,
                OUT exitosos INT,
                OUT fallidos INT
            ) AS $$
            DECLARE
                asignacion_obj JSONB;
                a_rol_grupo BIGINT;
                a_codigo_cu VARCHAR;
                a_descripcion_cu VARCHAR;
                v_error TEXT;
            BEGIN
                total_asignados := 0;
                exitosos := 0;
                fallidos := 0;

                -- Recorrer cada asignación en el JSON
                FOR asignacion_obj IN SELECT jsonb_array_elements(json_asignaciones::JSONB)
                LOOP
                    BEGIN
                        a_rol_grupo := (asignacion_obj->>'codigo_rol_grupo')::BIGINT;
                        a_codigo_cu := asignacion_obj->>'codigo_cu';
                        a_descripcion_cu := asignacion_obj->>'descripcion_cu';

                        -- Validar datos requeridos
                        IF a_rol_grupo IS NULL OR a_codigo_cu IS NULL THEN
                            RAISE EXCEPTION 'Faltan campos requeridos en asignación';
                        END IF;

                        -- Intentar insertar o actualizar
                        INSERT INTO rol_grupo_privilegio (codigo_rol_grupo, codigo_cu, descripcion_cu, created_at, updated_at)
                        VALUES (a_rol_grupo, a_codigo_cu, a_descripcion_cu, NOW(), NOW())
                        ON CONFLICT (codigo_rol_grupo, codigo_cu) DO UPDATE
                        SET descripcion_cu = EXCLUDED.descripcion_cu,
                            updated_at = NOW();

                        exitosos := exitosos + 1;
                        total_asignados := total_asignados + 1;

                    EXCEPTION WHEN OTHERS THEN
                        fallidos := fallidos + 1;
                        total_asignados := total_asignados + 1;
                        -- Continuar con la siguiente asignación
                    END;
                END LOOP;
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    /**
     * Rollback: Elimina todos los triggers y funciones de Fase 2
     */
    public function down(): void
    {
        // Eliminar triggers
        DB::statement("DROP TRIGGER IF EXISTS trg_usuario_insert ON users CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_usuario_update ON users CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_usuario_delete ON users CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_insert ON persona CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_update ON persona CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_persona_delete ON persona CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_asistencia_insert ON asistencia CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_asistencia_delete ON asistencia CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_rol_grupo_privilegio_insert ON rol_grupo_privilegio CASCADE");
        DB::statement("DROP TRIGGER IF EXISTS trg_rol_grupo_privilegio_delete ON rol_grupo_privilegio CASCADE");

        // Eliminar funciones
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_email_unico(VARCHAR) CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_ci_no_duplicado(VARCHAR) CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_calcular_porcentaje_asistencia(BIGINT, DATE, DATE) CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_validar_permiso_valido(VARCHAR) CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_usuario_insert() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_usuario_update() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_usuario_delete() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_persona_insert() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_persona_update() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_persona_delete() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_asistencia_insert() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_asistencia_delete() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_rol_grupo_privilegio_insert() CASCADE");
        DB::statement("DROP FUNCTION IF EXISTS fn_rol_grupo_privilegio_delete() CASCADE");

        // Eliminar procedimientos almacenados
        DB::statement("DROP PROCEDURE IF EXISTS sp_crear_usuarios_masivos(TEXT) CASCADE");
        DB::statement("DROP PROCEDURE IF EXISTS sp_importar_personas_masivas(TEXT) CASCADE");
        DB::statement("DROP PROCEDURE IF EXISTS sp_calcular_asistencia_periodo(DATE, DATE) CASCADE");
        DB::statement("DROP PROCEDURE IF EXISTS sp_asignar_permisos_masivos(TEXT) CASCADE");
    }
};
