<?php
$pdo = new PDO('pgsql:host=127.0.0.1;dbname=gestion_academica', 'postgres', 'postgres');
$pdo->exec('ALTER TABLE persona DISABLE TRIGGER fn_persona_delete');
$pdo->exec('DELETE FROM persona WHERE id >= 68');
$pdo->exec('ALTER TABLE persona ENABLE TRIGGER fn_persona_delete');
echo "Limpieza completada\n";
