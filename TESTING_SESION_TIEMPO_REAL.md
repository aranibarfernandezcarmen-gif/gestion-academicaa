# Testing: Validación de Sesión en Tiempo Real

## Objetivo
Verificar que cuando un usuario inicia sesión en un segundo dispositivo, el primer dispositivo se redirija automáticamente a la página principal **de forma inmediata** sin quedarse cargando.

---

## Requisitos Previos

1. ✅ Servidor Laravel corriendo en `http://localhost:8000`
2. ✅ Base de datos con migraciones ejecutadas
3. ✅ Frontend compilado (`npm run build`)
4. ✅ Mínimo 2 navegadores o pestañas con incógnita (simulan dispositivos diferentes)

---

## Flujo de Prueba

### PASO 1: Preparación

```bash
# En terminal 1 - Ejecutar servidor Laravel
cd "ruta-del-proyecto"
php artisan serve

# En terminal 2 - Ver logs en tiempo real (opcional)
tail -f storage/logs/laravel.log
```

---

### PASO 2: Abrir Dos Navegadores (Dispositivos Simulados)

**Dispositivo A (Móvil):**
- Abre navegador 1 en modalidad incógnita
- Dirección: `http://localhost:8000/postularse/ingresar`

**Dispositivo B (Computadora):**
- Abre navegador 2 (o pestaña en modalidad incógnita de otro navegador)
- Dirección: `http://localhost:8000/postularse/ingresar`

---

### PASO 3: Login en Dispositivo A (Móvil)

En el **navegador 1 (Móvil)**:

1. Completa el formulario:
   ```
   CI: 123
   Rol: decano (o el que tengas en la BD)
   Contraseña: 123
   ```

2. Click "Ingresar"

3. **Espera a que se cargue el dashboard completo**
   - Verás el header azul con la facultad
   - Botones P1, P2, P3, P4, P5
   - Datos del usuario

4. **NO cierres esta ventana** - La dejaremos abierta para monitorear

**Consola del Navegador 1 (Abierta mediante F12):**
```
✓ Deberías ver logs como:
[PostulacionDashboard] Mounted - Iniciando validación...
🔄 Iniciando validación de sesión cada 2 segundos
```

---

### PASO 4: Login en Dispositivo B (Computadora) con MISMO Usuario

En el **navegador 2 (Computadora)**:

1. Completa el formulario con **MISMO usuario**:
   ```
   CI: 123
   Rol: decano
   Contraseña: 123
   ```

2. Click "Ingresar"

3. **Espera a que se cargue el dashboard**

4. Verifica en la consola (F12) que ves:
   ```
   ✓ Sesión activa, persona_id: 1, role: decano
   ```

---

### PASO 5: Monitorear Dispositivo A (el punto crítico)

**En el navegador 1 (que dejamos abierto):**

1. **En los siguientes 2-3 segundos** deberías ver:

**Opción A - Redirección automática (CORRECTO):**
```
⚠️ Sesión invalidada detectada
→ [Redirige a página principal inmediatamente]
```

**La página debería cambiar automáticamente a la página principal (`/`) sin quedarse cargando**

**Opción B - Redirección lenta (PROBLEMA):**
```
La página se queda cargando durante varios segundos y luego redirige
```

**Opción C - Sin redirección (PROBLEMA):**
```
La página sigue mostrando el dashboard aunque la sesión sea inválida
```

---

### PASO 6: Verificar que NO Puede Volver Atrás

Una vez redirigido a la página principal en Dispositivo A:

1. Intenta presionar el botón "Atrás" del navegador
2. **Deberías ver:**
   - NO vuelve al dashboard cacheado
   - Se queda en la página principal
   - O redirige nuevamente a `/`

---

## Criterios de Éxito ✅

**Todo correcto si:**

- ✅ Navegador 1 se redirije a `/` en **máximo 3 segundos** (no se queda cargando)
- ✅ La redirección es automática (sin intervención del usuario)
- ✅ NO permite acceder al dashboard presionando "Atrás"
- ✅ No hay errores en la consola (F12)
- ✅ Navegador 2 sigue mostrando el dashboard normalmente

**Logs esperados en Navegador 1 (consola F12):**
```
🔄 Iniciando validación de sesión cada 2 segundos
👀 Pestaña visible, validando sesión...
[Session Validation] Response status: 200 (primera validación OK)
⚠️ Sesión invalidada detectada  ← En algún momento
→ window.location.href = /?error=... (redirección)
```

---

## Logs Esperados en Laravel

**storage/logs/laravel.log:**

```
[2026-06-05 12:00:00] local.INFO: GET /postularse/ingresar (login navegador 1)
[2026-06-05 12:00:05] local.INFO: POST /postularse/ingresar (envío login navegador 1)
[2026-06-05 12:00:06] local.INFO: Bitácora: Inicio de sesión como decano
[2026-06-05 12:00:08] local.INFO: GET /api/validar-sesion (navegador 1 validando)
[2026-06-05 12:00:08] local.INFO: Response: 200 OK (sesión válida)

[2026-06-05 12:00:12] local.INFO: POST /postularse/ingresar (envío login navegador 2)
[2026-06-05 12:00:13] local.INFO: Bitácora: Nueva sesión iniciada - desktop
[2026-06-05 12:00:13] local.INFO: user_sessions: Sesión anterior deactivada

[2026-06-05 12:00:15] local.INFO: GET /api/validar-sesion (navegador 1 validando)
[2026-06-05 12:00:15] local.INFO: Response: 401 UNAUTHORIZED
[2026-06-05 12:00:15] local.INFO: Reason: "Sesión cerrada en otro dispositivo"
```

---

## Verificar en Base de Datos

**Durante la prueba, en otro terminal:**

```bash
php artisan tinker
>>> DB::table('user_sessions')->where('is_active', true)->get();

// Deberías ver:
// - 0 sesiones activas después de que navegador 1 se redirija
// - O solo la sesión del navegador 2
```

---

## Solución de Problemas

### Problema 1: Navegador 1 no se redirije
**Causa:** El composable `useSessionValidation` no está siendo usado correctamente

**Solución:**
```javascript
// En PostulacionDashboard.vue, verifica:
import useSessionValidation from '@/Composables/useSessionValidation';
const { validateSession } = useSessionValidation();
// El composable debe iniciar automáticamente en onMounted
```

### Problema 2: La redirección es muy lenta (>5 segundos)
**Causa:** El intervalo de validación es muy largo o hay timeout en la red

**Solución:**
- Cambiar intervalo de 2 segundos a 1 segundo en `useSessionValidation.js`
- Reducir timeout de 3000ms a 1500ms

### Problema 3: Errores en consola
**Causa:** Path incorrecto al composable o import malo

**Solución:**
```javascript
// Verificar que existe el archivo:
resources/js/Composables/useSessionValidation.js

// Y que el import es correcto:
import useSessionValidation from '@/Composables/useSessionValidation';
```

---

## Testing Avanzado (Opcional)

### Test 1: Validar que la sesión en BD se marca como inactiva
```bash
php artisan tinker

>>> $sesiones = DB::table('user_sessions')
    ->where('id_persona', 1)
    ->orderBy('created_at', 'desc')
    ->limit(2)
    ->get();

>>> dd($sesiones);
// Debería mostrar:
// - Sesión 1: is_active = false (navegador 1)
// - Sesión 2: is_active = true (navegador 2)
```

### Test 2: Validar la bitácora
```bash
php artisan tinker

>>> DB::table('bitacora')
    ->where('id_persona', 1)
    ->orderBy('fecha_hora', 'desc')
    ->limit(5)
    ->get();
```

### Test 3: Logout en navegador 2 y verificar
```
1. En navegador 2, click en "Cerrar Sesión"
2. Debería redirigir a / con mensaje "Cierre de sesión exitoso"
3. En navegador 1 (ya en /), intentar ir a /postularse/entrada
4. Debería redirigir a login
```

---

## Resumen de Cambios Implementados

| Componente | Cambio |
|-----------|--------|
| `useSessionValidation.js` | ✨ Nuevo composable para validación en tiempo real |
| `PostulacionController::validarSesionAjax` | 🔧 Mejorado para validar en tabla `user_sessions` |
| `PostulacionDashboard.vue` | 🔧 Integración de composable en lugar de función local |
| `SessionCheck.php` | 🔧 Ya valida sesión activa en tabla |

---

## Cuándo Considerar Completado

✅ **Completado cuando:**
1. Navegador 1 se redirije en máximo 3 segundos
2. La redirección es automática (sin lag visible)
3. NO permite volver atrás al dashboard
4. Sesión se marca como inactiva en BD
5. Logs muestran todo correctamente

---

## Fecha de Implementación
- **Cambios realizados:** 2026-06-05
- **Testing recomendado:** Inmediatamente después
