<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Corregir función fn_bitacora_insert_pago
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_insert_pago ON pago;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP FUNCTION IF EXISTS fn_bitacora_insert_pago();
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION fn_bitacora_insert_pago()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO bitacora (accion, fecha_hora, ip_origen, id_persona)
                VALUES (
                    '[TRIGGER] Pago registrado - Monto: ' || COALESCE(NEW.monto::text, '0') || ', Estado: ' || COALESCE(NEW.estado, 'Pendiente'),
                    NOW(),
                    '0.0.0.0',
                    1
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER bitacora_insert_pago
            AFTER INSERT ON pago
            FOR EACH ROW
            EXECUTE FUNCTION fn_bitacora_insert_pago();
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS bitacora_insert_pago ON pago;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP FUNCTION IF EXISTS fn_bitacora_insert_pago();
        SQL);
    }
};
