<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix fn_persona_insert with ip_origen
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_persona_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Validar CI no duplicado
                IF NOT fn_validar_ci_no_duplicado(NEW.ci) THEN
                    RAISE EXCEPTION 'CI % ya existe en el sistema', NEW.ci;
                END IF;

                -- Registrar en bitácora con ip_origen default
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Persona creada', NOW(), '0.0.0.0', 0, 'persona', 'INSERT', NEW.id::TEXT, NULL, 
                        CONCAT('CI: ', NEW.ci, ', Nombre: ', NEW.nombre, ' ', NEW.apellido));

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_persona_update with ip_origen
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_persona_update()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Si el CI cambió, validar que sea único
                IF OLD.ci != NEW.ci AND NOT fn_validar_ci_no_duplicado(NEW.ci) THEN
                    RAISE EXCEPTION 'CI % ya existe en el sistema', NEW.ci;
                END IF;

                -- Registrar en bitácora
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Persona actualizada', NOW(), '0.0.0.0', 0, 'persona', 'UPDATE', NEW.id::TEXT, OLD.ci, NEW.ci);

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Fix fn_persona_delete with ip_origen
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_persona_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Registrar en bitácora
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Persona eliminada', NOW(), '0.0.0.0', 0, 'persona', 'DELETE', OLD.id::TEXT, 
                        CONCAT('CI: ', OLD.ci), NULL);

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
