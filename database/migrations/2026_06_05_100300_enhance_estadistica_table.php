<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estadistica', function (Blueprint $table) {
            $table->unsignedBigInteger('id_carrera')->nullable()->after('codigo');
            $table->string('periodo_academico', 20)->nullable()->after('id_carrera');
            $table->decimal('promedio_ponderado', 5, 2)->default(0)->after('total_grupos_habilitados');
            $table->decimal('porcentaje_aprobacion', 5, 2)->default(0)->after('promedio_ponderado');
            $table->timestamp('fecha_calculo')->useCurrent();
            
            $table->foreign('id_carrera')->references('codigo')->on('carrera')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('estadistica', function (Blueprint $table) {
            $table->dropForeign(['id_carrera']);
            $table->dropColumn(['id_carrera', 'periodo_academico', 'promedio_ponderado', 'porcentaje_aprobacion', 'fecha_calculo']);
        });
    }
};
