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
    Schema::create('bitacora', function (Blueprint $table) {
        $table->id('codigo');
        $table->string('accion', 100)->nullable();
        $table->timestamp('fecha_hora');
        $table->string('ip_origen', 50)->nullable();
        $table->foreignId('id_persona')->nullable()->constrained('persona', 'id');
        $table->string('tabla_modificada')->nullable();
        $table->string('operacion')->nullable();
        $table->string('registro_modificado')->nullable();
        $table->text('valor_anterior')->nullable();
        $table->text('valor_nuevo')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
