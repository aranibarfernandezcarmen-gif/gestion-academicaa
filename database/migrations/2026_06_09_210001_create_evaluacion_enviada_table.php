<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion_enviada', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formulario_id');
            $table->foreign('formulario_id')->references('id')->on('formulario_evaluacion')->onDelete('cascade');
            $table->enum('tipo_evaluador', ['Postulante', 'Docente']);
            $table->string('registro_evaluador', 20); // postulante.registro o docente.codigo
            $table->unsignedBigInteger('id_docente_evaluado')->nullable();
            $table->unsignedBigInteger('id_grupo_evaluado')->nullable();
            $table->json('respuestas'); // [{pregunta_id, texto_pregunta, puntuacion}]
            $table->foreign('id_docente_evaluado')->references('id')->on('docente')->onDelete('set null');
            $table->foreign('id_grupo_evaluado')->references('codigo')->on('grupo')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_enviada');
    }
};
