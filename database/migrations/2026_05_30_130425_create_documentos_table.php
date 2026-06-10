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
    Schema::create('documentos', function (Blueprint $table) {
        $table->id('codigo');
        $table->string('tipo_documento', 50);
        $table->foreignId('codigo_docente')->nullable()->constrained('docente', 'id');
        $table->foreignId('registro_postulante')->nullable()->constrained('postulante', 'id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
