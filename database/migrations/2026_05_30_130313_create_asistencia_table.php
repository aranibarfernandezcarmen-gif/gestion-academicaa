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
    Schema::create('asistencia', function (Blueprint $table) {
        $table->id('codigo');
        $table->date('fecha');
        $table->foreignId('codigo_docente')->constrained('docente', 'id');
        $table->foreignId('registro_postulante')->constrained('postulante', 'id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencia');
    }
};
