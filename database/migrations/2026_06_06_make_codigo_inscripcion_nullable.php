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
        Schema::table('postulante', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['codigo_inscripcion']);
            
            // Make codigo_inscripcion nullable and recreate the FK
            $table->unsignedBigInteger('codigo_inscripcion')->nullable()->change();
            
            // Recreate the foreign key with cascade on delete
            $table->foreign('codigo_inscripcion')->references('id')->on('inscripcion')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postulante', function (Blueprint $table) {
            $table->dropForeign(['codigo_inscripcion']);
            
            // Revert to non-nullable
            $table->unsignedBigInteger('codigo_inscripcion')->change();
            
            $table->foreign('codigo_inscripcion')->references('id')->on('inscripcion');
        });
    }
};
