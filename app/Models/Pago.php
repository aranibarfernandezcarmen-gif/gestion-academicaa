<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pago';

    protected $fillable = [
        'id_postulante',
        'monto',
        'fecha_pago',
        'hora_pago',
        'comprobante',
        'estado',
        'metodo_pago',
        'referencia_transaccion',
        'descripcion',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'hora_pago' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con postulante
     */
    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'id_postulante');
    }

    /**
     * Scopes
     */
    public function scopeCompletado($query)
    {
        return $query->where('estado', 'Completado');
    }

    public function scopePendiente($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeRechazado($query)
    {
        return $query->where('estado', 'Rechazado');
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_pago', [$inicio, $fin]);
    }

    public function scopePorPostulante($query, $idPostulante)
    {
        return $query->where('id_postulante', $idPostulante);
    }
}
