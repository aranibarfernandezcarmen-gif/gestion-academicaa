<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class SessionManager
{
    /**
     * Crear o actualizar una sesión de usuario
     * Desactiva todas las sesiones anteriores e inserta una nueva
     *
     * @param int $personaId
     * @param Request $request
     * @param string $sessionId (session ID de Laravel)
     * @return bool
     */
    public static function createUserSession($personaId, Request $request, $sessionId): bool
    {
        try {
            // Información del dispositivo/navegador
            $agent = new Agent();
            $agent->setUserAgent($request->userAgent());

            $deviceType = 'desktop';
            if ($agent->isMobile()) {
                $deviceType = 'mobile';
            } elseif ($agent->isTablet()) {
                $deviceType = 'tablet';
            }

            $deviceInfo = [
                'device_type' => $deviceType,
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ];

            // PASO 1: Obtener sesiones antiguas (excluyendo la actual si ya existiera)
            $sessionId = trim($sessionId);
            $oldSessions = DB::table('user_sessions')
                ->where('id_persona', $personaId)
                ->where('session_id', '!=', $sessionId)
                ->where('is_active', true)
                ->get();

            // Desactivar sesiones anteriores en nuestra tabla de control
            DB::table('user_sessions')
                ->where('id_persona', $personaId)
                ->where('session_id', '!=', $sessionId)
                ->update(['is_active' => false, 'updated_at' => now()]);

            foreach ($oldSessions as $oldSession) {
                try {
                    if (trim($oldSession->session_id) === $sessionId) continue;

                    DB::table('sessions')
                        ->where('id', $oldSession->session_id)
                        ->delete();
                } catch (\Throwable $e) {
                    // Si falla, no es crítico
                    \Illuminate\Support\Facades\Log::warning('Aviso: No se pudo eliminar sesión antigua de Laravel', [
                        'session_id' => $oldSession->session_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // PASO 2: Crear la nueva sesión activa (ignorar conflictos UNIQUE si existen)
            try {
                DB::table('user_sessions')->insert([
                    'id_persona' => $personaId,
                    'session_id' => $sessionId,
                    'device_type' => $deviceInfo['device_type'],
                    'browser' => $deviceInfo['browser'],
                    'os' => $deviceInfo['os'],
                    'user_agent' => $deviceInfo['user_agent'],
                    'ip_address' => $deviceInfo['ip_address'],
                    'last_activity' => now(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $uniqueError) {
                // Si hay conflicto UNIQUE en session_id, actualizar el registro existente
                if (strpos($uniqueError->getMessage(), '23505') !== false || 
                    strpos($uniqueError->getMessage(), 'unique') !== false) {
                    \Illuminate\Support\Facades\Log::info('Actualizar sesión existente debido a UNIQUE constraint', [
                        'persona_id' => $personaId,
                        'session_id' => $sessionId
                    ]);
                    
                    DB::table('user_sessions')
                        ->where('session_id', $sessionId)
                        ->update([
                            'id_persona' => $personaId,
                            'device_type' => $deviceInfo['device_type'],
                            'browser' => $deviceInfo['browser'],
                            'os' => $deviceInfo['os'],
                            'user_agent' => $deviceInfo['user_agent'],
                            'ip_address' => $deviceInfo['ip_address'],
                            'last_activity' => now(),
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);
                } else {
                    throw $uniqueError;
                }
            }

            // Registrar en bitácora (si está disponible)
            try {
                BitacoraService::registrar(
                    "Nueva sesión iniciada - {$deviceInfo['device_type']} ({$deviceInfo['browser']}, {$deviceInfo['ip_address']})",
                    $deviceInfo['ip_address'],
                    $personaId
                );
            } catch (\Throwable $e) {
                // Si falla el registro en bitácora, no afecta la sesión
                \Illuminate\Support\Facades\Log::warning('Aviso: No se pudo registrar sesión en bitácora', [
                    'error' => $e->getMessage()
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en SessionManager::createUserSession', [
                'persona_id' => $personaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Validar que una sesión sigue siendo activa para el usuario
     * Si se proporciona sessionId, valida esa sesión específica
     * Si no se proporciona, valida que hay AL MENOS UNA sesión activa
     *
     * @param int $personaId
     * @param string|null $sessionId - ID de sesión específica a validar (OBLIGATORIO para SessionCheck)
     * @return bool
     */
    public static function isSessionValid($personaId, $sessionId = null): bool
    {
        try {
            // Si se proporciona sessionId, validar esa sesión específica
            // (esto es lo que debe usar SessionCheck middleware)
            if ($sessionId !== null) {
                $session = DB::table('user_sessions')
                    ->where('id_persona', $personaId)
                    ->where('session_id', $sessionId)
                    ->where('is_active', true)
                    ->first();

                return $session !== null;
            }

            // Si NO se proporciona sessionId, validar que hay AL MENOS UNA sesión activa
            // (para compatibilidad con código anterior)
            $session = DB::table('user_sessions')
                ->where('id_persona', $personaId)
                ->where('is_active', true)
                ->first();

            return $session !== null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error validando sesión', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Actualizar la última actividad de una sesión (por usuario)
     * Actualiza la sesión ACTIVA del usuario, sin importar cambios de session_id
     *
     * @param int $personaId - ID del usuario
     * @param string|null $sessionId - Ignorado (mantenido por compatibilidad)
     * @return bool
     */
    public static function updateLastActivity($personaId, $sessionId = null): bool
    {
        try {
            DB::table('user_sessions')
                ->where('id_persona', $personaId)
                ->where('is_active', true)
                ->update(['last_activity' => now()]);

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error actualizando última actividad', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener todas las sesiones activas de un usuario
     *
     * @param int $personaId
     * @return \Illuminate\Support\Collection
     */
    public static function getActiveSessions($personaId)
    {
        return DB::table('user_sessions')
            ->where('id_persona', $personaId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Cerrar una sesión específica (logout)
     *
     * @param string $sessionId
     * @return bool
     */
    public static function closeSession($sessionId): bool
    {
        try {
            DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->update(['is_active' => false]);

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error cerrando sesión', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cerrar TODAS las sesiones de un usuario (fuerza logout en todos lados)
     *
     * @param int $personaId
     * @return bool
     */
    public static function closeAllUserSessions($personaId): bool
    {
        try {
            DB::table('user_sessions')
                ->where('id_persona', $personaId)
                ->update(['is_active' => false]);

            BitacoraService::registrar(
                "Todas las sesiones cerradas (logout desde administración)",
                null,
                $personaId
            );

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error cerrando todas las sesiones', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Limpiar sesiones inactivas (mayores a X minutos)
     *
     * @param int $minutosInactividad
     * @return int - Número de sesiones limpiadas
     */
    public static function cleanupInactiveSessions($minutosInactividad = 1440): int
    {
        try {
            $cutoffTime = now()->subMinutes($minutosInactividad);

            return DB::table('user_sessions')
                ->where('is_active', true)
                ->where('last_activity', '<', $cutoffTime)
                ->update(['is_active' => false]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error limpiando sesiones inactivas', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
