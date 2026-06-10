<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examen', function (Blueprint $table) {
            $table->unsignedBigInteger('id_materia')->nullable()->after('codigo');
            $table->string('tipo_examen', 50)->default('parcial')->after('fecha_examen');
            $table->string('aula_examen', 50)->nullable()->after('tipo_examen');
            $table->time('hora_inicio')->nullable()->after('aula_examen');
            $table->time('hora_fin')->nullable()->after('hora_inicio');
            $table->decimal('puntaje_maximo', 5, 2)->default(100)->after('hora_fin');
            $table->enum('estado', ['programado', 'realizado', 'cancelado'])->default('programado')->after('puntaje_maximo');
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('id_materia')->references('codigo')->on('materia')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('examen', function (Blueprint $table) {
            $table->dropForeign(['id_materia']);
            $table->dropColumn(['id_materia', 'tipo_examen', 'aula_examen', 'hora_inicio', 'hora_fin', 'puntaje_maximo', 'estado', 'created_at']);
        });
    }
};
