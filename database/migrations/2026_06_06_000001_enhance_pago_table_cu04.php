<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mejora tabla pago para CU04 - Seguimiento de Pagos
     */
    public function up(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            // Agregar relación con postulante
            if (!Schema::hasColumn('pago', 'id_postulante')) {
                $table->foreignId('id_postulante')->nullable()->constrained('postulante')->after('id');
            }

            // Agregar estado del pago
            if (!Schema::hasColumn('pago', 'estado')) {
                $table->enum('estado', ['Pendiente', 'Procesando', 'Completado', 'Rechazado', 'Cancelado'])
                    ->default('Pendiente')->after('comprobante');
            }

            // Agregar método de pago
            if (!Schema::hasColumn('pago', 'metodo_pago')) {
                $table->enum('metodo_pago', ['Transferencia', 'Efectivo', 'Tarjeta', 'Cheque', 'Otra'])
                    ->default('Transferencia')->after('estado');
            }

            // Agregar referencia de transacción
            if (!Schema::hasColumn('pago', 'referencia_transaccion')) {
                $table->string('referencia_transaccion', 100)->nullable()->after('metodo_pago');
            }

            // Agregar descripción
            if (!Schema::hasColumn('pago', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('referencia_transaccion');
            }

            // Timestamps
            if (!Schema::hasColumn('pago', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            if (Schema::hasColumn('pago', 'id_postulante')) {
                // Intenta eliminar la foreign key si existe
                try {
                    $table->dropForeign(['id_postulante']);
                } catch (\Exception $e) {
                    // Ignora si no existe
                }
                $table->dropColumn('id_postulante');
            }
            if (Schema::hasColumn('pago', 'estado')) {
                $table->dropColumn('estado');
            }
            if (Schema::hasColumn('pago', 'metodo_pago')) {
                $table->dropColumn('metodo_pago');
            }
            if (Schema::hasColumn('pago', 'referencia_transaccion')) {
                $table->dropColumn('referencia_transaccion');
            }
            if (Schema::hasColumn('pago', 'descripcion')) {
                $table->dropColumn('descripcion');
            }
            if (Schema::hasColumn('pago', 'created_at')) {
                $table->dropColumn(['created_at', 'updated_at']);
            }
        });
    }
};
