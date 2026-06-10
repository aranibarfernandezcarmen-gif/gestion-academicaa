<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordRecoveryToken extends Model
{
    protected $table = 'password_recovery_tokens';
    protected $guarded = [];
    protected $casts = [
        'code_expires_at' => 'datetime',
        'reset_expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    /**
     * Verificar si el código de verificación es válido
     */
    public function isVerificationCodeValid(): bool
    {
        return !$this->is_used && $this->code_expires_at->isFuture();
    }

    /**
     * Verificar si el token de reseteo es válido
     */
    public function isResetTokenValid(): bool
    {
        return !$this->is_used && $this->reset_expires_at->isFuture();
    }

    /**
     * Generar un código de 6 dígitos único
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('verification_code', $code)->where('is_used', false)->exists());

        return $code;
    }

    /**
     * Generar un token único para reseteo
     */
    public static function generateUniqueResetToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
