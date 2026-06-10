<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla pivote asignacion_grupo (postulante <-> grupo).
 *
 * La usan CU05, CU09, CU10, CU13 y el dashboard, pero NINGUNA migración la creaba
 * (solo existía en la BD local del grupo). Sin ella, el dashboard tras el login
 * lanzaba "relation asignacion_grupo does not exist".
 *
 * Además rellena la tabla desde postulante.codigo_grupo si ya hay postulantes
 * sembrados (para que el dashboard muestre datos sin tener que correr CU10).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asignacion_grupo')) {
            Schema::create('asignacion_grupo', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('postulante_id');
                $table->unsignedBigInteger('grupo_codigo');

                $table->foreign('postulante_id')->references('id')->on('postulante')->onDelete('cascade');
                $table->foreign('grupo_codigo')->references('codigo')->on('grupo')->onDelete('cascade');
                $table->index(['postulante_id', 'grupo_codigo']);
            });
        }

        // Backfill: si ya hay postulantes con codigo_grupo, crear sus asignaciones
        // (solo pares válidos y que no existan ya).
        if (Schema::hasColumn('postulante', 'codigo_grupo')) {
            DB::statement("
                INSERT INTO asignacion_grupo (postulante_id, grupo_codigo)
                SELECT p.id, p.codigo_grupo
                FROM postulante p
                JOIN grupo g ON g.codigo = p.codigo_grupo
                WHERE p.codigo_grupo IS NOT NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM asignacion_grupo a
                      WHERE a.postulante_id = p.id AND a.grupo_codigo = p.codigo_grupo
                  )
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_grupo');
    }
};
