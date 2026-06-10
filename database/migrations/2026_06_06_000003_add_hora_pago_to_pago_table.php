<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar columna hora_pago a tabla pago
     */
    public function up(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            if (!Schema::hasColumn('pago', 'hora_pago')) {
                $table->time('hora_pago')->nullable()->after('fecha_pago');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            if (Schema::hasColumn('pago', 'hora_pago')) {
                $table->dropColumn('hora_pago');
            }
        });
    }
};
