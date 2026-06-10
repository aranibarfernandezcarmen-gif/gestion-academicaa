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
    Schema::create('carrera', function (Blueprint $table) {
        $table->id('codigo');
        $table->string('sigla', 10); // Código o sigla de la carrera
        $table->string('nombre_carrera', 100);
        $table->string('facultad_sigla', 10);
        $table->foreign('facultad_sigla')->references('sigla')->on('facultad');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrera');
    }
};
