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
        Schema::create('control_asignacion_carrera', function (Blueprint $table) {
            $table->id('codigo');
            $table->foreignId('postulante_id')->constrained('postulante', 'id');
            $table->foreignId('carrera_asignada_id')->constrained('carrera', 'codigo');
            $table->date('fecha_asignacion');
            $table->boolean('es_segunda_opcion')->default(false);
            $table->integer('prioridad');
            $table->text('observacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_asignacion_carrera');
    }
};
