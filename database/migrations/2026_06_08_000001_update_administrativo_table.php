<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('administrativo', function (Blueprint $table) {
            // Agregar columnas para profesión y número de título si no existen
            if (!Schema::hasColumn('administrativo', 'profesion')) {
                $table->string('profesion', 100)->nullable()->after('codigo');
            }
            if (!Schema::hasColumn('administrativo', 'nro_titulo')) {
                $table->string('nro_titulo', 50)->nullable()->after('profesion');
            }
        });

        // Mantener horario_trabajo como TEXTO (la app lo valida como string y el
        // seeder inserta valores tipo "08:00-16:00"). La conversión a jsonb rompía
        // el despliegue porque esos valores no son JSON válido. Solo lo hacemos
        // nullable para que coincida con la validación 'nullable' de la app.
        if (Schema::hasColumn('administrativo', 'horario_trabajo')) {
            DB::statement("ALTER TABLE administrativo ALTER COLUMN horario_trabajo DROP NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('administrativo', function (Blueprint $table) {
            if (Schema::hasColumn('administrativo', 'profesion')) {
                $table->dropColumn('profesion');
            }
            if (Schema::hasColumn('administrativo', 'nro_titulo')) {
                $table->dropColumn('nro_titulo');
            }
        });

        // Volver horario_trabajo a string
        if (Schema::hasColumn('administrativo', 'horario_trabajo')) {
            DB::statement("ALTER TABLE administrativo ALTER COLUMN horario_trabajo TYPE varchar(100)");
        }
    }
};
