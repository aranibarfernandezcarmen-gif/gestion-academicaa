<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BitacoraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener algunos usuarios de prueba
        $usuarios = DB::table('persona')->limit(5)->pluck('id');

        // Array de acciones comunes
        $acciones = [
            'Login - Docente ingresó al sistema',
            'Login - Administrativo ingresó al sistema',
            'Login - Postulante ingresó al portal de postulación',
            'Logout - Docente cerró sesión',
            'Logout - Postulante cerró sesión',
            'Crear - Nuevo postulante registrado',
            'Editar - Datos de postulante actualizados',
            'Crear - Calificación registrada para materia Matemática',
            'Editar - Cupo de carrera Ingeniería modificado',
            'Eliminar - Registro de postulante eliminado',
        ];

        $tablas = ['postulante', 'calificacion', 'cupo_carrera', 'persona', 'grupo'];

        // Crear 30 registros de ejemplo
        for ($i = 1; $i <= 30; $i++) {
            $usuarioId = $usuarios->count() > 0 ? $usuarios->random() : null;
            
            DB::table('bitacora')->insert([
                'accion' => $acciones[array_rand($acciones)],
                'fecha_hora' => now()->subHours(rand(0, 72)),
                'usuario_id' => $usuarioId,
                'tabla_afectada' => $tablas[array_rand($tablas)],
                'registro_id' => rand(1, 100),
                'detalles' => 'Operación registrada automáticamente por el sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
