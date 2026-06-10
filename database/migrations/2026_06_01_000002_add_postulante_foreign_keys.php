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
        Schema::table('postulante', function (Blueprint $table) {
            $table->foreign('codigo_inscripcion')->references('id')->on('inscripcion');
            $table->foreign('codigo_grupo')->references('codigo')->on('grupo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postulante', function (Blueprint $table) {
            $table->dropForeign(['codigo_inscripcion']);
            $table->dropForeign(['codigo_grupo']);
        });
    }
};
