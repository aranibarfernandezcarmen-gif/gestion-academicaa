<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materia', function (Blueprint $table) {
            $table->unsignedBigInteger('id_carrera')->nullable()->after('codigo');
            $table->text('descripcion')->nullable()->after('nombre_materia');
            $table->integer('creditos')->default(0)->after('descripcion');
            $table->integer('horas_teorica')->default(0)->after('creditos');
            $table->integer('horas_practica')->default(0)->after('horas_teorica');
            $table->enum('estado', ['activa', 'inactiva'])->default('activa')->after('horas_practica');
            $table->timestamps();
            
            $table->foreign('id_carrera')->references('codigo')->on('carrera')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('materia', function (Blueprint $table) {
            $table->dropForeign(['id_carrera']);
            $table->dropColumn(['id_carrera', 'descripcion', 'creditos', 'horas_teorica', 'horas_practica', 'estado', 'created_at', 'updated_at']);
        });
    }
};
