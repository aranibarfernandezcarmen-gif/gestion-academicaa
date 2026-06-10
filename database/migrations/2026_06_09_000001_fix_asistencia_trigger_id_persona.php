<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // fn_asistencia_insert: cambiar id_persona 0 → NULL
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_asistencia_insert()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Asistencia registrada', NOW(), '0.0.0.0', NULL, 'asistencia', 'INSERT', NEW.codigo::TEXT,
                        NULL, CONCAT('Postulante: ', NEW.registro_postulante, ', Fecha: ', NEW.fecha));
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // fn_asistencia_delete: cambiar id_persona 0 → NULL
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_asistencia_delete()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Asistencia eliminada', NOW(), '0.0.0.0', NULL, 'asistencia', 'DELETE', OLD.codigo::TEXT,
                        CONCAT('Postulante: ', OLD.registro_postulante, ', Fecha: ', OLD.fecha), NULL);
                RETURN OLD;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        // Revertir a la versión con 0 (fallida)
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_asistencia_insert()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Asistencia registrada', NOW(), '0.0.0.0', 0, 'asistencia', 'INSERT', NEW.codigo::TEXT,
                        NULL, CONCAT('Postulante: ', NEW.registro_postulante, ', Fecha: ', NEW.fecha));
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }
};
