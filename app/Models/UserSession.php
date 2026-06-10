<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $table = 'user_sessions';

    protected $fillable = [
        'id_persona',
        'session_id',
        'device_type',
        'browser',
        'os',
        'user_agent',
        'ip_address',
        'last_activity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_activity' => 'datetime',
    ];

    /**
     * Relación: Una sesión pertenece a una persona
     */
    public function persona()
    {
        return $this->belongsTo(\App\Models\User::class, 'id_persona');
    }

    /**
     * Scope para obtener solo sesiones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para obtener solo sesiones inactivas
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope para obtener sesiones de un usuario
     */
    public function scopeForUser($query, $personaId)
    {
        return $query->where('id_persona', $personaId);
    }
}
