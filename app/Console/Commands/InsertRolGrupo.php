<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InsertRolGrupo extends Command
{
    protected $signature = 'app:insert-rol-grupo';
    protected $description = 'Insert rol_grupo data into database';

    public function handle()
    {
        DB::table('rol_grupo')->insert([
            ['nombre_grupo' => 'Docente', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Administrativo', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Coordinador', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_grupo' => 'Postulante', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->info('rol_grupo data inserted successfully!');
    }
}
