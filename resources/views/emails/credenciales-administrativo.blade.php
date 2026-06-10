<x-mail::message>
# Bienvenido al Sistema de Gestión Académica

Estimado/a **{{ $nombre }} {{ $apellido }}**,

Tu cuenta como **Administrativo** ha sido registrada exitosamente en el sistema.

## Tus Credenciales de Acceso

| Campo | Valor |
|-------|-------|
| **Registro** | {{ $registro }} |
| **CI** | {{ $ci }} |
| **Contraseña** | {{ $ci }} |
| **Profesión** | {{ $profesion ?? 'No especificada' }} |
| **Nro. Título** | {{ $nroTitulo ?? 'No especificado' }} |

## Para Iniciar Sesión

1. Ingresa a: [Sistema de Gestión Académica]({{ url('/postularse/ingresar') }})
2. Utiliza tu **Registro** como usuario
3. Tu contraseña inicial es tu **CI**
4. Se recomienda cambiar tu contraseña en tu primer acceso

## Información Importante

- Por favor guarda estas credenciales en un lugar seguro
- No compartas tu contraseña con nadie
- Si tienes problemas para acceder, contacta al administrador del sistema

---

**Sistema de Gestión Académica**
</x-mail::message>
