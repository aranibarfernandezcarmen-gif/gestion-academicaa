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
        Schema::table('materia', function (Blueprint $table) {
            $table->integer('grupos_existentes')->nullable()->after('nombre_materia');
            $table->integer('grupos_necesarios')->nullable()->after('grupos_existentes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materia', function (Blueprint $table) {
            $table->dropColumn(['grupos_existentes', 'grupos_necesarios']);
        });
    }
};
