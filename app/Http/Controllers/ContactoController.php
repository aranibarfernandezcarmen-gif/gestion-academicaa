<?php

namespace App\Http\Controllers;

use App\Mail\ContactoCoordinadores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactoController extends Controller
{
    public function enviar(Request $request)
    {
        $validated = $request->validate([
            'correo' => 'required|email|max:255',
            'asunto' => 'required|string|max:255',
            'descripcion' => 'required|string|max:2000',
        ]);

        try {
            Mail::to('fcalani38@gmail.com')->send(
                new ContactoCoordinadores($validated['correo'], $validated['asunto'], $validated['descripcion'])
            );
        } catch (\Exception $e) {
            Log::error('[ContactoController] Error al enviar mensaje de contacto', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo enviar el mensaje. Intente nuevamente más tarde.',
            ], 500);
        }

        return response()->json([
            'message' => 'Mensaje enviado correctamente.',
        ]);
    }
}
