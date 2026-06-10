<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    use HasFactory;

    protected $table = 'bitacora';
    protected $primaryKey = 'codigo';
    public $timestamps = false;

    protected $fillable = [
        'accion',
        'fecha_hora',
        'ip_origen',
        'id_persona',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    /**
     * Relación: Bitácora pertenece a una Persona
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id');
    }

    /**
     * Scope: Últimos N registros
     */
    public function scopeUltimos($query, $cantidad = 50)
    {
        return $query->orderBy('fecha_hora', 'desc')->limit($cantidad);
    }

    /**
     * Scope: Registros de un período
     */
    public function scopeDelPeriodo($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_hora', [$desde, $hasta])
            ->orderBy('fecha_hora', 'desc');
    }

    /**
     * Scope: Registros de un usuario
     */
    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('id_persona', $usuarioId)
            ->orderBy('fecha_hora', 'desc');
    }

    /**
     * Scope: Registros que coincidan con una acción
     */
    public function scopeAccion($query, $accion)
    {
        return $query->where('accion', 'like', '%' . $accion . '%')
            ->orderBy('fecha_hora', 'desc');
    }

    /**
     * Scope: Registros de triggers (marcados con [TRIGGER])
     */
    public function scopeDeTriggers($query)
    {
        return $query->where('accion', 'like', '%[TRIGGER]%')
            ->orderBy('fecha_hora', 'desc');
    }

    /**
     * Scope: Registros de errores
     */
    public function scopeDeErrores($query)
    {
        return $query->where('accion', 'like', '%[ERROR]%')
            ->orderBy('fecha_hora', 'desc');
    }

    /**
     * Scope: Registros recientes (últimas N horas)
     */
    public function scopeRecientes($query, $horas = 24)
    {
        return $query->where('fecha_hora', '>=', now()->subHours($horas))
            ->orderBy('fecha_hora', 'desc');
    }

    /**
     * Obtener nombre completo del usuario
     */
    public function getNombreUsuarioCompleto()
    {
        if ($this->persona) {
            return $this->persona->nombre . ' ' . $this->persona->apellido;
        }
        return 'Sistema';
    }

    /**
     * Obtener tipo de acción (INSERT, UPDATE, DELETE, etc)
     */
    public function getTipoAccion()
    {
        if (str_contains($this->accion, '[TRIGGER]')) {
            return 'TRIGGER';
        }
        if (str_contains($this->accion, '[ERROR]')) {
            return 'ERROR';
        }
        if (str_contains($this->accion, 'Creación')) {
            return 'CREATE';
        }
        if (str_contains($this->accion, 'Actualización') || str_contains($this->accion, 'cambió')) {
            return 'UPDATE';
        }
        if (str_contains($this->accion, 'Eliminación')) {
            return 'DELETE';
        }
        return 'OTRO';
    }

    /**
     * Obtener ícono de Bootstrap según el tipo de acción
     */
    public function getIconoTipoAccion()
    {
        $tipo = $this->getTipoAccion();
        return match ($tipo) {
            'TRIGGER' => 'database',
            'ERROR' => 'exclamation-circle',
            'CREATE' => 'plus-circle',
            'UPDATE' => 'arrow-repeat',
            'DELETE' => 'trash',
            default => 'info-circle'
        };
    }

    /**
     * Obtener color de Bootstrap según el tipo de acción
     */
    public function getColorTipoAccion()
    {
        $tipo = $this->getTipoAccion();
        return match ($tipo) {
            'TRIGGER' => 'primary',
            'ERROR' => 'danger',
            'CREATE' => 'success',
            'UPDATE' => 'warning',
            'DELETE' => 'dark',
            default => 'info'
        };
    }
}
