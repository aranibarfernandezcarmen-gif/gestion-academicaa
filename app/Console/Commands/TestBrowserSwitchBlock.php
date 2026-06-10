<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SessionManager;
use Illuminate\Http\Request;

class TestBrowserSwitchBlock extends Command
{
    protected $signature = 'test:browser-switch';
    protected $description = 'Simula el cambio de navegador y verifica que la sesión anterior se bloquea';

    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║  TEST: Bloqueo de sesión al cambiar de navegador              ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Obtener usuario de prueba
        $persona = DB::table('persona')->first();
        if (!$persona) {
            $this->error('❌ No hay personas en la base de datos');
            return 1;
        }

        // Limpiar sesiones anteriores
        DB::table('user_sessions')->where('id_persona', $persona->id)->delete();

        $this->line("👤 Usuario: {$persona->nombre} {$persona->apellido} (ID: {$persona->id})");
        $this->newLine();

        // PASO 1: Simular login en CHROME
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('PASO 1️⃣: Login en CHROME');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $chromeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
        $chromeRequest = $this->createMockRequest($chromeUserAgent, '192.168.1.100');
        $chromeSessionId = 'chrome_' . uniqid();

        // Simular que Laravel crearía una sesión
        DB::table('sessions')->insert([
            'id' => $chromeSessionId,
            'user_id' => null,
            'ip_address' => '192.168.1.100',
            'user_agent' => $chromeUserAgent,
            'payload' => base64_encode(json_encode(['_token' => 'fake', 'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $persona->id])),
            'last_activity' => now()->timestamp,
        ]);

        $result1 = SessionManager::createUserSession($persona->id, $chromeRequest, $chromeSessionId);
        $this->line($result1 ? '✅ Sesión Chrome creada' : '❌ Error creando sesión Chrome');

        $chromeSessions = DB::table('user_sessions')
            ->where('id_persona', $persona->id)
            ->where('is_active', true)
            ->get();

        $this->table(
            ['Browser', 'IP', 'Activa', 'Session ID'],
            $chromeSessions->map(fn($s) => [$s->browser, $s->ip_address, '✅', $s->session_id])->toArray()
        );

        $this->newLine();
        $this->info('💻 En Chrome: El usuario puede navegar normalmente');
        $this->info('   isSessionValid(' . $persona->id . ') = ' . (SessionManager::isSessionValid($persona->id) ? '✅ TRUE' : '❌ FALSE'));
        $this->newLine();

        $this->line('⏳ Esperando 2 segundos...');
        sleep(2);

        // PASO 2: Simular login en EDGE
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('PASO 2️⃣: Login en EDGE (mismo usuario)');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $edgeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 Edg/121.0.0.0';
        $edgeRequest = $this->createMockRequest($edgeUserAgent, '192.168.1.200');
        $edgeSessionId = 'edge_' . uniqid();

        // Simular que Laravel crearía una sesión
        DB::table('sessions')->insert([
            'id' => $edgeSessionId,
            'user_id' => null,
            'ip_address' => '192.168.1.200',
            'user_agent' => $edgeUserAgent,
            'payload' => base64_encode(json_encode(['_token' => 'fake', 'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $persona->id])),
            'last_activity' => now()->timestamp,
        ]);

        $result2 = SessionManager::createUserSession($persona->id, $edgeRequest, $edgeSessionId);
        $this->line($result2 ? '✅ Sesión Edge creada' : '❌ Error creando sesión Edge');

        $allSessions = DB::table('user_sessions')
            ->where('id_persona', $persona->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->table(
            ['Browser', 'IP', 'Activa', 'Session ID'],
            $allSessions->map(fn($s) => [$s->browser, $s->ip_address, $s->is_active ? '✅' : '❌', substr($s->session_id, 0, 20) . '...'])->toArray()
        );

        $this->newLine();
        $this->info('✅ En EDGE: El usuario puede navegar normalmente');
        $this->info('   isSessionValid(' . $persona->id . ') = ' . (SessionManager::isSessionValid($persona->id) ? '✅ TRUE' : '❌ FALSE'));
        $this->newLine();

        // PASO 3: Intentar acceso desde CHROME
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('PASO 3️⃣: Intento de acceso desde CHROME (después de Edge)');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Verificar si la sesión de Chrome fue eliminada de la tabla sessions de Laravel
        $chromeSessionExists = DB::table('sessions')
            ->where('id', $chromeSessionId)
            ->exists();

        $this->line('');
        $this->line('Verificando estado de sesiones en tabla sessions (Laravel):');
        $this->line('  • Sesión Chrome en BD: ' . ($chromeSessionExists ? '❌ EXISTE (MALA)' : '✅ NO EXISTE (BUENO)'));

        $edgeSessionExists = DB::table('sessions')
            ->where('id', $edgeSessionId)
            ->exists();
        $this->line('  • Sesión Edge en BD: ' . ($edgeSessionExists ? '✅ EXISTE (BUENO)' : '❌ NO EXISTE (MALA)'));

        $this->newLine();

        // Evaluación final
        if ($chromeSessionExists) {
            $this->error('❌ ERROR: Chrome sigue teniendo sesión en BD (debería estar bloqueado)');
            $this->newLine();
            $this->error('⚠️  La sesión NO se está bloqueando correctamente');
            $this->error('    Laravel seguirá autenticando a Chrome aunque sea inactivo en user_sessions');
        } else {
            $this->info('✅ CORRECTO: Chrome NO tiene sesión en BD');
            $this->line('   El middleware lo bloqueará en la próxima request porque:');
            $this->line('   1. Auth::check() retornará FALSE (sesión no existe)');
            $this->line('   2. Se redirige al login: "Inició sesión desde otro dispositivo"');
        }

        // Mostrar estado final
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('ESTADO FINAL');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $finalSessions = DB::table('user_sessions')
            ->where('id_persona', $persona->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $activeCount = $finalSessions->where('is_active', true)->count();
        $inactiveCount = $finalSessions->where('is_active', false)->count();

        $this->table(
            ['Browser', 'IP', 'Estado', 'Última actividad'],
            $finalSessions->map(fn($s) => [
                $s->browser,
                $s->ip_address,
                $s->is_active ? '✅ ACTIVA' : '❌ INACTIVA',
                $s->last_activity
            ])->toArray()
        );

        $this->newLine();
        $this->info("Sesiones ACTIVAS: {$activeCount}");
        $this->info("Sesiones INACTIVAS: {$inactiveCount}");

        if ($activeCount === 1 && $inactiveCount === 1) {
            $this->info('');
            $this->info('✅ ✅ ✅ PRUEBA EXITOSA ✅ ✅ ✅');
            $this->info('Solo 1 sesión activa. El control de sesión única funciona correctamente.');
        } else {
            $this->info('');
            $this->error('❌ PRUEBA FALLIDA');
            $this->error("Debería haber 1 sesión activa y 1 inactiva, pero hay {$activeCount} activas y {$inactiveCount} inactivas.");
        }

        // Verificar bitácora
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('REGISTROS EN BITÁCORA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $bitacoras = DB::table('bitacora')
            ->where('id_persona', $persona->id)
            ->where('accion', 'like', '%Nueva sesión%')
            ->orderBy('fecha_hora', 'desc')
            ->limit(5)
            ->get();

        if ($bitacoras->isEmpty()) {
            $this->error('⚠️ No se encontraron registros en bitácora');
        } else {
            foreach ($bitacoras as $b) {
                $this->line("✓ {$b->accion}");
                $this->line("  {$b->fecha_hora} | IP: {$b->ip_origen}");
            }
        }

        return 0;
    }

    /**
     * Crear una Request simulada
     */
    private function createMockRequest($userAgent, $ip): Request
    {
        $request = new Request();
        $request->server->set('HTTP_USER_AGENT', $userAgent);
        $request->server->set('REMOTE_ADDR', $ip);
        return $request;
    }
}
