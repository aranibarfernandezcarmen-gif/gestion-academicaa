<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix fn_usuario_insert
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NOT fn_validar_email_unico(NEW.email) THEN
                    RAISE EXCEPTION 'Email % ya existe en el sistema', NEW.email;
                END IF;
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario creado', NOW(), '0.0.0.0', COALESCE(NEW.id, 0), 'users', 'INSERT', NEW.id::TEXT, NULL, NEW.email);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_usuario_update
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_update()
            RETURNS TRIGGER AS $$
            BEGIN
                IF OLD.email != NEW.email AND NOT fn_validar_email_unico(NEW.email) THEN
                    RAISE EXCEPTION 'Email % ya existe en el sistema', NEW.email;
                END IF;
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario actualizado', NOW(), '0.0.0.0', COALESCE(NEW.id, 0), 'users', 'UPDATE', NEW.id::TEXT, OLD.email, NEW.email);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_usuario_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_usuario_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Usuario eliminado', NOW(), '0.0.0.0', COALESCE(OLD.id, 0), 'users', 'DELETE', OLD.id::TEXT, OLD.email, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_asistencia_insert
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_asistencia_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Asistencia registrada', NOW(), '0.0.0.0', 0, 'asistencia', 'INSERT', NEW.codigo::TEXT,
                        NULL, CONCAT('Postulante: ', NEW.registro_postulante, ', Fecha: ', NEW.fecha));
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_asistencia_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_asistencia_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Asistencia eliminada', NOW(), '0.0.0.0', 0, 'asistencia', 'DELETE', OLD.codigo::TEXT,
                        CONCAT('Postulante: ', OLD.registro_postulante, ', Fecha: ', OLD.fecha), NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_rol_grupo_privilegio_insert
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_rol_grupo_privilegio_insert()
            RETURNS TRIGGER AS $$
            DECLARE
                count_rol_grupo INT;
            BEGIN
                IF NOT fn_validar_permiso_valido(NEW.codigo_cu) THEN
                    RAISE EXCEPTION 'Código de permiso % inválido (debe ser formato CU01-CU50)', NEW.codigo_cu;
                END IF;
                SELECT COUNT(*) INTO count_rol_grupo FROM rol_grupo WHERE codigo = NEW.codigo_rol_grupo;
                IF count_rol_grupo = 0 THEN
                    RAISE EXCEPTION 'Rol grupo % no existe', NEW.codigo_rol_grupo;
                END IF;
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Privilegio asignado', NOW(), '0.0.0.0', 0, 'rol_grupo_privilegio', 'INSERT', NEW.codigo::TEXT,
                        NULL, CONCAT('Rol: ', NEW.codigo_rol_grupo, ', CU: ', NEW.codigo_cu));
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_rol_grupo_privilegio_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_rol_grupo_privilegio_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Privilegio eliminado', NOW(), '0.0.0.0', 0, 'rol_grupo_privilegio', 'DELETE', OLD.codigo::TEXT,
                        CONCAT('Rol: ', OLD.codigo_rol_grupo, ', CU: ', OLD.codigo_cu), NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_carrera_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_carrera_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Carrera eliminada', NOW(), '0.0.0.0', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'carrera', 'DELETE', OLD.codigo::TEXT, OLD.nombre_carrera, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_materia_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_materia_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Materia eliminada', NOW(), '0.0.0.0', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'materia', 'DELETE', OLD.codigo::TEXT, OLD.nombre_materia, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_examen_insert
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_examen_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.id_materia IS NOT NULL THEN
                    IF NOT fn_validar_examen_duplicado(NEW.registro_postulante, NEW.id_materia) THEN
                        RAISE EXCEPTION 'El postulante ya tiene un examen activo en esta materia';
                    END IF;
                END IF;
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Examen creado', NOW(), '0.0.0.0', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'INSERT', NEW.codigo::TEXT, NULL, NEW.tipo_examen);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_examen_update
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_examen_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Examen actualizado', NOW(), '0.0.0.0', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'UPDATE', NEW.codigo::TEXT, OLD.estado, NEW.estado);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_examen_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_examen_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Examen eliminado', NOW(), '0.0.0.0', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'DELETE', OLD.codigo::TEXT, OLD.tipo_examen, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_estadistica_insert
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_estadistica_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Estadísticas creadas', NOW(), '0.0.0.0', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'estadistica', 'INSERT', NEW.codigo::TEXT, NULL, 'Estadísticas calculadas');
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_estadistica_update
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_estadistica_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Estadísticas actualizadas', NOW(), '0.0.0.0', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'estadistica', 'UPDATE', NEW.codigo::TEXT, NEW.porcentaje_aprobacion::TEXT, NEW.porcentaje_aprobacion::TEXT);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_reporte_update
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_reporte_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Reporte actualizado', NOW(), '0.0.0.0', COALESCE(NEW.id_persona, 1), 'reporte', 'UPDATE', NEW.codigo::TEXT, OLD.estado, NEW.estado);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_reporte_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_reporte_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Reporte eliminado', NOW(), '0.0.0.0', COALESCE(OLD.id_persona, 1), 'reporte', 'DELETE', OLD.codigo::TEXT, OLD.tipo_reporte, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        //
    }
};
