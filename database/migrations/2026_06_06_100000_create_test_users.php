<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // Deshabilitar triggers temporalmente
        DB::statement('ALTER TABLE users DISABLE TRIGGER ALL');

        // Crear usuarios de prueba para las personas existentes
        DB::table('users')->insert([
            [
                'id' => 16,
                'name' => 'Natalia Cruz',
                'email' => 'natalia.cruz@gmail.com',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 17,
                'name' => 'Fernando Calani',
                'email' => 'fernando.calani@mail.com',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1,
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@mail.com',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'María López',
                'email' => 'maria.lopez@mail.com',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Carlos Gómez',
                'email' => 'carlos.gomez@mail.com',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Rehabilitar triggers
        DB::statement('ALTER TABLE users ENABLE TRIGGER ALL');
    }

    public function down(): void
    {
        DB::table('users')->whereIn('id', [1, 2, 3, 16, 17])->delete();
    }
};
