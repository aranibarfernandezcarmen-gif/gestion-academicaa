<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
                VALUES ('Persona creada', 0, 'persona', 'INSERT', NEW.id::TEXT, NULL, 
                        CONCAT('CI: ', NEW.ci, ', Nombre: ', NEW.nombre, ' ', NEW.apellido));

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
