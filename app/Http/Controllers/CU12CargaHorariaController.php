<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CU12CargaHorariaController extends Controller
{
    public function index()
    {
        $docentes = DB::table('docente')
            ->join('persona', 'docente.id_persona', '=', 'persona.id')
            ->leftJoin('carga_horaria_docente', 'docente.id', '=', 'carga_horaria_docente.codigo_docente')
            ->select(
                'docente.id',
                'docente.codigo as registro',
                'persona.ci',
                'persona.nombre',
                'persona.apellido',
                DB::raw("COALESCE(docente.profesional_area, docente.especialidad, '—') as area"),
                DB::raw('COALESCE(carga_horaria_docente.horas_asignadas, 0) as horas_asignadas')
            )
            ->orderBy('persona.apellido')
            ->get();

        $grupos = DB::table('grupo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->leftJoin('docente', 'grupo.codigo_docente', '=', 'docente.id')
            ->leftJoin('persona as dp', 'docente.id_persona', '=', 'dp.id')
            ->leftJoin('horario', 'grupo.codigo_horario', '=', 'horario.codigo')
            ->select(
                'grupo.codigo',
                'grupo.nombre_grupo',
                'materia.sigla',
                'materia.nombre_materia',
                'grupo.codigo_docente',
                DB::raw("COALESCE(dp.nombre || ' ' || dp.apellido, NULL) as docente_nombre"),
                'horario.hora_inicio',
                'horario.hora_fin',
                'horario.dia'
            )
            ->orderBy('materia.codigo')
            ->get();

        return Inertia::render('CU12CargaHoraria', [
            'docentes' => $docentes,
            'grupos'   => $grupos,
        ]);
    }

    public function asignarDocente(Request $request)
    {
        $request->validate([
            'grupo_codigo' => 'required|integer|exists:grupo,codigo',
            'docente_id'   => 'nullable|integer|exists:docente,id',
            'hora_inicio'  => 'required|string',
            'hora_fin'     => 'required|string',
            'dia'          => 'required|string',
        ]);

        [$startH, $startM] = array_map('intval', explode(':', $request->hora_inicio));
        [$endH,   $endM]   = array_map('intval', explode(':', $request->hora_fin));
        $diffMinutes    = ($endH * 60 + $endM) - ($startH * 60 + $startM);
        $horasSemanales = max(0, round(($diffMinutes / 60) * 7));

        // Abreviar días para respetar varchar(20): "Lunes, Miércoles" → "Lu-Mi"
        $abrev = ['Lunes'=>'Lu','Martes'=>'Ma','Miércoles'=>'Mi','Jueves'=>'Ju','Viernes'=>'Vi','Sábado'=>'Sá','Domingo'=>'Do'];
        $diasAbrev = implode('-', array_map(
            fn($d) => $abrev[trim($d)] ?? substr(trim($d), 0, 2),
            explode(',', $request->dia)
        ));

        $horario = DB::table('horario')
            ->where('hora_inicio', $request->hora_inicio)
            ->where('hora_fin',    $request->hora_fin)
            ->where('dia',         $diasAbrev)
            ->first();

        if (!$horario) {
            DB::statement("SELECT setval('horario_codigo_seq', COALESCE((SELECT MAX(codigo) FROM horario), 0))");
        }

        $horarioId = $horario
            ? $horario->codigo
            : DB::table('horario')->insertGetId([
                'dia'         => $diasAbrev,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin'    => $request->hora_fin,
            ], 'codigo');

        DB::table('grupo')->where('codigo', $request->grupo_codigo)->update([
            'codigo_docente' => $request->docente_id,
            'codigo_horario' => $horarioId,
        ]);

        if ($request->docente_id) {
            $existing = DB::table('carga_horaria_docente')
                ->where('codigo_docente', $request->docente_id)
                ->first();

            if ($existing) {
                DB::table('carga_horaria_docente')
                    ->where('id', $existing->id)
                    ->update(['horas_asignadas' => $existing->horas_asignadas + $horasSemanales]);
            } else {
                DB::statement("SELECT setval('carga_horaria_docente_id_seq', COALESCE((SELECT MAX(id) FROM carga_horaria_docente), 0))");
                DB::table('carga_horaria_docente')->insert([
                    'codigo_docente'  => $request->docente_id,
                    'horas_asignadas' => $horasSemanales,
                ]);
            }
        }

        return response()->json(['message' => 'Docente asignado correctamente.']);
    }

    public function eliminarAsignacion($grupo_codigo)
    {
        $grupo = DB::table('grupo')
            ->leftJoin('horario', 'grupo.codigo_horario', '=', 'horario.codigo')
            ->where('grupo.codigo', $grupo_codigo)
            ->select('grupo.codigo', 'grupo.codigo_docente', 'horario.hora_inicio', 'horario.hora_fin')
            ->first();

        if (!$grupo) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        $horasSemanales = 0;
        if ($grupo->hora_inicio && $grupo->hora_fin) {
            [$startH, $startM] = array_map('intval', explode(':', $grupo->hora_inicio));
            [$endH,   $endM]   = array_map('intval', explode(':', $grupo->hora_fin));
            $diffMinutes    = ($endH * 60 + $endM) - ($startH * 60 + $startM);
            $horasSemanales = max(0, round(($diffMinutes / 60) * 7));
        }

        $oldDocente = $grupo->codigo_docente;

        DB::table('grupo')->where('codigo', $grupo_codigo)->update([
            'codigo_docente' => null,
        ]);

        if ($oldDocente) {
            $existing = DB::table('carga_horaria_docente')
                ->where('codigo_docente', $oldDocente)
                ->first();
            if ($existing) {
                DB::table('carga_horaria_docente')
                    ->where('id', $existing->id)
                    ->update(['horas_asignadas' => max(0, $existing->horas_asignadas - $horasSemanales)]);
            }
        }

        return response()->json(['message' => 'Asignación eliminada correctamente.']);
    }
}
