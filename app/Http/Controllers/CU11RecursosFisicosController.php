<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CU11RecursosFisicosController extends Controller
{
    public function index()
    {
        $grupos = DB::table('grupo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->leftJoin('horario', 'grupo.codigo_horario', '=', 'horario.codigo')
            ->leftJoin('aula', 'grupo.codigo_aula', '=', 'aula.nro')
            ->select(
                'grupo.codigo',
                'grupo.nombre_grupo',
                'materia.sigla',
                'materia.nombre_materia',
                'grupo.capacidad_maxima',
                'grupo.codigo_horario',
                'grupo.codigo_aula',
                'horario.dia',
                'horario.hora_inicio',
                'horario.hora_fin',
                'aula.numero_aula',
                'aula.piso'
            )
            ->orderBy('materia.codigo')
            ->orderBy('grupo.codigo')
            ->get();

        $aulas = DB::table('aula')
            ->select('nro', 'numero_aula', 'piso')
            ->orderBy('nro')
            ->get();

        $horarios = DB::table('horario')
            ->select('codigo', 'dia', 'hora_inicio', 'hora_fin')
            ->orderBy('codigo')
            ->get();

        return Inertia::render('CU11RecursosFisicos', [
            'grupos'   => $grupos,
            'aulas'    => $aulas,
            'horarios' => $horarios,
        ]);
    }

    public function update(Request $request, $codigo)
    {
        $request->validate([
            'aula_numero'    => 'nullable|string|max:20',
            'codigo_horario' => 'nullable|integer|exists:horario,codigo',
        ]);

        $codigoAula = null;
        if ($request->filled('aula_numero')) {
            $aula = DB::table('aula')->where('numero_aula', $request->aula_numero)->first();
            if (!$aula) {
                DB::statement("SELECT setval('aula_nro_seq', COALESCE((SELECT MAX(nro) FROM aula), 0))");
                $aulaId = DB::table('aula')->insertGetId([
                    'numero_aula' => $request->aula_numero,
                    'piso'        => 1,
                ], 'nro');
                $codigoAula = $aulaId;
            } else {
                $codigoAula = $aula->nro;
            }
        }

        DB::table('grupo')->where('codigo', $codigo)->update([
            'codigo_aula'    => $codigoAula,
            'codigo_horario' => $request->codigo_horario,
        ]);

        return response()->json(['message' => 'Recursos asignados correctamente.']);
    }
}
