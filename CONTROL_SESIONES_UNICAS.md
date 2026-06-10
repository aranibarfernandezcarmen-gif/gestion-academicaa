# Control de Sesiones Únicas por Usuario - Documentación

## Descripción General

Se implementó un sistema de **control de sesiones únicas** que **invalida automáticamente sesiones anteriores** cuando un usuario inicia sesión desde un nuevo dispositivo.

### Comportamiento

```
Escenario Actual (Mejorado):
1. Usuario inicia sesión en MÓVIL
   → Se crea sesión en tabla user_sessions → ✓ ACTIVA

2. Mismo usuario inicia sesión en COMPUTADORA  
   → Sesión MÓVIL se deactiva automáticamente → ✗ INACTIVA
   → Nueva sesión COMPUTADORA se crea → ✓ ACTIVA

3. Resultado: Solo 1 sesión activa en todo momento
```

---

## Componentes Implementados

### 1. Tabla `user_sessions` (Migración)
**Archivo:** `database/migrations/2026_06_05_000000_create_user_sessions_table.php`

Almacena información de sesiones activas:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `id_persona` | bigint | FK a tabla persona |
| `session_id` | string | ID único de sesión de Laravel |
| `device_type` | string | 'mobile', 'tablet', 'desktop' |
| `browser` | string | Navegador detectado |
| `os` | string | Sistema operativo |
| `user_agent` | string | User-Agent completo |
| `ip_address` | string | IP origen de la sesión |
| `last_activity` | timestamp | Última actividad |
| `is_active` | boolean | Estado de la sesión |
| `created_at` | timestamp | Creación |
| `updated_at` | timestamp | Actualización |

### 2. Modelo `UserSession`
**Archivo:** `app/Models/UserSession.php`

ORM Eloquent para la tabla `user_sessions`:

```php
// Obtener sesiones activas de un usuario
UserSession::whereForUser($personaId)->active()->get();

// Scopes disponibles
$session->active();        // Solo sesiones activas
$session->inactive();      // Solo sesiones inactivas
$session->forUser($id);    // Sesiones de un usuario
```

### 3. Servicio `SessionManager`
**Archivo:** `app/Services/SessionManager.php`

Lógica centralizada para gestión de sesiones:

#### `createUserSession($personaId, $request, $sessionId): bool`
- **Función:** Crear nueva sesión e **invalidar TODAS las anteriores**
- **Pasos:**
  1. Detecta tipo de dispositivo (mobile/tablet/desktop)
  2. Extrae información del navegador
  3. **Desactiva todas las sesiones anteriores del usuario**
  4. Crea nueva sesión activa
  5. Registra en bitácora
- **Uso en login:** Se llama después de actualizar `$request->session()`

#### `isSessionValid($personaId, $sessionId): bool`
- Valida que una sesión sea activa
- Devuelve `false` si fue revocada
- **Uso en middleware:** Verifica que la sesión no fue invalidada por login desde otro dispositivo

#### `updateLastActivity($sessionId): bool`
- Actualiza timestamp de última actividad
- **Uso en middleware:** En cada request protegido

#### `getActiveSessions($personaId): Collection`
- Obtiene todas las sesiones activas de un usuario
- Útil para UI de "sesiones abiertas"

#### `closeSession($sessionId): bool`
- Cierra una sesión específica (logout)

#### `closeAllUserSessions($personaId): bool`
- Fuerza logout en TODOS los dispositivos del usuario
- Útil para administración de seguridad

#### `cleanupInactiveSessions($minutosInactividad): int`
- Limpia sesiones inactivas (comando de mantenimiento)

---

## Integración en el Código

### 1. Controllers - PostulacionController

#### En `login()` (línea ~190):
```php
// ✅ Crear registro de sesión y INVALIDAR sesiones anteriores
SessionManager::createUserSession(
    $persona->id,
    $request,
    $request->session()->getId()
);
```

#### En `logout()` (línea ~360):
```php
// ✅ Cerrar sesión en tabla user_sessions
SessionManager::closeSession($sessionId);
```

### 2. Middleware - SessionCheck

#### Validación de sesión activa (línea ~40):
```php
// ✅ VALIDAR que la sesión está activa en user_sessions
if (!SessionManager::isSessionValid($personaId, $sessionId)) {
    $request->session()->flush();
    return redirect('/')->with('error', 'Su sesión ha sido cerrada. Inicie sesión nuevamente.');
}

// ✅ Actualizar última actividad
SessionManager::updateLastActivity($sessionId);
```

---

## Flujo Completo de Uso

### Caso: Usuario accede desde móvil, luego desde PC

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USUARIO ACCEDE DESDE MÓVIL                              │
└─────────────────────────────────────────────────────────────┘
   POST /postularse/ingresar (ci=123, role=decano)
         ↓
   PostulacionController::login()
         ↓
   SessionManager::createUserSession(id_persona=5, request, sessionId=abc123)
         ↓
   user_sessions:
   ├─ ID=1, persona_id=5, device_type='mobile', is_active=TRUE ✓
         ↓
   Redirect → Dashboard de Decano

┌─────────────────────────────────────────────────────────────┐
│ 2. MISMO USUARIO ACCEDE DESDE COMPUTADORA                  │
└─────────────────────────────────────────────────────────────┘
   POST /postularse/ingresar (ci=123, role=decano)
         ↓
   PostulacionController::login()
         ↓
   SessionManager::createUserSession(id_persona=5, request, sessionId=def456)
         ↓
   user_sessions:
   ├─ ID=1, persona_id=5, device_type='mobile', is_active=FALSE ✗
   ├─ ID=2, persona_id=5, device_type='desktop', is_active=TRUE ✓

┌─────────────────────────────────────────────────────────────┐
│ 3. USUARIO INTENTA ACCEDER A RUTA PROTEGIDA DESDE MÓVIL    │
└─────────────────────────────────────────────────────────────┘
   GET /postularse/entrada
         ↓
   SessionCheck middleware
         ↓
   SessionManager::isSessionValid(persona_id=5, sessionId=abc123)
         ↓
   Busca en user_sessions donde session_id='abc123' AND is_active=true
   NO ENCUENTRA (fue invalidada)
         ↓
   return redirect('/')->with('error', 'Su sesión ha sido cerrada...')
         ↓
   Usuario redirigido a login ✓ (seguro)
```

---

## Detección de Dispositivo

Usando la librería `Jenssegers/Agent`:

```
User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)
  → device_type = 'mobile'
  → browser = 'Safari'
  → os = 'iOS'

User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
  → device_type = 'desktop'
  → browser = 'Chrome'
  → os = 'Windows'

User-Agent: Mozilla/5.0 (iPad; CPU OS 13_3 like Mac OS X)
  → device_type = 'tablet'
  → browser = 'Safari'
  → os = 'iOS'
```

---

## Registros en Bitácora

Cuando se crea una sesión, se registra:

```
Acción: "Nueva sesión iniciada - mobile (Safari, 192.168.1.100)"
Fecha: 2026-06-05 12:30:45
Usuario: Natalia Cruz (decano)
IP: 192.168.1.100
```

---

## Pruebas Automatizadas

### Comando de Prueba
```bash
php artisan test:session-control
```

**Archivo:** `app/Console/Commands/TestSessionControl.php`

**Qué prueba:**
1. Simula login desde móvil
2. Verifica que sesión móvil está activa
3. Simula login desde desktop
4. Verifica que solo sesión desktop está activa
5. Verifica que sesión móvil fue deactivada
6. Valida que `isSessionValid()` retorna correcto

**Salida esperada:**
```
✅ TODAS LAS PRUEBAS PASARON!
   • La sesión móvil fue deactivada automáticamente
   • Solo la sesión desktop sigue activa
   • El control de sesión única funciona perfectamente
```

---

## Escenarios de Seguridad

### ✅ Escenario 1: Login normal
1. Usuario inicia sesión
2. Sesión creada en `user_sessions`
3. Acceso a rutas protegidas OK

### ✅ Escenario 2: Login desde nuevo dispositivo
1. Usuario en dispositivo A (sesión activa)
2. Usuario inicia sesión en dispositivo B
3. Sesión dispositivo A se deactiva automáticamente
4. Usuario intenta acceder desde A → **REDIRIGIDO A LOGIN**

### ✅ Escenario 3: Logout
1. Usuario hace logout
2. SessionManager::closeSession() marca como inactiva
3. Intento de reacceder con cookies antiguas → **REDIRIGIDO A LOGIN**

### ✅ Escenario 4: Sesión forzada cerrada (admin)
1. Admin ejecuta `SessionManager::closeAllUserSessions($personaId)`
2. Todas las sesiones del usuario se marcan como inactivas
3. Usuario en cualquier dispositivo es deslogueado en siguiente request

---

## Comandos Disponibles

### Limpiar sesiones inactivas (mayor a 24 horas)
```bash
php artisan tinker
>>> App\Services\SessionManager::cleanupInactiveSessions(1440)
```

### Ver sesiones activas de un usuario (desde tinker)
```bash
>>> App\Services\SessionManager::getActiveSessions(1)
=> Collection
```

---

## Implicaciones de Seguridad

### Positivas ✅
- Solo 1 sesión activa por usuario en todo momento
- Imposible compartir sesiones entre dispositivos
- Auto-protección contra robo de cookies (si se intercepan, se invalidan al login siguiente)
- Auditoría completa de dispositivos por usuario

### Consideraciones 🔄
- Usuario no puede estar en múltiples dispositivos simultáneamente
- Si se cambia de dispositivo, sesión anterior se cierra automáticamente
- Es el comportamiento de **apps móviles modernas** (WhatsApp Web, Gmail, etc.)

---

## Configuración Futura

### Opcional: Permitir múltiples sesiones
Si en futuro se requiere cambiar a múltiples sesiones simultáneas:

1. Modificar `createUserSession()` para NO invalidar anteriores
2. Cambiar middleware a validar sesión válida (sin invalidarla)
3. Agregar UI para mostrar "dispositivos conectados"

### Opcional: Sesiones con timeout
Agregar limpieza automática de sesiones con:
```bash
php artisan schedule:work  # Ejecutar en background
```

---

## Resumen de Cambios

| Archivo | Cambio |
|---------|--------|
| `database/migrations/2026_06_05_000000_create_user_sessions_table.php` | Nueva migración |
| `app/Models/UserSession.php` | Nuevo modelo |
| `app/Services/SessionManager.php` | Nuevo servicio |
| `app/Http/Controllers/PostulacionController.php` | +SessionManager::createUserSession() en login |
| `app/Http/Controllers/PostulacionController.php` | +SessionManager::closeSession() en logout |
| `app/Http/Middleware/SessionCheck.php` | +Validación de sesión activa |
| `app/Console/Commands/TestSessionControl.php` | Comando de pruebas |
| `composer.json` | +jenssegers/agent (detección de dispositivo) |

---

## Testing Manual en la Web

### Pasos para probar:

1. **Abre 2 navegadores** (o navegador + incógnita)
   
2. **Dispositivo A (Móvil):**
   - Accede a `http://localhost:8000/postularse/ingresar`
   - Inicia sesión como Decano (ci=123, password=123, role=decano)
   - Verifica que ves el dashboard
   - **NO cierres esta ventana**

3. **Dispositivo B (PC):**
   - En OTRA ventana del navegador, accede a `http://localhost:8000/postularse/ingresar`
   - Inicia sesión con **MISMO usuario**
   - Verifica que ves el dashboard

4. **De vuelta en Dispositivo A:**
   - Intenta recargar o acceder a una ruta protegida
   - **Deberías ser redirigido al login** ❌
   - Verifica el error: "Su sesión ha sido cerrada..."

5. **Verificar en BD:**
   - Abre phpmyadmin o tu cliente SQL
   - Consulta: `SELECT * FROM user_sessions WHERE is_active = true;`
   - Deberías ver **solo 1 sesión activa** (la de Dispositivo B)

---

## Estado de Implementación

- ✅ Tabla `user_sessions` creada
- ✅ Modelo `UserSession` creado
- ✅ Servicio `SessionManager` implementado
- ✅ Integración en `PostulacionController` login/logout
- ✅ Validación en middleware `SessionCheck`
- ✅ Detección de dispositivo (Jenssegers/Agent)
- ✅ Comando de pruebas automatizadas
- ✅ Pruebas unitarias pasadas ✓
- ⏳ Testing manual en web (próximo paso)

---

## Fecha de Implementación
- **Inicio:** 2026-06-05
- **Completado:** 2026-06-05
- **Testing:** En progreso
