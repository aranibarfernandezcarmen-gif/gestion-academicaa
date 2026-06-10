<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix fn_bitacora_insert_postulante - usar NEW.id_persona en lugar de NEW.persona_id
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_bitacora_insert_postulante()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona, tabla_modificada, operacion, registro_modificado)
                VALUES (
                    '[TRIGGER] Nuevo postulante registrado: ' || COALESCE(NEW.registro, ''),
                    NOW(),
                    '0.0.0.0',
                    NEW.id_persona,
                    'postulante',
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
