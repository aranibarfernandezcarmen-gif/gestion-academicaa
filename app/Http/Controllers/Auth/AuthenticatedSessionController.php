<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SessionManager;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Regenerar sesión de Laravel
        $request->session()->regenerate();

        // Obtener el usuario autenticado
        $user = Auth::user();
        
        // ✅ REGISTRAR NUEVA SESIÓN ÚNICA
        // Esto desactiva todas las sesiones anteriores del usuario
        SessionManager::createUserSession(
            $user->id,
            $request,
            $request->session()->getId()
        );
        
        // Guardar ID de persona en la sesión para acceso rápido
        $request->session()->put('persona_id', $user->id);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $sessionId = $request->session()->getId();

        // ✅ CERRAR SESIÓN en user_sessions
        if ($user && $sessionId) {
            SessionManager::closeSession($sessionId);
            
            // ✅ REGISTRAR LOGOUT en bitácora
            BitacoraService::registrar(
                "Sesión cerrada - Logout realizado",
                $request->ip(),
                $user->id
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
