<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTestData extends Command
{
    protected $signature = 'cleanup:test-data';
    protected $description = 'Limpia datos de prueba (personas id >= 68)';

    public function handle()
    {
        DB::beginTransaction();
        try {
            // Usando transacción con constraints diferidas
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
            DB::statement('DELETE FROM bitacora WHERE id_persona >= 68');
            DB::statement('DELETE FROM postulante WHERE id_persona >= 68');
            DB::statement('DELETE FROM persona WHERE id >= 68');
            DB::commit();
            $this->info('✓ Limpieza completada - Registros de prueba eliminados');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
