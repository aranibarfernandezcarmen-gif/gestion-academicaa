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
    Schema::create('lote_usuarios', function (Blueprint $table) {
        $table->id('codigo');
        $table->string('archivo_csv', 100);
        $table->date('fecha_carga');
        $table->string('usuario_carga', 50);
        $table->foreignId('id_persona')->constrained('persona', 'id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote_usuarios');
    }
};
