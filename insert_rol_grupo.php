<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

DB::table('rol_grupo')->truncate();
DB::table('rol_grupo')->insert([
    ['nombre_grupo' => 'Docente', 'created_at' => now(), 'updated_at' => now()],
    ['nombre_grupo' => 'Administrativo', 'created_at' => now(), 'updated_at' => now()],
    ['nombre_grupo' => 'Coordinador', 'created_at' => now(), 'updated_at' => now()],
    ['nombre_grupo' => 'Postulante', 'created_at' => now(), 'updated_at' => now()],
]);

echo "rol_grupo data inserted successfully!\n";
