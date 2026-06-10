<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$persona = \App\Models\Persona::find(16);
echo "ID: " . $persona->id . "\n";
echo "Nombre: " . $persona->nombre . " " . $persona->apellido . "\n";
echo "CI: " . $persona->ci . "\n";
echo "Correo: " . $persona->correo_electronico . "\n";
echo "Temporary Password: " . ($persona->temporary_password ?? 'NULL') . "\n";
