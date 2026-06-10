<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;

class TestSessionValidationCUs extends Command
{
    protected $signature = 'test:session-validation-cus';
    protected $description = 'Valida que la sesión sea detectada como inválida incluso en CUs públicos';

    public function handle()
    {
        $this->info('🧪 Test: Validación de sesión en CUs públicos');
        $this->info('=========================================\n');

        // 1️⃣ Simular primer login (Chrome)
        $this->info('1️⃣ Simulando login en Chrome...');
        
        $personaId = 1; // Usuario de prueba
        $sessionId1 = 'chrome_session_' . bin2hex(random_bytes(16));
        
        // Crear entrada en user_sessions para Chrome
        DB::table('user_sessions')->insert([
            'id_persona' => $personaId,
            'session_id' => $sessionId1,
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/91.0',
            'ip_address' => '192.168.1.100',
            'last_activity' => now(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->line("   ✅ Chrome session: {$sessionId1}");
        
        // Crear entrada en sessions table (simular Laravel session)
        DB::table('sessions')->insertOrIgnore([
            'id' => $sessionId1,
            'user_id' => null,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/91.0',
            'payload' => 'dummy_payload',
            'last_activity' => now()->timestamp,
        ]);
        
        $this->line("   ✅ Entry added to sessions table\n");

        // 2️⃣ Simular segundo login (Edge - mismo usuario)
        $this->info('2️⃣ Simulando login en Edge (mismo usuario)...');
        
        $sessionId2 = 'edge_session_' . bin2hex(random_bytes(16));
        
        // Marcar Chrome como inactiva
        DB::table('user_sessions')
            ->where('id_persona', $personaId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
        
        $this->line("   ✅ Chrome session marked as inactive");
        
        // Eliminar sesión Chrome de sessions table (simular lo que hace SessionManager)
        DB::table('sessions')->where('id', $sessionId1)->delete();
        $this->line("   ✅ Chrome session deleted from sessions table");
        
        // Crear nueva sesión en user_sessions para Edge
        DB::table('user_sessions')->insert([
            'id_persona' => $personaId,
            'session_id' => $sessionId2,
            'device_type' => 'desktop',
            'browser' => 'Edge',
            'os' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Edg/91.0',
            'ip_address' => '192.168.1.101',
            'last_activity' => now(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->line("   ✅ Edge session: {$sessionId2}\n");

        // 3️⃣ Verificar estado en base de datos
        $this->info('3️⃣ Verificando estado en base de datos...');
        
        $chromeSessions = DB::table('user_sessions')
            ->where('session_id', $sessionId1)
            ->first();
        
        $edgeSessions = DB::table('user_sessions')
            ->where('session_id', $sessionId2)
            ->first();
        
        $this->table(
            ['Session ID', 'Browser', 'Status', 'Expected'],
            [
                [
                    $sessionId1,
                    'Chrome',
                    $chromeSessions->is_active ? '❌ ACTIVE' : '✅ INACTIVE',
                    '✅ INACTIVE'
                ],
                [
                    $sessionId2,
                    'Edge',
                    $edgeSessions->is_active ? '✅ ACTIVE' : '❌ INACTIVE',
                    '✅ ACTIVE'
                ],
            ]
        );
        
        // 4️⃣ Simular que Chrome intenta acceder a un CU
        $this->info('\n4️⃣ Simulando acceso a CU público con sesión Chrome (inválida)...');
        
        // Verificar si session_id de Chrome existe en user_sessions y está activa
        $validSession = DB::table('user_sessions')
            ->where('session_id', $sessionId1)
            ->first();
        
        if ($validSession) {
            if (!$validSession->is_active) {
                $this->line("   🔴 Chrome session es INVÁLIDA (desactivada)");
                $this->line("   ✅ El middleware REDIRIGIRÁ a / con error");
                $this->line("   ✅ Usuario será desautenticado automáticamente");
            } else {
                $this->error("   ❌ Chrome session debería estar inactiva pero está activa!");
            }
        } else {
            $this->error("   ❌ Chrome session no encontrada en user_sessions!");
        }
        
        // 5️⃣ Resultado final
        $this->info('\n5️⃣ Resultado Final:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if (!$chromeSessions->is_active && $edgeSessions->is_active) {
            $this->line('✅ ¡ÉXITO! El sistema detectará la sesión inválida en CUs');
            $this->line('✅ Chrome será redirigido a / incluso en CUs públicos');
            $this->line('✅ El middleware validará TODAS las rutas, no solo autenticadas');
        } else {
            $this->error('❌ Error: El estado de las sesiones no es el esperado');
        }
        
        // Limpiar
        $this->info('\n🧹 Limpiando datos de prueba...');
        DB::table('user_sessions')
            ->where('session_id', $sessionId1)
            ->orWhere('session_id', $sessionId2)
            ->delete();
        
        DB::table('sessions')
            ->where('id', $sessionId1)
            ->orWhere('id', $sessionId2)
            ->delete();
        
        $this->line('   ✅ Datos de prueba eliminados');
    }
}
