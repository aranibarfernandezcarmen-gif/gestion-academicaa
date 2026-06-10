# ⏰ CONFIGURACIÓN DE ZONA HORARIA - BOLIVIA

## Resumen de Cambios

Se ha configurado completamente el sistema para registrar todas las acciones en la **zona horaria de Bolivia (America/La_Paz)**, que es **UTC-4**.

---

## 📋 CAMBIOS REALIZADOS

### 1️⃣ Configuración en Laravel (`config/app.php`)
```php
'timezone' => 'America/La_Paz'
```

✅ Ahora todos los registros de `now()` usarán la hora de Bolivia  
✅ Las fechas en la aplicación se mostrarán en hora de Bolivia  
✅ Los triggers de PostgreSQL también usarán esta zona  

---

### 2️⃣ Middleware Mejorado (`AutomaticBitacoraLogger`)

Ahora registra automáticamente:

#### Accesos de Postulantes:
- ✅ Acceso a formulario de postulación
- ✅ Acceso a login
- ✅ Envío de postulación
- ✅ Confirmación de postulación
- ✅ Acceso a dashboard

#### Acciones de Pago:
- ✅ Creación de pago PayPal
- ✅ Pago completado exitosamente
- ✅ Pago cancelado

#### Otras Acciones:
- ✅ Actualización de perfil
- ✅ Cierre de sesión
- ✅ Todas las rutas GET/POST del sistema

**Todo registrado con:**
- 🕐 Hora de Bolivia (America/La_Paz)
- 🌐 IP del usuario
- 👤 ID de la persona
- 📝 Descripción clara de la acción

---

### 3️⃣ Migración de Zona Horaria en PostgreSQL

Se creó una migración que:
- ✅ Crea función `get_now_bolivia()` para obtener hora exacta
- ✅ Configura la base de datos para usar América/La_Paz
- ✅ Registra la configuración en la bitácora

---

## 🚀 CÓMO ACTIVAR

```bash
# Ejecutar las nuevas migraciones
php artisan migrate

# Verificar en tinker
php artisan tinker
>>> use App\Models\Bitacora
>>> Bitacora::ultimos(10)->get()
```

---

## 📊 QUÉ SE REGISTRA AHORA (CON HORA DE BOLIVIA)

### Ingresos/Accesos:
```
[2026-06-04 14:35:22 Bolivia] Usuario: Juan Pérez
- Acceso a formulario de postulación (IP: 192.168.1.100)
- Acceso a login de postulante (IP: 192.168.1.100)
- Envío de postulación (IP: 192.168.1.100)
- Pago completado exitosamente (IP: 192.168.1.100)
```

### Cambios de Datos:
```
[2026-06-04 14:40:15 Bolivia] Sistema
- [TRIGGER] Nuevo postulante registrado: P001
- [TRIGGER] Estado de postulante cambió de Pendiente a Asignado
- [TRIGGER] Calificación registrada
```

### Errores:
```
[2026-06-04 14:45:30 Bolivia] Sistema
- [ERROR] Error al procesar pago - Excepción...
```

---

## 🔍 VERIFICAR ZONA HORARIA

### Desde Laravel (Tinker):
```php
>>> use Illuminate\Support\Facades\Config;
>>> Config::get('app.timezone')
// Resultado: "America/La_Paz"

>>> now()
// Resultado: 2026-06-04 14:35:22 (Hora de Bolivia)
```

### Desde PostgreSQL:
```sql
SELECT NOW() AT TIME ZONE 'America/La_Paz' as "Hora Bolivia";
SELECT NOW() AT TIME ZONE 'UTC' as "Hora UTC";
```

### Desde Bitácora:
```php
>>> Bitacora::first()->fecha_hora
// Resultado: 2026-06-04 14:35:22 (Hora de Bolivia)
```

---

## 📝 EJEMPLOS DE USO

### Registrar login manual:
```php
use App\Services\BitacoraService;

BitacoraService::registrar(
    "Login de usuario",
    request()->ip(),
    $usuarioId
);
// Se registrará con hora de Bolivia automáticamente
```

### Consultar acciones de hoy (Bolivia):
```php
use App\Models\Bitacora;

$hoy = now()->format('Y-m-d');
$bitacora = Bitacora::whereDate('fecha_hora', $hoy)->get();

foreach ($bitacora as $registro) {
    echo $registro->fecha_hora->format('H:i:s'); // Hora de Bolivia
}
```

### Filtrar por período (Bolivia):
```php
$desde = now()->startOfDay();   // Inicio de hoy (hora Bolivia)
$hasta = now()->endOfDay();     // Fin de hoy (hora Bolivia)

$bitacora = Bitacora::whereBetween('fecha_hora', [$desde, $hasta])
    ->orderBy('fecha_hora', 'desc')
    ->get();
```

---

## ⚠️ IMPORTANTE

### ✅ Qué funciona ahora:
- Todos los timestamps están en hora de Bolivia
- Los triggers registran con NOW() que respeta la zona
- Los usuarios ven horarios en Bolivia
- La auditoría es completamente trazable

### 🔄 Cambios de zona horaria a futuro:
Si en el futuro necesitas cambiar la zona:

```bash
# Cambiar en config/app.php
'timezone' => 'Nueva/Zona'

# Ejecutar (sin necesidad de migración)
php artisan config:cache
```

---

## 📈 VENTAJAS

✅ **Consistencia** - Toda auditoría en misma zona horaria  
✅ **Claridad** - Sin confusión UTC vs hora local  
✅ **Cumplimiento** - Requisito regulatorio boliviano  
✅ **Trazabilidad** - Saber exactamente cuándo pasó cada acción  
✅ **Debugging** - Fácil correlacionar eventos  

---

## 📞 SOPORTE

Para verificar que está funcionando:

```bash
# Ejecutar migración
php artisan migrate

# Entrar a tinker
php artisan tinker

# Verificar últimos registros
>>> use App\Models\Bitacora
>>> Bitacora::ultimos(5)->get()->map(fn($b) => ['accion' => $b->accion, 'hora' => $b->fecha_hora])
```

Deberías ver registros con timestamp en formato: `2026-06-04 14:35:22`
(Hora de Bolivia: UTC-4)
