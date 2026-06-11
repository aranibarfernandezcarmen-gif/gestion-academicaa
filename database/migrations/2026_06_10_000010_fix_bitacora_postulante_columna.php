<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los triggers de bitácora sobre 'postulante' usaban NEW.persona_id / OLD.persona_id,
 * pero la columna real en 'postulante' es 'id_persona'. Eso rompía:
 *   - UPDATE de postulante (CU08 asignar cupos, CU10, etc.) -> "record new has no field persona_id"
 *   - INSERT y DELETE de postulante por el mismo motivo.
 * Se recrean las tres funciones usando id_persona.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_bitacora_insert_postulante()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Nuevo postulante registrado: ' || COALESCE(NEW.registro, ''),
                    NOW(), '0.0.0.0', COALESCE(NEW.id_persona, 1)
                );
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE OR REPLACE FUNCTION fn_bitacora_update_postulante()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF OLD.estado_asignacion IS DISTINCT FROM NEW.estado_asignacion THEN
                    INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                    VALUES (
                        '[TRIGGER] Estado de postulante ' || COALESCE(NEW.registro, '') || ' cambió de ' || COALESCE(OLD.estado_asignacion, 'NULL') || ' a ' || COALESCE(NEW.estado_asignacion, 'NULL'),
                        NOW(), '0.0.0.0', COALESCE(NEW.id_persona, 1)
                    );
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE OR REPLACE FUNCTION fn_bitacora_delete_postulante()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Postulante eliminado: ' || COALESCE(OLD.registro, ''),
                    NOW(), '0.0.0.0', COALESCE(OLD.id_persona, 1)
                );
                RETURN OLD;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        // No revertir.
    }
};
