<?php

namespace App\Http\Middleware;

use App\Services\BitacoraService;
use Closure;
use Illuminate\Http\Request;

class AutomaticBitacoraLogger
{
    /**
     * Rutas que se deben excluir del registro de auditoría
     * - cu20.stream: API streaming en tiempo real
     * - laravel-admin.dashboard: Panel de administración
     * - postulacion.login.submit: El login se registra en el controlador DESPUÉS de actualizar sesión
     * - postulacion.logout: El logout se registra en el controlador ANTES de limpiar sesión
     * - api.validar-sesion: Validación interna de sesión via AJAX (no es acción del usuario)
     */
    private array $exclude = [
        'cu20.stream',
        'laravel-admin.dashboard',
        'postulacion.login.submit',
        'postulacion.logout',
        'api.validar-sesion',
    ];

    public function handle(Request $request, Closure $next)
    {
        $persona_id = $request->session()->get('persona_id');

        if ($persona_id) {
            $routeName = $request->route()?->getName();
            $method = $request->method();
            $path = $request->path();

            // Excluir rutas que no queremos registrar
            if (!in_array($routeName, $this->exclude)) {
                // Construir descripción de la acción
                $accion = $this->construirDescripcion($routeName, $method, $path);

                // Registrar en bitácora con zona horaria de Bolivia
                BitacoraService::registrar(
                    $accion,
                    $request->ip(),
                    $persona_id
                );
            }
        }

        return $next($request);
    }

    /**
     * Construir descripción legible de la acción
     * Genera descripciones dinámicas según el rol del usuario actual
     */
    private function construirDescripcion(?string $routeName, string $method, string $path): string
    {
        if (!$routeName) {
            return "Acceso {$method} {$path}";
        }

        // Obtener rol actual del usuario desde sesión
        $rolActual = request()->session()->get('role');

        // Mapear rol a descripción legible
        $roleDescripcion = [
            'postulante' => 'Postulante',
            'docente' => 'Docente',
            'administrativo' => 'Administrativo',
            'coordinador' => 'Coordinador',
            'decano' => 'Decano',
        ];

        $rolLabel = isset($roleDescripcion[$rolActual]) 
            ? $roleDescripcion[$rolActual] 
            : $rolActual;

        // Acciones dinámicas según rol
        if ($routeName === 'postulacion.dashboard') {
            return "Acceso a dashboard de {$rolLabel}, Sesion Validada";
        }

        if ($routeName === 'postulacion.actualizarPerfil') {
            return "Actualización de perfil de {$rolLabel}";
        }

        // Acciones estáticas
        $acciones = [
            'postulacion.form' => 'Acceso a formulario de postulación',
            'postulacion.login' => 'Acceso a login',
            'postulacion.store' => 'Envío de postulación',
            'postulacion.success' => 'Ver confirmación de postulación',
            'postulacion.logout' => 'Cierre de sesión',
            'payment.create' => 'Creación de pago PayPal',
            'payment.success' => 'Pago completado exitosamente',
            'payment.cancel' => 'Pago cancelado',
        ];

        return $acciones[$routeName] ?? "Acceso {$method} {$routeName}";
    }
}
