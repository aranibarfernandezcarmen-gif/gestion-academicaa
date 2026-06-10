<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BitacoraService
{
    /**
     * Registrar una acción en la bitácora con manejo robusto de errores
     *
     * @param string $accion - Descripción de la acción
     * @param string|null $ipOrigen - IP origen de la solicitud
     * @param int|null $usuarioId - ID del usuario (si no está autenticado)
     * @return bool - Retorna true si se registró, false si falló
     */
    public static function registrar($accion, $ipOrigen = null, $usuarioId = null): bool
    {
        try {
            // Obtener ID del usuario desde varias fuentes
            $usuarioId = self::obtenerUsuarioId($usuarioId);

            // Si no hay usuario, no registrar (podría ser público)
            if ($usuarioId === null) {
                Log::warning("Bitácora: No se pudo determinar usuario para acción: {$accion}");
                return false;
            }

            // Preparar datos
            $datos = [
                'accion' => self::truncarAccion($accion),
                'fecha_hora' => now(),
                'ip_origen' => $ipOrigen ?? self::obtenerIP(),
                'id_persona' => $usuarioId,
            ];

            // Insertar en base de datos
            DB::table('bitacora')->insert($datos);

            Log::info("Bitácora registrada correctamente", $datos);
            return true;

        } catch (\Throwable $e) {
            Log::error('Error crítico registrando en bitácora', [
                'accion' => $accion,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Registrar una acción de creación
     *
     * @param string $tabla - Tabla afectada
     * @param int $registroId - ID del registro creado
     * @param array $datos - Datos creados
     */
    public static function registrarCreacion($tabla, $registroId, $datos = []): bool
    {
        $detalles = !empty($datos) ? json_encode($datos) : '';
        $accion = "Creación en {$tabla} (ID: {$registroId}) {$detalles}";
        return self::registrar($accion);
    }

    /**
     * Registrar una acción de actualización
     *
     * @param string $tabla - Tabla afectada
     * @param int $registroId - ID del registro actualizado
     * @param array $cambios - Cambios realizados
     */
    public static function registrarActualizacion($tabla, $registroId, $cambios = []): bool
    {
        $detalles = !empty($cambios) ? json_encode($cambios) : '';
        $accion = "Actualización en {$tabla} (ID: {$registroId}) {$detalles}";
        return self::registrar($accion);
    }

    /**
     * Registrar una acción de eliminación
     *
     * @param string $tabla - Tabla afectada
     * @param int $registroId - ID del registro eliminado
     */
    public static function registrarEliminacion($tabla, $registroId): bool
    {
        $accion = "Eliminación en {$tabla} (ID: {$registroId})";
        return self::registrar($accion);
    }

    /**
     * Registrar una excepción o error
     *
     * @param string $mensaje - Mensaje de error
     * @param \Throwable $excepcion - Excepción capturada
     */
    public static function registrarError($mensaje, \Throwable $excepcion): bool
    {
        $accion = "[ERROR] {$mensaje} - " . $excepcion->getMessage();
        return self::registrar($accion);
    }

    /**
     * Obtener ID del usuario desde varias fuentes
     *
     * @param int|null $usuarioId - ID explícito del usuario
     * @return int|null - ID del usuario o null
     */
    private static function obtenerUsuarioId($usuarioId = null): ?int
    {
        // Si se proporciona explícitamente
        if ($usuarioId !== null && $usuarioId > 0) {
            return $usuarioId;
        }

        // Desde la sesión
        if (request()->hasSession()) {
            $sessionId = request()->session()->get('persona_id');
            if ($sessionId) {
                return $sessionId;
            }
        }

        // Desde usuario autenticado (Laravel Auth)
        if (Auth::check()) {
            $user = Auth::user();
            return $user?->id;
        }

        return null;
    }

    /**
     * Obtener IP del cliente
     *
     * @return string - IP origen
     */
    private static function obtenerIP(): string
    {
        try {
            $ip = request()->ip();
            return $ip ?: '0.0.0.0';
        } catch (\Exception $e) {
            return '0.0.0.0';
        }
    }

    /**
     * Truncar acción a 100 caracteres (tamaño máximo en BD)
     *
     * @param string $accion - Acción a truncar
     * @return string - Acción truncada
     */
    private static function truncarAccion($accion): string
    {
        return substr($accion, 0, 100);
    }

    /**
     * Obtener la bitácora paginada
     *
     * @param int $porPagina - Registros por página
     * @return \Illuminate\Pagination\Paginator
     */
    public static function obtener($porPagina = 50)
    {
        try {
            return DB::table('bitacora')
                ->leftJoin('persona', 'bitacora.id_persona', '=', 'persona.id')
                ->select([
                    'bitacora.*',
                    DB::raw("CONCAT(persona.nombre, ' ', persona.apellido) as usuario_nombre")
                ])
                ->orderBy('bitacora.fecha_hora', 'desc')
                ->paginate($porPagina);
        } catch (\Exception $e) {
            Log::error("Error obteniendo bitácora: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Obtener bitácora filtrada
     *
     * @param array $filtros - Filtros a aplicar
     * @param int $porPagina - Registros por página
     * @return \Illuminate\Pagination\Paginator
     */
    public static function obtenerFiltrada($filtros = [], $porPagina = 50)
    {
        try {
            $query = DB::table('bitacora')
                ->leftJoin('persona', 'bitacora.id_persona', '=', 'persona.id')
                ->select([
                    'bitacora.*',
                    DB::raw("CONCAT(persona.nombre, ' ', persona.apellido) as usuario_nombre")
                ]);

            // Filtrar por usuario
            if (isset($filtros['usuario_id']) && $filtros['usuario_id']) {
                $query->where('bitacora.id_persona', $filtros['usuario_id']);
            }

            // Filtrar por acción
            if (isset($filtros['accion']) && $filtros['accion']) {
                $query->where('bitacora.accion', 'like', '%' . $filtros['accion'] . '%');
            }

            // Filtrar por fecha desde
            if (isset($filtros['fecha_desde']) && $filtros['fecha_desde']) {
                $query->whereDate('bitacora.fecha_hora', '>=', $filtros['fecha_desde']);
            }

            // Filtrar por fecha hasta
            if (isset($filtros['fecha_hasta']) && $filtros['fecha_hasta']) {
                $query->whereDate('bitacora.fecha_hora', '<=', $filtros['fecha_hasta']);
            }

            return $query->orderBy('bitacora.fecha_hora', 'desc')
                ->paginate($porPagina);

        } catch (\Exception $e) {
            Log::error("Error obteniendo bitácora filtrada: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Contar registros en bitácora por período
     *
     * @param int $dias - Días a contar hacia atrás
     * @return int - Cantidad de registros
     */
    public static function contar($dias = 30): int
    {
        try {
            return DB::table('bitacora')
                ->where('fecha_hora', '>=', now()->subDays($dias))
                ->count();
        } catch (\Exception $e) {
            Log::error("Error contando bitácora: " . $e->getMessage());
            return 0;
        }
    }
}
