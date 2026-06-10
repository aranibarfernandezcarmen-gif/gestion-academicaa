<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carrera', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('nombre_carrera');
            $table->enum('estado', ['activa', 'inactiva', 'suspendida'])->default('activa')->after('descripcion');
            $table->integer('total_grupos')->default(0)->after('estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('carrera', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'estado', 'total_grupos', 'created_at', 'updated_at']);
        });
    }
};
