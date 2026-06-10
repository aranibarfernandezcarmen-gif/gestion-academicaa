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
    Schema::create('cupo_carrera', function (Blueprint $table) {
        $table->id('codigo');
        $table->foreignId('carrera_id')->unique()->constrained('carrera', 'codigo');
        $table->integer('cupo_maximo');
        $table->integer('cupos_disponibles');
        $table->foreignId('gestion_academica_id')->constrained('gestion_academica', 'codigo');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupo_carrera');
    }
};
