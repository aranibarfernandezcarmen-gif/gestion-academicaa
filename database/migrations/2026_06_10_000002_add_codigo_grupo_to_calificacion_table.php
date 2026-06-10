<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega calificacion.codigo_grupo.
 *
 * La usan CU05 y el dashboard del docente (para enlazar la nota con el grupo),
 * pero no había migración que la creara (solo existía en la BD local del grupo).
 * Se rellena con el grupo del postulante para las notas ya sembradas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('calificacion', 'codigo_grupo')) {
            Schema::table('calificacion', function (Blueprint $table) {
                $table->unsignedBigInteger('codigo_grupo')->nullable()->after('registro_postulante');
            });
        }

        // Backfill: enlazar la nota con el grupo del postulante (si lo tiene).
        DB::statement("
            UPDATE calificacion
            SET codigo_grupo = p.codigo_grupo
            FROM postulante p
            WHERE p.id = calificacion.registro_postulante
              AND calificacion.codigo_grupo IS NULL
              AND p.codigo_grupo IS NOT NULL
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('calificacion', 'codigo_grupo')) {
            Schema::table('calificacion', function (Blueprint $table) {
                $table->dropColumn('codigo_grupo');
            });
        }
    }
};
