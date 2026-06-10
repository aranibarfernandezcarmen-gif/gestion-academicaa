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
        Schema::create('rol_grupo', function (Blueprint $table) {
            $table->id('codigo');
            $table->string('nombre_grupo', 50)->unique();
            $table->timestamps();
        });

        Schema::create('rol_grupo_privilegio', function (Blueprint $table) {
            $table->id('codigo');
            $table->unsignedBigInteger('codigo_rol_grupo');
            $table->string('codigo_cu', 50); // CU01, CU02, CU03, etc.
            $table->string('descripcion_cu', 255);
            $table->timestamps();

            $table->foreign('codigo_rol_grupo')
                ->references('codigo')
                ->on('rol_grupo')
                ->onDelete('cascade');

            $table->unique(['codigo_rol_grupo', 'codigo_cu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol_grupo_privilegio');
        Schema::dropIfExists('rol_grupo');
    }
};
