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
        Schema::table('persona', function (Blueprint $table) {
            // Agregar columna para contraseña temporal de recuperación
            if (!Schema::hasColumn('persona', 'temporary_password')) {
                $table->string('temporary_password', 255)->nullable()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persona', function (Blueprint $table) {
            if (Schema::hasColumn('persona', 'temporary_password')) {
                $table->dropColumn('temporary_password');
            }
        });
    }
};
