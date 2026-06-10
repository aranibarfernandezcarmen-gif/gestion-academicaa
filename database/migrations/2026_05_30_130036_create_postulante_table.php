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
    Schema::create('postulante', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_persona')->constrained('persona');
        $table->string('registro', 20);
        $table->string('colegio_procedencia', 100);
        $table->string('ciudad', 50);
        $table->string('titulo_bachiller', 50);
        $table->text('otros_requisitos');
        $table->unsignedBigInteger('codigo_inscripcion');
        $table->unsignedBigInteger('codigo_grupo')->nullable();
        $table->foreignId('carrera_primera_opcion_id')->nullable()->constrained('carrera', 'codigo');
        $table->foreignId('carrera_segunda_opcion_id')->nullable()->constrained('carrera', 'codigo');
        $table->foreignId('carrera_asignada_id')->nullable()->constrained('carrera', 'codigo');
        $table->string('estado_asignacion', 20)->default('Pendiente');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postulante');
    }
};
