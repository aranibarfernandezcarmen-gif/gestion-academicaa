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
        Schema::table('bitacora', function (Blueprint $table) {
            $table->string('tabla_modificada')->nullable()->after('id_persona');
            $table->string('operacion')->nullable()->after('tabla_modificada');
            $table->string('registro_modificado')->nullable()->after('operacion');
            $table->text('valor_anterior')->nullable()->after('registro_modificado');
            $table->text('valor_nuevo')->nullable()->after('valor_anterior');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bitacora', function (Blueprint $table) {
            $table->dropColumn(['tabla_modificada', 'operacion', 'registro_modificado', 'valor_anterior', 'valor_nuevo']);
        });
    }
};
