<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea tabla para CU15 - Importación Masiva de Datos (CSV/Excel)
     */
    public function up(): void
    {
        Schema::create('importacion_masiva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users');
            $table->enum('tipo_datos', ['Postulantes', 'Docentes', 'Estudiantes', 'Calificaciones', 'Otro'])->default('Postulantes');
            $table->enum('formato_archivo', ['CSV', 'Excel', 'JSON'])->default('CSV');
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 255);
            $table->bigInteger('total_registros')->default(0);
            $table->bigInteger('registros_exitosos')->default(0);
            $table->bigInteger('registros_fallidos')->default(0);
            $table->enum('estado', ['Pendiente', 'Procesando', 'Completado', 'Completado con errores', 'Cancelado', 'Error fatal'])
                ->default('Pendiente');
            $table->text('errores')->nullable(); // JSON con lista de errores
            $table->text('resumen')->nullable(); // Resumen del proceso
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('id_usuario');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importacion_masiva');
    }
};
