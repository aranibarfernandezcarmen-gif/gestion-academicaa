<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$conn = new PDO('pgsql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);

// Buscar código de Administrativo
$stmt = $conn->prepare('SELECT codigo FROM rol_grupo WHERE nombre_grupo = ?');
$stmt->execute(['Administrativo']);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Código Administrativo: " . ($grupo['codigo'] ?? 'NO ENCONTRADO') . "\n";

if ($grupo) {
  // Ver qué permisos tiene
  $stmt2 = $conn->prepare('SELECT codigo_cu, descripcion_cu FROM rol_grupo_privilegio WHERE codigo_rol_grupo = ? ORDER BY codigo_cu');
  $stmt2->execute([$grupo['codigo']]);
  $permisos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
  echo "Permisos encontrados: " . count($permisos) . "\n";
  foreach ($permisos as $p) {
    echo "  - " . $p['codigo_cu'] . ": " . $p['descripcion_cu'] . "\n";
  }
}
?>
