<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Eliminar registros antiguos de bitácora (códigos 49, 48, 47)
     * que no son acciones recientes
     */
    public function up(): void
    {
        // Eliminar los 3 registros especificados
        DB::table('bitacora')->whereIn('codigo', [49, 48, 47])->delete();

        // Registrar esta limpieza
        DB::table('bitacora')->insert([
            'accion' => '[SISTEMA] Limpieza de registros históricos no recientes',
            'fecha_hora' => now(),
            'ip_origen' => '0.0.0.0',
            'id_persona' => 1
        ]);

        echo "\n✅ Registros eliminados: 3 (códigos 49, 48, 47)\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se pueden recuperar, era una limpieza intencional
        echo "\n⚠️ No se pueden recuperar los registros eliminados\n";
    }
};
