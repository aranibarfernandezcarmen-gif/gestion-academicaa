<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportacionMasiva extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'importacion_masiva';

    protected $fillable = [
        'id_usuario',
        'tipo_datos',
        'formato_archivo',
        'nombre_archivo',
        'ruta_archivo',
        'total_registros',
        'registros_exitosos',
        'registros_fallidos',
        'estado',
        'errores',
        'resumen',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'errores' => 'json',
        'resumen' => 'json',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Scopes
     */
    public function scopeCompletadas($query)
    {
        return $query->whereIn('estado', ['Completado', 'Completado con errores']);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeProcesando($query)
    {
        return $query->where('estado', 'Procesando');
    }

    public function scopeConErrores($query)
    {
        return $query->where('estado', 'Completado con errores');
    }

    public function scopeDelTipo($query, $tipo)
    {
        return $query->where('tipo_datos', $tipo);
    }

    /**
     * Porcentaje de éxito
     */
    public function getPorcentajeExitoAttribute(): float
    {
        if ($this->total_registros === 0) {
            return 0;
        }
        return ($this->registros_exitosos / $this->total_registros) * 100;
    }
}
