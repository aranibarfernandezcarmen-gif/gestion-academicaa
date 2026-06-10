<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix fn_carrera_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_carrera_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Carrera eliminada', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'carrera', 'DELETE', OLD.codigo::TEXT, OLD.nombre_carrera, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_materia_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_materia_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Materia eliminada', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'materia', 'DELETE', OLD.codigo::TEXT, OLD.nombre_materia, NULL);
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
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Examen creado', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'INSERT', NEW.codigo::TEXT, NULL, NEW.tipo_examen);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_examen_update
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_examen_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Examen actualizado', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'UPDATE', NEW.codigo::TEXT, OLD.estado, NEW.estado);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_examen_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_examen_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Examen eliminado', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'examen', 'DELETE', OLD.codigo::TEXT, OLD.tipo_examen, NULL);
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_estadistica_insert
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_estadistica_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Estadísticas creadas', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'estadistica', 'INSERT', NEW.codigo::TEXT, NULL, 'Estadísticas calculadas');
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_estadistica_update
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_estadistica_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Estadísticas actualizadas', COALESCE((SELECT id_persona FROM usuario LIMIT 1), 1), 'estadistica', 'UPDATE', NEW.codigo::TEXT, NEW.porcentaje_aprobacion::TEXT, NEW.porcentaje_aprobacion::TEXT);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_reporte_update
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_reporte_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Reporte actualizado', COALESCE(NEW.id_persona, 1), 'reporte', 'UPDATE', NEW.codigo::TEXT, OLD.estado, NEW.estado);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_reporte_delete
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_reporte_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Reporte eliminado', COALESCE(OLD.id_persona, 1), 'reporte', 'DELETE', OLD.codigo::TEXT, OLD.tipo_reporte, NULL);
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
