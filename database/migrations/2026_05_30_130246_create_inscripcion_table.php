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
    Schema::create('inscripcion', function (Blueprint $table) {
        $table->id();
        $table->date('fecha_inscripcion');
        $table->string('estado_pago', 20);
        $table->foreignId('codigo_gestion_academica')->constrained('gestion_academica', 'codigo');
        $table->foreignId('codigo_pago')->nullable()->constrained('pago');
        $table->unsignedBigInteger('codigo_pasarelaPago')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripcion');
    }
};
