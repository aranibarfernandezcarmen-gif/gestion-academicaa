<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CsrfTokenFix
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Si es una solicitud GET, asegurarse de que la sesión tiene un token CSRF válido
        if ($request->isMethod('get')) {
            // Regenerar el token CSRF si es necesario
            if (!$request->session()->has('_token')) {
                $request->session()->regenerateToken();
            }
        }

        return $next($request);
    }
}
