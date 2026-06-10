<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Función para insertar postulante
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_insert_postulante()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Nuevo postulante registrado: ' || COALESCE(NEW.registro, ''),
                    NOW(),
                    '0.0.0.0',
                    COALESCE(NEW.persona_id, 1)
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_insert_postulante ON postulante;
            CREATE TRIGGER bitacora_insert_postulante
            AFTER INSERT ON postulante
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_insert_postulante();
        SQL);

        // Función para actualizar postulante
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_update_postulante()
            RETURNS TRIGGER AS $$
            BEGIN
                IF OLD.estado_asignacion IS DISTINCT FROM NEW.estado_asignacion THEN
                    INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                    VALUES (
                        '[TRIGGER] Estado de postulante ' || COALESCE(NEW.registro, '') || ' cambió de ' || COALESCE(OLD.estado_asignacion, 'NULL') || ' a ' || COALESCE(NEW.estado_asignacion, 'NULL'),
                        NOW(),
                        '0.0.0.0',
                        COALESCE(NEW.persona_id, 1)
                    );
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_update_postulante ON postulante;
            CREATE TRIGGER bitacora_update_postulante
            AFTER UPDATE ON postulante
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_update_postulante();
        SQL);

        // Función para insertar calificación
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_insert_calificacion()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Calificación registrada - Notas: ' || COALESCE(NEW.nota1::text, '0') || ', ' || COALESCE(NEW.nota2::text, '0') || ', ' || COALESCE(NEW.nota3::text, '0'),
                    NOW(),
                    '0.0.0.0',
                    1
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_insert_calificacion ON calificacion;
            CREATE TRIGGER bitacora_insert_calificacion
            AFTER INSERT ON calificacion
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_insert_calificacion();
        SQL);

        // Función para actualizar calificación
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_update_calificacion()
            RETURNS TRIGGER AS $$
            BEGIN
                IF OLD.nota1 IS DISTINCT FROM NEW.nota1 OR OLD.nota2 IS DISTINCT FROM NEW.nota2 OR OLD.nota3 IS DISTINCT FROM NEW.nota3 THEN
                    INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                    VALUES (
                        '[TRIGGER] Calificación actualizada - De (' || OLD.nota1 || ',' || OLD.nota2 || ',' || OLD.nota3 || ') a (' || NEW.nota1 || ',' || NEW.nota2 || ',' || NEW.nota3 || ')',
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
            DROP TRIGGER IF EXISTS bitacora_update_calificacion ON calificacion;
            CREATE TRIGGER bitacora_update_calificacion
            AFTER UPDATE ON calificacion
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_update_calificacion();
        SQL);

        // Función para insertar pago
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_insert_pago()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Pago registrado - Monto: ' || COALESCE(NEW.monto::text, '0') || ', Estado: ' || COALESCE(NEW.estado_pago, 'PENDIENTE'),
                    NOW(),
                    '0.0.0.0',
                    COALESCE(NEW.persona_id, 1)
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_insert_pago ON pago;
            CREATE TRIGGER bitacora_insert_pago
            AFTER INSERT ON pago
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_insert_pago();
        SQL);

        // Función para actualizar pago
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_update_pago()
            RETURNS TRIGGER AS $$
            BEGIN
                IF OLD.estado_pago IS DISTINCT FROM NEW.estado_pago THEN
                    INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                    VALUES (
                        '[TRIGGER] Estado de pago cambió de ' || COALESCE(OLD.estado_pago, 'NULL') || ' a ' || COALESCE(NEW.estado_pago, 'NULL'),
                        NOW(),
                        '0.0.0.0',
                        COALESCE(NEW.persona_id, 1)
                    );
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_update_pago ON pago;
            CREATE TRIGGER bitacora_update_pago
            AFTER UPDATE ON pago
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_update_pago();
        SQL);

        // Función para insertar inscripción
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_insert_inscripcion()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Nueva inscripción realizada',
                    NOW(),
                    '0.0.0.0',
                    COALESCE(NEW.persona_id, 1)
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_insert_inscripcion ON inscripcion;
            CREATE TRIGGER bitacora_insert_inscripcion
            AFTER INSERT ON inscripcion
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_insert_inscripcion();
        SQL);

        // Función para insertar grupo
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_insert_grupo()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Nuevo grupo creado - Código: ' || COALESCE(NEW.codigo::text, ''),
                    NOW(),
                    '0.0.0.0',
                    1
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_insert_grupo ON grupo;
            CREATE TRIGGER bitacora_insert_grupo
            AFTER INSERT ON grupo
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_insert_grupo();
        SQL);

        // Función para eliminar postulante
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_delete_postulante()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Postulante eliminado: ' || COALESCE(OLD.registro, ''),
                    NOW(),
                    '0.0.0.0',
                    COALESCE(OLD.persona_id, 1)
                );
                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_delete_postulante ON postulante;
            CREATE TRIGGER bitacora_delete_postulante
            AFTER DELETE ON postulante
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_delete_postulante();
        SQL);
    }

    public function down(): void
    {
        // Eliminar triggers
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_insert_postulante ON postulante CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_update_postulante ON postulante CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_insert_calificacion ON calificacion CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_update_calificacion ON calificacion CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_insert_pago ON pago CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_update_pago ON pago CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_insert_inscripcion ON inscripcion CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_insert_grupo ON grupo CASCADE');
        DB::unprepared('DROP TRIGGER IF EXISTS bitacora_delete_postulante ON postulante CASCADE');

        // Eliminar funciones
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_insert_postulante() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_update_postulante() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_insert_calificacion() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_update_calificacion() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_insert_pago() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_update_pago() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_insert_inscripcion() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_insert_grupo() CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_delete_postulante() CASCADE');
    }
};
