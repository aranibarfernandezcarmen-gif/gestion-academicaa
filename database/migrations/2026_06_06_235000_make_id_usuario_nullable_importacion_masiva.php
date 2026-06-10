<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Hacer id_usuario nullable en importacion_masiva
     */
    public function up(): void
    {
        Schema::table('importacion_masiva', function (Blueprint $table) {
            $table->foreignId('id_usuario')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importacion_masiva', function (Blueprint $table) {
            $table->foreignId('id_usuario')->change();
        });
    }
};
