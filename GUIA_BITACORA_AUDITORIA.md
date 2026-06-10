# 📋 GUÍA DE USO - SISTEMA HÍBRIDO DE AUDITORÍA (Bitácora)

## Resumen de Cambios

Se ha implementado un **sistema híbrido de auditoría** que combina:
- **Triggers SQL** - Capturan cambios automáticos a nivel de base de datos
- **BitacoraService Mejorado** - Registra acciones con contexto completo desde la aplicación
- **Modelo Bitacora** - Proporciona scopes y métodos útiles para consultas

---

## 🔧 IMPLEMENTACIÓN

### 1️⃣ Ejecutar la Migración

```bash
php artisan migrate
```

Esto crea los siguientes triggers:
- `bitacora_insert_postulante` - Registra nuevos postulantes
- `bitacora_update_postulante` - Registra cambios en estado de postulantes
- `bitacora_insert_calificacion` - Registra nuevas calificaciones
- `bitacora_update_calificacion` - Registra cambios en calificaciones
- `bitacora_insert_pago` - Registra nuevos pagos
- `bitacora_update_pago` - Registra cambios en pagos
- `bitacora_insert_inscripcion` - Registra nuevas inscripciones
- `bitacora_insert_grupo` - Registra nuevos grupos
- `bitacora_delete_postulante` - Registra eliminación de postulantes

---

## 📝 USOS DEL BitacoraService

### Uso Básico (Como ya lo usabas)
```php
use App\Services\BitacoraService;

// Registrar una acción
BitacoraService::registrar(
    "Registro de postulante P001",
    request()->ip(),
    $personaId
);
```

### Nuevos Métodos Especializados

#### Registrar Creación
```php
// Más legible y estructurado
BitacoraService::registrarCreacion(
    'postulante',        // tabla
    $postulanteId,       // ID del registro
    ['registro' => 'P001', 'estado' => 'Pendiente']  // datos creados
);
```

#### Registrar Actualización
```php
BitacoraService::registrarActualizacion(
    'calificacion',      // tabla
    $calificacionId,     // ID del registro
    ['nota1' => ['100', '85'], 'nota2' => ['90', '92']]  // cambios
);
```

#### Registrar Eliminación
```php
BitacoraService::registrarEliminacion(
    'postulante',        // tabla
    $postulanteId        // ID del registro
);
```

#### Registrar Errores
```php
try {
    // operación riesgosa
} catch (\Exception $e) {
    BitacoraService::registrarError(
        "Error al procesar pago",
        $e
    );
}
```

---

## 🔍 USOS DEL MODELO Bitacora

### Consultas Básicas

#### Obtener últimos 50 registros
```php
use App\Models\Bitacora;

$bitacora = Bitacora::ultimos(50)->get();
```

#### Obtener registros de un usuario
```php
$usuario_id = 5;
$bitacora = Bitacora::delUsuario($usuario_id)->get();
```

#### Obtener registros de un período
```php
$desde = now()->subDays(7);
$hasta = now();
$bitacora = Bitacora::delPeriodo($desde, $hasta)->get();
```

#### Obtener registros por acción
```php
$bitacora = Bitacora::accion('postulante')->get();
```

### Consultas Avanzadas

#### Solo registros de Triggers
```php
$bitacora = Bitacora::deTriggers()->get();
```

#### Solo registros de Errores
```php
$errores = Bitacora::deErrores()->get();
```

#### Registros recientes (últimas 24 horas)
```php
$bitacora = Bitacora::recientes(24)->get();
```

### Métodos Útiles

```php
$registro = Bitacora::first();

// Obtener nombre completo del usuario
$usuario = $registro->getNombreUsuarioCompleto();

// Obtener tipo de acción
$tipo = $registro->getTipoAccion();  // 'TRIGGER', 'ERROR', 'CREATE', 'UPDATE', 'DELETE'

// Obtener ícono de Bootstrap
$icono = $registro->getIconoTipoAccion();  // 'database', 'exclamation-circle', etc

// Obtener color de Bootstrap
$color = $registro->getColorTipoAccion();  // 'primary', 'danger', 'success', etc
```

---

## 💡 EJEMPLO COMPLETO EN UN CONTROLLER

```php
<?php

namespace App\Http\Controllers;

use App\Services\BitacoraService;
use App\Models\Bitacora;

class CU07ConfigurarCuposController extends Controller
{
    public function actualizar($id)
    {
        try {
            // Obtener datos anteriores para comparación
            $cupoAnterior = CupoCarrera::find($id);
            
            // Validar y actualizar
            $validated = request()->validate([
                'cupo_maximo' => 'required|integer|min:0',
            ]);
            
            // Actualizar
            $cupoNuevo = CupoCarrera::find($id)->update($validated);
            
            // Registrar cambios
            BitacoraService::registrarActualizacion(
                'cupo_carrera',
                $id,
                [
                    'cupo_maximo' => [
                        'anterior' => $cupoAnterior->cupo_maximo,
                        'nuevo' => $validated['cupo_maximo']
                    ]
                ]
            );
            
            return redirect()->back()->with('success', 'Cupo actualizado');
            
        } catch (\Exception $e) {
            // Registrar error automáticamente
            BitacoraService::registrarError('Error actualizando cupo', $e);
            return redirect()->back()->with('error', 'Error al actualizar');
        }
    }
}
```

---

## 📊 CONSULTAS EN EL MÓDULO DE AUDITORÍA

El módulo CU20AuditoriaController ya usa estos scopes:

```php
public function index(Request $request)
{
    // Construcción de query con filtros
    $bitacora = Bitacora::query()
        ->when($request->usuario_id, fn($q) => $q->delUsuario($request->usuario_id))
        ->when($request->accion, fn($q) => $q->accion($request->accion))
        ->when($request->fecha_desde, fn($q) => $q->whereDate('fecha_hora', '>=', $request->fecha_desde))
        ->when($request->fecha_hasta, fn($q) => $q->whereDate('fecha_hora', '<=', $request->fecha_hasta))
        ->paginate(50);
    
    return Inertia::render('CU20Auditoria', [
        'bitacora' => $bitacora,
    ]);
}
```

---

## ⚠️ IMPORTANTE

### Lo que Registran los TRIGGERS:
- ✅ Inserciones directas en postulante, calificacion, pago, etc
- ✅ Actualizaciones de estado importantes
- ✅ Eliminaciones de registros
- ✅ Cambios a nivel de BD (sin necesidad de aplicación)

### Lo que Registra BitacoraService:
- ✅ Acciones desde la aplicación
- ✅ IP origen del usuario
- ✅ ID del usuario autenticado
- ✅ Contexto completo de la acción
- ✅ Errores y excepciones

---

## 🧪 PRUEBAS

### Verificar que todo funciona

```bash
# 1. Ver registros recientes
php artisan tinker
>>> Bitacora::ultimos(10)->get()

# 2. Ver solo triggers
>>> Bitacora::deTriggers()->get()

# 3. Ver errores
>>> Bitacora::deErrores()->get()

# 4. Contar registros
>>> Bitacora::count()
```

---

## 📈 VENTAJAS DEL NUEVO SISTEMA

✅ **Redundancia** - Si falla la app, los triggers siguen auditoría  
✅ **Completo** - Captura cambios desde la app y desde BD  
✅ **Legible** - Código más claro y estructurado  
✅ **Queryable** - Scopes para consultas comunes  
✅ **Confiable** - Manejo robusto de errores  
✅ **Git-friendly** - Todo versionado en migraciones  

---

## 🔄 REVERTIR (si es necesario)

```bash
php artisan migrate:rollback --step=1
```

Esto eliminará todos los triggers automáticamente.
