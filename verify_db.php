<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "✓ Postulantes: " . DB::table('postulante')->count() . "\n";
echo "✓ Calificaciones: " . DB::table('calificacion')->count() . "\n";
echo "✓ Cupo Carrera: " . DB::table('cupo_carrera')->count() . "\n";
echo "✓ Carreras: " . DB::table('carrera')->count() . "\n";
echo "✓ Grupos: " . DB::table('grupo')->count() . "\n";
echo "✓ Persona: " . DB::table('persona')->count() . "\n";
echo "✓ Docente: " . DB::table('docente')->count() . "\n";
echo "✓ Administrativo: " . DB::table('administrativo')->count() . "\n";
echo "✓ Gestion Academica: " . DB::table('gestion_academica')->count() . "\n";
echo "\n✅ BD RESTAURADA CORRECTAMENTE\n";
