<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SessionManager;

class SessionCheck
{
    /**
     * Middleware para validar sesiones únicas
     * Se ejecuta en TODAS las rutas autenticadas
     */
    public function handle(Request $request, Closure $next)
    {
        // ✅ IMPORTANTE: Si el usuario está autenticado, validar sesión
        // Esto se aplica a TODAS las rutas, no solo a un listado específico
        if (Auth::check()) {
            try {
                $user = Auth::user();
                $personaId = $user->id;
                $currentSessionId = $request->session()->getId();

                // 🔴 VALIDAR: ¿La sesión ACTUAL está activa en user_sessions?
                // Usar la sesión ID específica para validar que es ESTA sesión la activa
                if (!SessionManager::isSessionValid($personaId, $currentSessionId)) {
                    // La sesión fue invalidada (login desde otro dispositivo)
                    Auth::guard('web')->logout();
                    $request->session()->flush();
                    $request->session()->regenerateToken();
                    
                    return redirect('/?error=' . urlencode('Sesión cerrada en otro dispositivo'));
                }

                // ✅ Actualizar última actividad (heartbeat)
                SessionManager::updateLastActivity($personaId);

            } catch (\Exception $e) {
                // Si hay error validando, es mejor ser seguro y cerrar sesión
                \Illuminate\Support\Facades\Log::error('Error en SessionCheck middleware', [
                    'error' => $e->getMessage()
                ]);
                Auth::guard('web')->logout();
                $request->session()->flush();
                return redirect('/?error=' . urlencode('Error al validar sesión. Inicie sesión nuevamente.'));
            }
        }

        // Obtener la respuesta del siguiente middleware/controlador
        $response = $next($request);

        // Si está autenticado, agregar headers anti-caché agresivos
        if (Auth::check()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '-1');
            $response->headers->set('Surrogate-Control', 'no-store');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $response->headers->set('ETag', '"' . md5(time()) . '"');
        }

        return $response;
    }
}
