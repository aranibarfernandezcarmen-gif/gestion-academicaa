<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestion_academica', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('gestion');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
        });

        Schema::create('calendario_actividad', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('fecha');
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->string('color', 30)->default('#3b82f6');
            $table->unsignedBigInteger('gestion_academica_id')->nullable();
            $table->timestamps();

            $table->foreign('gestion_academica_id')
                  ->references('codigo')
                  ->on('gestion_academica')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_actividad');
        Schema::table('gestion_academica', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio', 'fecha_fin']);
        });
    }
};
