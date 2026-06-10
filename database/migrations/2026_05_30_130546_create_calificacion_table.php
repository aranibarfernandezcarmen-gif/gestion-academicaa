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
    Schema::create('calificacion', function (Blueprint $table) {
        $table->id();
        $table->integer('nota1'); // Nota parcial 1, 0-100
        $table->integer('nota2'); // Nota parcial 2, 0-100
        $table->integer('nota3'); // Nota parcial 3, 0-100
        $table->foreignId('registro_postulante')->constrained('postulante', 'id');
        $table->foreignId('codigo_examen')->constrained('examen', 'codigo');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calificacion');
    }
};
