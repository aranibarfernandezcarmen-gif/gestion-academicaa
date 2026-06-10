<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coordinador', function (Blueprint $table) {
            $table->string('profesion', 100)->nullable()->after('horario_trabajo');
            $table->string('nro_titulo', 50)->nullable()->after('profesion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coordinador', function (Blueprint $table) {
            $table->dropColumn('profesion');
            $table->dropColumn('nro_titulo');
        });
    }
};
