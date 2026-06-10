<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credenciales de Acceso - COPA FICCT</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(to right, #1e40af, #1e3a8a);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px 20px;
        }
        .content h2 {
            color: #1e40af;
            font-size: 22px;
            margin-top: 0;
        }
        .content p {
            margin: 15px 0;
            line-height: 1.8;
        }
        .credentials-box {
            background-color: #f0f4f8;
            border-left: 4px solid #1e40af;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .credential-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .credential-row:last-child {
            border-bottom: none;
        }
        .credential-label {
            font-weight: bold;
            color: #1e40af;
            min-width: 150px;
        }
        .credential-value {
            font-family: 'Courier New', monospace;
            color: #333;
            word-break: break-all;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background-color: #1e40af;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #1e3a8a;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>¡Bienvenido a COPA!</h1>
            <p>Cursos Preuniversitarios - FICCT</p>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>Hola {{ $nombre }},</h2>
            <p>Tu pago ha sido procesado exitosamente. A continuación encontrarás tus credenciales de acceso al sistema:</p>

            <!-- Credentials Box -->
            <div class="credentials-box">
                <div class="credential-row">
                    <span class="credential-label">Código de Registro:</span>
                    <span class="credential-value">{{ $codigo }}</span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Contraseña:</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>

            <!-- Warning -->
            <div class="warning">
                <strong>⚠️ Importante:</strong> Por favor, guarda estas credenciales en un lugar seguro. No compartas tu contraseña con nadie. Te recomendamos cambiar tu contraseña después del primer acceso.
            </div>

            <!-- Button -->
            <div class="button-container">
                <a href="{{ $loginUrl }}" class="button">Ingresar al Sistema</a>
            </div>

            <!-- Instructions -->
            <h3 style="color: #1e40af; margin-top: 30px;">Próximos pasos:</h3>
            <ol style="line-height: 2;">
                <li>Ingresa al sistema usando el código de registro y contraseña</li>
                <li>Completa tu perfil con información adicional si es necesario</li>
                <li>Revisa el calendario de clases y contenidos del curso</li>
                <li>Participa activamente en el programa</li>
            </ol>

            <p style="margin-top: 30px; color: #666;">
                Si tienes problemas accediendo al sistema o necesitas asistencia, no dudes en contactar al departamento de admisiones de la FICCT.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Equipo COPA FICCT</strong></p>
            <p>Cursos Preuniversitarios - Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones</p>
            <p>Universidad Autónoma Gabriel René Moreno</p>
        </div>
    </div>
</body>
</html>
