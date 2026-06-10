<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulante', function (Blueprint $table) {
            $table->text('observaciones_rechazo')->nullable()->after('estado_asignacion');
        });
    }

    public function down(): void
    {
        Schema::table('postulante', function (Blueprint $table) {
            $table->dropColumn('observaciones_rechazo');
        });
    }
};
