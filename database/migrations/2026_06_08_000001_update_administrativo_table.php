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

        // Cambiar horario_trabajo a JSONB
        if (Schema::hasColumn('administrativo', 'horario_trabajo')) {
            // Primero hacer nullable
            DB::statement("ALTER TABLE administrativo ALTER COLUMN horario_trabajo DROP NOT NULL");
            // Actualizar a valores vacíos con un JSON válido
            DB::statement("UPDATE administrativo SET horario_trabajo = '{}' WHERE horario_trabajo IS NULL OR horario_trabajo = ''");
            // Ahora cambiar el tipo
            DB::statement("ALTER TABLE administrativo ALTER COLUMN horario_trabajo TYPE jsonb USING horario_trabajo::jsonb");
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
