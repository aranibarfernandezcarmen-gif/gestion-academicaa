<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
    Schema::create('configuracion_porcentajes', function (Blueprint $table) {
        $table->id('codigo');
        $table->integer('porcentaje_examen1');
        $table->integer('porcentaje_examen2');
        $table->integer('porcentaje_examen3');
        $table->foreignId('codigo_examen')->nullable()->constrained('examen', 'codigo');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_porcentajes');
    }
};
