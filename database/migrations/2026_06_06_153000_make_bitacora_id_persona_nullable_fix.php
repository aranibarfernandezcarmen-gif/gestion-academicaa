<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitacora', function (Blueprint $table) {
            // Hacer id_persona nullable
            $table->unsignedBigInteger('id_persona')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bitacora', function (Blueprint $table) {
            $table->unsignedBigInteger('id_persona')->nullable(false)->change();
        });
    }
};
