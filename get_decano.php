<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

$decano = DB::table('decano')->first();
if ($decano) {
    $persona = DB::table('persona')->where('id', $decano->id_persona)->first();
    echo "Decano encontrado:\n";
    echo "Codigo: " . $decano->codigo . "\n";
    echo "Nombre: " . $persona->nombre . " " . $persona->apellido . "\n";
    echo "CI: " . $persona->ci . "\n";
    echo "Email: " . $persona->correo_electronico . "\n";
} else {
    echo "No hay Decano registrado\n";
}
