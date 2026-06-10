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
        Schema::create('password_recovery_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_persona');
            $table->string('email', 100);
            $table->string('verification_code', 6)->unique(); // Código de 6 dígitos
            $table->string('reset_token', 255)->unique(); // Token para restablecer
            $table->dateTime('code_expires_at'); // Expiración del código (15 minutos)
            $table->dateTime('reset_expires_at'); // Expiración del token de reseteo (1 hora)
            $table->boolean('is_used')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('id_persona')
                ->references('id')
                ->on('persona')
                ->onDelete('cascade');

            // Índices para búsquedas rápidas
            $table->index('email');
            $table->index('verification_code');
            $table->index('reset_token');
            $table->index(['id_persona', 'is_used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_recovery_tokens');
    }
};
