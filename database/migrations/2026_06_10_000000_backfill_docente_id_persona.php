<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rellena docente.id_persona enlazando por codigo = persona.ci.
 *
 * El login de docente busca la persona vía docente.id_persona, pero el seeder
 * no poblaba esa columna (el código del docente es el CI de la persona).
 * Sin esto, el inicio de sesión de docente falla.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('docente', 'id_persona')) {
            DB::statement("
                UPDATE docente
                SET id_persona = persona.id
                FROM persona
                WHERE persona.ci = docente.codigo
                  AND docente.id_persona IS NULL
            ");
        }
    }

    public function down(): void
    {
        // No revertir (sería solo volver a poner null).
    }
};
