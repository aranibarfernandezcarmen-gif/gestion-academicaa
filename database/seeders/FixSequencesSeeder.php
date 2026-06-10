<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Resincroniza las secuencias de PostgreSQL después de insertar datos con IDs
 * explícitos en DatabaseSeeder. Sin esto, el primer INSERT automático en esas
 * tablas (calificaciones, cupos, bitácora, etc.) falla con "duplicate key".
 *
 * Versión genérica: recorre TODAS las tablas del esquema public y resincroniza
 * cualquier columna que tenga una secuencia (serial / identity), sin importar
 * cómo crezca el modelo de datos.
 */
class FixSequencesSeeder extends Seeder
{
    public function run(): void
    {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

        foreach ($tables as $t) {
            $table = $t->tablename;

            $columns = DB::select(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
                ['public', $table]
            );

            foreach ($columns as $c) {
                $col = $c->column_name;

                $seqRow = DB::selectOne('SELECT pg_get_serial_sequence(?, ?) AS seq', [$table, $col]);
                $seq = $seqRow->seq ?? null;

                if (! $seq) {
                    continue; // esa columna no tiene secuencia
                }

                $max = DB::table($table)->max($col);

                if ($max !== null) {
                    // is_called=true => el próximo nextval será $max + 1
                    DB::statement('SELECT setval(?, ?, true)', [$seq, $max]);
                }
            }
        }

        $this->command?->info('Secuencias de PostgreSQL resincronizadas (todas las tablas).');
    }
}
