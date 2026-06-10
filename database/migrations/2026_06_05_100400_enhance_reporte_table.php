<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporte', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('tipo_reporte');
            $table->enum('estado', ['generado', 'enviado', 'visto', 'archivado'])->default('generado')->after('formato');
            $table->string('ruta_archivo', 255)->nullable()->after('estado');
            $table->integer('cantidad_registros')->default(0)->after('ruta_archivo');
            $table->json('filtros')->nullable()->after('cantidad_registros');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::table('reporte', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'estado', 'ruta_archivo', 'cantidad_registros', 'filtros', 'created_at', 'updated_at']);
        });
    }
};
