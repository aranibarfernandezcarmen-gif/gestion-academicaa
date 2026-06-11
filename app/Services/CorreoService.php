<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envío de correos por la API HTTP de Brevo (https://api.brevo.com).
 *
 * Render (plan gratis) bloquea el SMTP saliente, por eso NO funciona Gmail/SMTP.
 * Brevo usa HTTPS (puerto 443), que sí está permitido, y su plan gratis permite
 * enviar a cualquier destinatario verificando solo el remitente.
 *
 * Requiere la variable de entorno BREVO_API_KEY. Si no está configurada, no falla:
 * solo registra una advertencia y devuelve false (el resto del flujo continúa).
 */
class CorreoService
{
    public static function enviar(string $to, ?string $toName, string $subject, string $htmlContent): bool
    {
        $apiKey = env('BREVO_API_KEY');
        if (empty($apiKey)) {
            Log::warning('[CorreoService] BREVO_API_KEY no configurada; correo NO enviado a ' . $to);
            return false;
        }

        $fromEmail = env('MAIL_FROM_ADDRESS', 'fcalani38@gmail.com');
        $fromName  = env('MAIL_FROM_NAME', 'Sistema de Gestion Academica');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'api-key'      => $apiKey,
                    'accept'       => 'application/json',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.brevo.com/v3/smtp/email', [
                    'sender'      => ['email' => $fromEmail, 'name' => $fromName],
                    'to'          => [['email' => $to, 'name' => $toName ?: $to]],
                    'subject'     => $subject,
                    'htmlContent' => $htmlContent,
                ]);

            if ($response->successful()) {
                Log::info('[CorreoService] Correo enviado a ' . $to);
                return true;
            }

            Log::error('[CorreoService] Brevo respondió ' . $response->status() . ': ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('[CorreoService] Excepción enviando correo: ' . $e->getMessage());
            return false;
        }
    }
}
