<?php
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$sessions = DB::table('user_sessions')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "=== ÚLTIMAS SESIONES ===\n";
foreach ($sessions as $session) {
    echo json_encode($session, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== SESIONES ACTIVAS POR USUARIO ===\n";
$activeSessions = DB::table('user_sessions')
    ->where('is_active', true)
    ->get();

foreach ($activeSessions as $session) {
    echo "Usuario {$session->id_persona}: {$session->session_id} ({$session->browser}) - Activa\n";
}

echo "\n=== VERIFICA TABLA PERSONA ===\n";
$personas = DB::table('persona')->count();
echo "Total personas: {$personas}\n";
