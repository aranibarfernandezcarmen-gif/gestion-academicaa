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
    Schema::create('persona', function (Blueprint $table) {
        $table->id();
        $table->string('ci', 20);
        $table->string('nombre', 50);
        $table->string('apellido', 50);
        $table->date('fecha_nacimiento');
        $table->string('sexo', 10);
        $table->string('direccion', 100);
        $table->string('telefono', 20)->nullable();
        $table->string('correo_electronico', 100);
        $table->string('ciudad', 50);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persona');
    }
};
