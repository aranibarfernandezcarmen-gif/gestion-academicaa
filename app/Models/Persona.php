<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    protected $table = 'persona';
    
    protected $fillable = [
        'ci',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'sexo',
        'direccion',
        'telefono',
        'correo_electronico',
    ];
    
    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Relación con postulantes
     */
    public function postulantes(): HasMany
    {
        return $this->hasMany(Postulante::class, 'id_persona');
    }
}
