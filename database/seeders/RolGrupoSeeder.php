<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolGrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('rol_grupo')->insert([
            ['nombre_grupo' => 'Docente', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Administrativo', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Coordinador', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Postulante', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
