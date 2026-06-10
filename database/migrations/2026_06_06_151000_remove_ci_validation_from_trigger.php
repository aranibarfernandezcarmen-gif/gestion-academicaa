<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remover validación de CI del trigger AFTER (ya está en tabla)
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_persona_insert()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Persona creada', NOW(), '0.0.0.0', NEW.id, 'persona', 'INSERT', NEW.id::TEXT, NULL, 
                        CONCAT('CI: ', NEW.ci, ', Nombre: ', NEW.nombre, ' ', NEW.apellido));
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE OR REPLACE FUNCTION fn_persona_update()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado, valor_anterior, valor_nuevo)
                VALUES ('Persona actualizada', NOW(), '0.0.0.0', NEW.id, 'persona', 'UPDATE', NEW.id::TEXT, OLD.ci, NEW.ci);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        //
    }
};
