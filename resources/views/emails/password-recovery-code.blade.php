<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .code-container {
            background-color: #f9fafb;
            border: 2px solid #1e40af;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #1e40af;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }
        .expires {
            font-size: 12px;
            color: #ff6b6b;
            margin-top: 10px;
            font-weight: 500;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #92400e;
        }
        .button-container {
            text-align: center;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #1e40af;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recuperación de Contraseña</h1>
            <p>FICCT - Cursos Preuniversitarios (CUP)</p>
        </div>

        <div class="content">
            <div class="greeting">
                Hola {{ $personaName }},
            </div>

            <div class="message">
                Has solicitado restablecer tu contraseña. Por favor, utiliza el siguiente código de verificación para continuar con el proceso de recuperación:
            </div>

            <div class="code-container">
                <div class="code">{{ $verificationCode }}</div>
                <div class="expires">Válido por {{ $expiresInMinutes }} minutos</div>
            </div>

            <div class="message">
                Este código es personal e intransferible. No lo compartas con nadie más.
            </div>

            <div class="warning">
                <strong>⚠️ Importante:</strong> Si no solicitaste esta recuperación, por favor ignora este mensaje. Tu cuenta seguirá siendo segura con tu contraseña actual.
            </div>

            <div class="message">
                Si necesitas ayuda, contacta con el equipo de soporte de FICCT.
            </div>
        </div>

        <div class="footer">
            <p>Este es un mensaje automático. Por favor, no respondas a este correo.</p>
            <p>&copy; {{ date('Y') }} FICCT - Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
