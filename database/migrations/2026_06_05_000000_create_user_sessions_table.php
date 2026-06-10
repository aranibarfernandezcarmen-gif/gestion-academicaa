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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_persona');
            $table->string('session_id')->unique();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_type')->nullable(); // 'mobile', 'tablet', 'desktop'
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices
            $table->index('id_persona');
            $table->index('session_id');
            $table->index('is_active');

            // Foreign key
            $table->foreign('id_persona')->references('id')->on('persona')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
