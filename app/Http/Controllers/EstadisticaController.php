<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstadisticaController extends Controller
{
    // Obtener todas las estadísticas
    public function index()
    {
        $estadisticas = DB::table('estadistica')
            ->leftJoin('carrera', 'estadistica.id_carrera', '=', 'carrera.codigo')
            ->select('estadistica.*', 'carrera.nombre_carrera')
            ->get();
        return response()->json($estadisticas);
    }

    // Obtener estadísticas por carrera
    public function porCarrera($id_carrera)
    {
        $estadisticas = DB::table('estadistica')
            ->where('id_carrera', $id_carrera)
            ->orderBy('fecha_calculo', 'desc')
            ->get();
        return response()->json($estadisticas);
    }

    // Obtener estadísticas por período
    public function porPeriodo($periodo)
    {
        $estadisticas = DB::table('estadistica')
            ->where('periodo_academico', $periodo)
            ->get();
        return response()->json($estadisticas);
    }

    // Calcular estadísticas de una carrera
    public function calcularCarrera($id_carrera)
    {
        try {
            $resultados = DB::select("
                SELECT * FROM fn_calcular_estadisticas_carrera(?, ?)
            ", [$id_carrera, now()->format('Y-m')]);

            if (!empty($resultados)) {
                $result = $resultados[0];
                DB::table('estadistica')->insert([
                    'id_carrera' => $id_carrera,
                    'periodo_academico' => now()->format('Y-m'),
                    'total_inscritos' => $result->total_inscritos,
                    'total_aprobados' => $result->total_aprobados,
                    'total_reprobados' => $result->total_reprobados,
                    'promedio_ponderado' => $result->promedio,
                    'porcentaje_aprobacion' => ($result->total_inscritos > 0) 
                        ? ($result->total_aprobados / $result->total_inscritos) * 100 
                        : 0,
                    'fecha_calculo' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas calculadas exitosamente',
                'data' => $resultados
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Calcular estadísticas de período
    public function calcularPeriodo(Request $request)
    {
        try {
            $validated = $request->validate([
                'periodo_inicio' => 'required|date',
                'periodo_fin' => 'required|date',
            ]);

            DB::statement("CALL sp_calcular_estadisticas_periodo(?, ?)", [
                $validated['periodo_inicio'],
                $validated['periodo_fin']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas calculadas para el período'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
