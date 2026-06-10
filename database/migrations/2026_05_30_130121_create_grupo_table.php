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
    Schema::create('grupo', function (Blueprint $table) {
        $table->id('codigo');
        $table->string('nombre_grupo', 50);
        $table->integer('capacidad_maxima');
        $table->foreignId('codigo_materia')->constrained('materia', 'codigo');
        $table->foreignId('codigo_docente')->constrained('docente', 'id');
        $table->foreignId('codigo_horario')->constrained('horario', 'codigo');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo');
    }
};
