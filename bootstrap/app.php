<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Excluir rutas del middleware CSRF:
        // - actualizar-perfil: evita problemas de sincronización de tokens después de session regeneration
        // - rutas de recuperación: usuario no autenticado aún
        $middleware->validateCsrfTokens(except: [
            '/postularse/actualizar-perfil',
            '/postularse/solicitar-recuperacion',
            '/postularse/verificar-codigo-recuperacion',
            '/postularse/restablecer-contraseña',
        ]);

        // SessionCheck valida sesiones únicas en TODAS las rutas autenticadas
        // Se ejecuta después del middleware de autenticación de Laravel
        // Si detecta que la sesión fue invalidada (login desde otro dispositivo),
        // cierra automáticamente la sesión antigua
        $middleware->web(append: [
            \App\Http\Middleware\SessionCheck::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\AutomaticBitacoraLogger::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Registrar alias de middleware personalizado
        $middleware->alias([
            'session.check' => \App\Http\Middleware\SessionCheck::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
