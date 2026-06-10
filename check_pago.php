<?php
require 'bootstrap/app.php';
$app = app();
$pagos = DB::table('pago')->orderBy('created_at', 'desc')->limit(5)->get();

echo "\n=== ÚLTIMOS 5 PAGOS ===\n";
foreach ($pagos as $pago) {
    echo "ID: {$pago->id}, Postulante: {$pago->id_postulante}, Monto: {$pago->monto}, Estado: {$pago->estado}\n";
}
echo "\nTotal de pagos: " . DB::table('pago')->count() . "\n";
