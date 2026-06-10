<?php
$pdo = DB::connection()->getPdo();
DB::statement('ALTER TABLE persona DISABLE TRIGGER fn_persona_delete');
DB::statement('DELETE FROM persona WHERE id >= 68');
DB::statement('ALTER TABLE persona ENABLE TRIGGER fn_persona_delete');
echo "✓ Limpieza completada - Personas id >= 68 eliminadas\n";
