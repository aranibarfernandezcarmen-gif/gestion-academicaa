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
    Schema::create('docente', function (Blueprint $table) {
        $table->id();
        $table->string('codigo', 20);
        $table->string('especialidad', 100);
        $table->string('profesional_area', 100);
        $table->string('maestria', 100);
        $table->string('diplomado_educacion_superior', 100);
        $table->integer('cantidad_grupos_asignados');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docente');
    }
};
