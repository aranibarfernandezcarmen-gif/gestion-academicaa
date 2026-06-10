<x-mail::message>
# Bienvenido al Sistema de Gestión Académica

Estimado(a) {{ $nombre }} {{ $apellido }},

Su cuenta de docente ha sido creada exitosamente. A continuación, encontrará sus credenciales de acceso:

<x-mail::panel>
**Registro (Usuario):** {{ $registro }}
**CI:** {{ $ci }}
**Contraseña:** {{ $ci }}
**Rol:** Docente
</x-mail::panel>

Para iniciar sesión, use el formulario con los siguientes datos:
- **Registro**: {{ $registro }}
- **CI**: {{ $ci }}
- **Contraseña**: {{ $ci }}
- **Rol**: Docente

<x-mail::button url="{{ url('/postularse/ingresar') }}">
Ir al Sistema
</x-mail::button>

Si tiene algún problema, contacte al administrador del sistema.

Saludos,  
**Sistema de Gestión Académica**
</x-mail::message>
