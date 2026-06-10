<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postulante extends Model
{
    protected $table = 'postulante';
    
    protected $fillable = [
        'id_persona',
        'registro',
        'colegio_procedencia',
        'ciudad',
        'titulo_bachiller',
        'otros_requisitos',
        'codigo_inscripcion',
        'codigo_grupo',
        'carrera_primera_opcion_id',
        'carrera_segunda_opcion_id',
    ];

    /**
     * Relación con persona
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    /**
     * Relación con pagos
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_postulante');
    }
}
