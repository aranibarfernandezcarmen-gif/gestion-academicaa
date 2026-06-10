<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CU17DesempenoController extends Controller
{
    public function index(Request $request)
    {
        $estadisticas = DB::table('estadistica')
            ->leftJoin('carrera', 'estadistica.id_carrera', '=', 'carrera.codigo')
            ->select(
                'estadistica.codigo',
                'estadistica.periodo_academico',
                'estadistica.total_inscritos',
                'estadistica.total_aprobados',
                'estadistica.total_reprobados',
                'estadistica.total_grupos_habilitados',
                'estadistica.promedio_ponderado',
                'estadistica.porcentaje_aprobacion',
                'estadistica.fecha_calculo',
                DB::raw("COALESCE(carrera.nombre_carrera, 'General') as carrera_nombre")
            )
            ->orderBy('estadistica.fecha_calculo', 'desc')
            ->get();

        // Calcular estadísticas en tiempo real desde calificaciones
        $resumenActual = DB::table('calificacion')
            ->selectRaw('
                COUNT(*) as total_calificaciones,
                COUNT(CASE WHEN promedio IS NOT NULL THEN 1 END) as con_promedio,
                COUNT(CASE WHEN estado = \'APROBADO\' THEN 1 END) as aprobados,
                COUNT(CASE WHEN estado = \'REPROBADO\' THEN 1 END) as reprobados,
                ROUND(AVG(CASE WHEN promedio IS NOT NULL THEN promedio END), 2) as promedio_general
            ')
            ->first();

        $porMateria = DB::table('calificacion')
            ->join('grupo', 'calificacion.codigo_grupo', '=', 'grupo.codigo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->whereNotNull('calificacion.promedio')
            ->select(
                'materia.sigla',
                'materia.nombre_materia',
                DB::raw('COUNT(*) as total'),
                DB::raw("COUNT(CASE WHEN calificacion.estado = 'APROBADO' THEN 1 END) as aprobados"),
                DB::raw("COUNT(CASE WHEN calificacion.estado = 'REPROBADO' THEN 1 END) as reprobados"),
                DB::raw('ROUND(AVG(calificacion.promedio), 2) as promedio')
            )
            ->groupBy('materia.codigo', 'materia.sigla', 'materia.nombre_materia')
            ->orderBy('materia.codigo')
            ->get();

        return Inertia::render('CU17Desempeno', [
            'estadisticas'  => $estadisticas,
            'resumenActual' => $resumenActual,
            'porMateria'    => $porMateria,
            'registro'      => $request->query('registro'),
            'role'          => $request->query('role'),
        ]);
    }

    public function calcular(Request $request)
    {
        $periodo = $request->input('periodo', date('Y') . '-1');

        $total    = DB::table('postulante')->where('estado_asignacion', '<>', 'Eliminado')->count();
        $aprobados  = DB::table('calificacion')->where('estado', 'APROBADO')->count();
        $reprobados = DB::table('calificacion')->where('estado', 'REPROBADO')->count();
        $grupos   = DB::table('grupo')->count();
        $promedio = DB::table('calificacion')->whereNotNull('promedio')->avg('promedio') ?? 0;
        $porcentaje = $total > 0 ? round(($aprobados / max($total, 1)) * 100, 2) : 0;

        DB::table('estadistica')->insert([
            'periodo_academico'       => $periodo,
            'total_inscritos'         => $total,
            'total_aprobados'         => $aprobados,
            'total_reprobados'        => $reprobados,
            'total_grupos_habilitados' => $grupos,
            'promedio_ponderado'      => round($promedio, 2),
            'porcentaje_aprobacion'   => $porcentaje,
            'fecha_calculo'           => now(),
        ]);

        return response()->json(['message' => 'Estadísticas calculadas y guardadas.']);
    }
}
