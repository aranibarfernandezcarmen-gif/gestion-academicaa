<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->input('tipo_usuario') === 'decano') {
            return [
                'registro' => ['required', 'string'],
                'password' => ['required', 'string'],
            ];
        }

        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if ($this->input('tipo_usuario') === 'decano') {
            $this->authenticateDecano();
        } else {
            if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function authenticateDecano(): void
    {
        $registro = $this->input('registro');

        $decano = DB::table('decano')->where('codigo', $registro)->first();

        if (! $decano) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'registro' => 'El registro de Decano no existe.',
            ]);
        }

        $user = User::find($decano->id_persona);

        if (! $user) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'registro' => 'No se encontró una cuenta asociada a este registro.',
            ]);
        }

        if (! Auth::attempt(['email' => $user->email, 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'registro' => trans('auth.failed'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $field = $this->input('tipo_usuario') === 'decano' ? 'registro' : 'email';

        throw ValidationException::withMessages([
            $field => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        if ($this->input('tipo_usuario') === 'decano') {
            return Str::transliterate(Str::lower($this->string('registro')).'|'.$this->ip());
        }

        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
