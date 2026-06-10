<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix fn_bitacora_insert_inscripcion - NO usamos NEW.persona_id que no existe
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_bitacora_insert_inscripcion()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado)
                VALUES (
                    '[TRIGGER] Nueva inscripción realizada',
                    NOW(),
                    '0.0.0.0',
                    NULL,
                    'inscripcion',
                    'INSERT',
                    NEW.id::TEXT
                );
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
