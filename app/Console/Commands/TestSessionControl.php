<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class TestSessionControl extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:session-control';

    /**
     * The console command description.
     */
    protected $description = 'Prueba el control de sesiones únicas por usuario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== PRUEBA: CONTROL DE SESIONES ÚNICAS POR USUARIO ===');
        $this->newLine();

        // Obtener un usuario de prueba
        $persona = DB::table('persona')->first();

        if (!$persona) {
            $this->error('❌ No hay personas en la base de datos. Registra una persona primero.');
            return 1;
        }

        $this->info("✓ Usuario de prueba: {$persona->nombre} {$persona->apellido} (ID: {$persona->id})");
        $this->newLine();

        // Limpiar sesiones anteriores del usuario
        DB::table('user_sessions')
            ->where('id_persona', $persona->id)
            ->delete();

        // PASO 1: Simular login desde móvil
        $this->info('1️⃣ SIMULANDO LOGIN DESDE MÓVIL...');
        $this->line('   User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');

        $requestMobile = $this->createMockRequest(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
            '192.168.1.100'
        );

        $sessionIdMobile = 'SESSION_MOBILE_' . uniqid();
        SessionManager::createUserSession($persona->id, $requestMobile, $sessionIdMobile);

        $sessionsAfterMobile = SessionManager::getActiveSessions($persona->id);
        $this->line("   ✓ Sesión móvil creada. Sesiones activas: " . count($sessionsAfterMobile));
        $this->table(['ID', 'Dispositivo', 'Navegador', 'IP', 'Activa'], 
            $sessionsAfterMobile->map(function($s) {
                return [
                    $s->id,
                    $s->device_type,
                    $s->browser ?? '-',
                    $s->ip_address,
                    $s->is_active ? '✓ Sí' : '✗ No'
                ];
            })->toArray()
        );
        $this->newLine();

        // PASO 2: Esperar
        $this->info('2️⃣ ESPERANDO 2 SEGUNDOS...');
        sleep(2);
        $this->newLine();

        // PASO 3: Simular login desde computadora
        $this->info('3️⃣ SIMULANDO LOGIN DESDE COMPUTADORA...');
        $this->line('   User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $requestDesktop = $this->createMockRequest(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            '192.168.1.50'
        );

        $sessionIdDesktop = 'SESSION_DESKTOP_' . uniqid();
        SessionManager::createUserSession($persona->id, $requestDesktop, $sessionIdDesktop);

        $sessionsAfterDesktop = SessionManager::getActiveSessions($persona->id);
        $this->line("   ✓ Sesión desktop creada. Sesiones activas: " . count($sessionsAfterDesktop));
        $this->table(['ID', 'Dispositivo', 'Navegador', 'IP', 'Activa'], 
            $sessionsAfterDesktop->map(function($s) {
                return [
                    $s->id,
                    $s->device_type,
                    $s->browser ?? '-',
                    $s->ip_address,
                    $s->is_active ? '✓ Sí' : '✗ No'
                ];
            })->toArray()
        );
        $this->newLine();

        // PASO 4: Verificar resultado
        $this->info('4️⃣ VERIFICANDO RESULTADOS...');
        $activeSessions = SessionManager::getActiveSessions($persona->id);
        $inactiveSessions = DB::table('user_sessions')
            ->where('id_persona', $persona->id)
            ->where('is_active', false)
            ->get();

        $this->line("   ✓ Sesiones activas: " . count($activeSessions));
        $this->line("   ✓ Sesiones inactivas: " . count($inactiveSessions));

        if (count($activeSessions) === 1 && count($inactiveSessions) === 1) {
            $this->info("\n✅ RESULTADO: CORRECTO!");
            $this->line("   • La sesión móvil fue deactivada automáticamente");
            $this->line("   • Solo la sesión desktop sigue activa");
            $this->line("   • El control de sesión única funciona perfectamente");
            
            // Prueba adicional: validar que la sesión móvil NO es válida
            $this->newLine();
            $this->info('5️⃣ VALIDACIÓN ADICIONAL...');
            $isMobileValid = SessionManager::isSessionValid($persona->id, $sessionIdMobile);
            $isDesktopValid = SessionManager::isSessionValid($persona->id, $sessionIdDesktop);
            
            $this->line("   • Sesión móvil válida: " . ($isMobileValid ? "✓ Sí" : "✗ No"));
            $this->line("   • Sesión desktop válida: " . ($isDesktopValid ? "✓ Sí" : "✗ No"));
            
            if (!$isMobileValid && $isDesktopValid) {
                $this->info("\n✅ TODAS LAS PRUEBAS PASARON!");
                return 0;
            }
        } else {
            $this->error("\n❌ RESULTADO: INCORRECTO!");
            $this->line("   Se esperaba: 1 sesión activa, 1 inactiva");
            $this->line("   Se obtuvo: " . count($activeSessions) . " activas, " . count($inactiveSessions) . " inactivas");
        }
        
        return 1;
    }

    /**
     * Crear un Request simulado con User-Agent e IP
     */
    private function createMockRequest($userAgent, $ipAddress)
    {
        $request = new Request();
        $request->server->set('HTTP_USER_AGENT', $userAgent);
        $request->server->set('REMOTE_ADDR', $ipAddress);
        
        // Para debugging, verificar que se captura correctamente
        $this->line("      Debug - IP capturada: " . $request->ip());
        $this->line("      Debug - UserAgent: " . $request->userAgent());
        
        return $request;
    }
}
