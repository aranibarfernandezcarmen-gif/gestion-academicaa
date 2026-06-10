<x-mail::message>
# Nuevo mensaje de contacto

Se recibió un nuevo mensaje desde el formulario "Contactar a Coordinadores" del sitio web.

<x-mail::panel>
**Correo del remitente:** {{ $correoRemitente }}
**Asunto:** {{ $asunto }}
</x-mail::panel>

**Descripción:**

{{ $descripcion }}

Puede responder directamente a este correo para contactar al remitente.

Saludos,
**Sistema de Gestión Académica**
</x-mail::message>
