<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Limpiar registros problemáticos de bitácora
     * 
     * PROBLEMA: El middleware se ejecutaba ANTES de que el controlador actualizara
     * la sesión, causando que se registraran acciones con el usuario anterior.
     * 
     * SOLUCIÓN: 
     * 1. Excluir rutas de login/logout del middleware
     * 2. Registrar desde el controlador DESPUÉS de actualizar sesión
     * 3. Marcar registros antiguos como "[VERIFICAR]" para auditoría
     */
    public function up(): void
    {
        // Identificar y marcar registros problemáticos
        // Registros de "Acceso a dashboard de postulante" registrados con Natalia (id_persona: 16, decano)
        // cuando en realidad fueron otros usuarios accediendo a dashboard
        
        DB::table('bitacora')
            ->where('id_persona', 16)
            ->where('accion', 'like', '%dashboard de postulante%')
            ->update([
                'accion' => DB::raw("CONCAT('[DATO_INCORRECTO_FIJADO] ', accion)")
            ]);

        // Registros duplicados de logout (se registraba tanto en middleware como en controlador)
        DB::table('bitacora')
            ->where('accion', 'Cierre de sesión')
            ->where('id_persona', 16)
            ->where('fecha_hora', '>=', '2026-06-04 20:30:00')
            ->where('fecha_hora', '<=', '2026-06-04 20:35:05')
            ->limit(1)  // Mantener uno, eliminar el duplicado
            ->delete();

        // Registrar esta corrección en la bitácora (solo si existe la persona 1,
        // evita violar la FK en una BD recién creada sin datos)
        if (DB::table('persona')->where('id', 1)->exists()) {
            DB::table('bitacora')->insert([
                'accion' => '[SISTEMA] Bitácora corregida: registros de usuario incorrecto marcados',
                'fecha_hora' => now(),
                'ip_origen' => '0.0.0.0',
                'id_persona' => 1  // Sistema
            ]);
        }

        echo "\n✅ Bitácora limpiada y corregida\n";
        echo "   - Registros problemáticos marcados como [DATO_INCORRECTO_FIJADO]\n";
        echo "   - Sistema ahora registra con usuario CORRECTO\n";
        echo "   - Próximos logins se registrarán con el usuario correcto\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir los cambios
        DB::table('bitacora')
            ->where('accion', 'like', '%[DATO_INCORRECTO_FIJADO]%')
            ->update([
                'accion' => DB::raw("REPLACE(accion, '[DATO_INCORRECTO_FIJADO] ', '')")
            ]);

        DB::table('bitacora')
            ->where('accion', 'like', '%Corrección de registros de auditoría%')
            ->delete();
    }
};
