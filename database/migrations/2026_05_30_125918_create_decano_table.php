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
    Schema::create('decano', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_persona')->constrained('persona');
        $table->string('codigo', 20);
        $table->date('fecha_designacion');
        $table->string('titulo_profesional', 100);
        $table->string('sigla_facultad', 10);
        $table->foreign('sigla_facultad')->references('sigla')->on('facultad');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decano');
    }
};
