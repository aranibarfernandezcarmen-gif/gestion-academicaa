<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BitacoraService;
use App\Services\DatabaseOperationsService;
use Inertia\Inertia;

class CU06CalcularPromedioController extends Controller
{
    public function index()
    {
        // Los 4 grupos/materias del sistema
        $grupos = DB::table('grupo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->select('grupo.codigo as grupo_codigo', 'materia.sigla', 'materia.nombre_materia')
            ->orderBy('materia.codigo')
            ->get();

        $numGrupos = $grupos->count();

        // Calificaciones con grupo asignado (una por postulante por materia)
        $cals = DB::table('calificacion')
            ->join('postulante', 'calificacion.registro_postulante', '=', 'postulante.id')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->leftJoin('examen', 'calificacion.codigo_examen', '=', 'examen.codigo')
            ->whereNotNull('calificacion.codigo_grupo')
            ->select(
                'calificacion.registro_postulante',
                'calificacion.codigo_grupo',
                'calificacion.nota1',
                'calificacion.nota2',
                'calificacion.nota3',
                'postulante.registro',
                'persona.ci',
                'persona.nombre',
                'persona.apellido',
                'examen.fecha_examen'
            )
            ->get()
            ->groupBy('registro_postulante');

        $resultados = [];

        foreach ($cals as $postId => $rows) {
            $first = $rows->first();
            $resultado = [
                'id'       => $postId,
                'registro' => $first->registro,
                'ci'       => $first->ci,
                'nombre'   => $first->nombre . ' ' . $first->apellido,
            ];

            $promediosPorMateria = [];
            $fechaMax            = null;

            foreach ($grupos as $g) {
                $cal = $rows->firstWhere('codigo_grupo', $g->grupo_codigo);
                if ($cal) {
                    $prom = ($cal->nota1 + $cal->nota2 + $cal->nota3) / 3;
                    $resultado['prom_' . $g->sigla] = round($prom, 2);
                    $promediosPorMateria[] = $prom;

                    if ($cal->fecha_examen && ($fechaMax === null || $cal->fecha_examen > $fechaMax)) {
                        $fechaMax = $cal->fecha_examen;
                    }
                } else {
                    $resultado['prom_' . $g->sigla] = null;
                }
            }

            $completo = count($promediosPorMateria) === $numGrupos;

            $resultado['promedio_final'] = $completo
                ? round(array_sum($promediosPorMateria) / $numGrupos, 2)
                : null;

            if ($completo) {
                // Aprobado si el promedio de CADA materia >= 60
                $todasAprobadas = !empty($promediosPorMateria) && min($promediosPorMateria) >= 60;
                $resultado['estado']           = $todasAprobadas ? 'APROBADO' : 'REPROBADO';
                $resultado['fecha_aprobacion'] = $todasAprobadas ? $fechaMax : null;
            } else {
                $resultado['estado']           = 'PENDIENTE';
                $resultado['fecha_aprobacion'] = null;
            }

            $resultado['completo'] = $completo;
            $resultados[] = $resultado;
        }

        // Ordenar: promedio final descendente; empates → APROBADO primero
        usort($resultados, function ($a, $b) {
            $diff = ($b['promedio_final'] ?? 0) <=> ($a['promedio_final'] ?? 0);
            if ($diff !== 0) return $diff;
            $ord = ['APROBADO' => 0, 'REPROBADO' => 1, 'PENDIENTE' => 2];
            return ($ord[$a['estado']] ?? 3) <=> ($ord[$b['estado']] ?? 3);
        });

        $completados     = array_values(array_filter($resultados, fn($r) => $r['completo']));
        $aprobados       = array_filter($completados, fn($r) => $r['estado'] === 'APROBADO');
        $reprobados      = array_filter($completados, fn($r) => $r['estado'] === 'REPROBADO');
        $promediosFin    = array_filter(array_column($completados, 'promedio_final'));
        $promedioGeneral = count($promediosFin) > 0
            ? round(array_sum($promediosFin) / count($promediosFin), 2)
            : null;

        return Inertia::render('CU06CalcularPromedio', [
            'resultados'   => array_values($resultados),
            'grupos'       => $grupos,
            'estadisticas' => [
                'total_calificados' => count($completados),
                'total_aprobados'   => count($aprobados),
                'total_reprobados'  => count($reprobados),
                'promedio_general'  => $promedioGeneral,
            ],
        ]);
    }

    public function recalculate(Request $request)
    {
        try {
            $validated = $request->validate([
                'gestion_academica_id' => 'required|integer|exists:gestion_academica,codigo'
            ]);

            $dbOperations = new DatabaseOperationsService();
            $resultado = $dbOperations->recalcularPromediosGestion($validated['gestion_academica_id']);

            if ($resultado['success']) {
                BitacoraService::registrar(
                    "Recalculación de promedios - Total: {$resultado['total']}, Aprobadas: {$resultado['aprobadas']}, Reprobadas: {$resultado['reprobadas']}",
                    request()->ip(),
                    auth()->user()->persona_id ?? 1
                );
            }

            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error en recalculate: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
