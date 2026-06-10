<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CU13AsistenciaController extends Controller
{
    public function index()
    {
        $grupos = DB::table('grupo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->leftJoin('docente', 'grupo.codigo_docente', '=', 'docente.id')
            ->leftJoin('persona as dp', 'docente.id_persona', '=', 'dp.id')
            ->select(
                'grupo.codigo',
                'grupo.nombre_grupo',
                'materia.sigla',
                'materia.nombre_materia',
                'grupo.codigo_docente',
                DB::raw("COALESCE(dp.nombre || ' ' || dp.apellido, 'Sin docente') as docente_nombre")
            )
            ->orderBy('materia.codigo')
            ->get();

        $asistencias = DB::table('asistencia')
            ->join('docente', 'asistencia.codigo_docente', '=', 'docente.id')
            ->join('persona as dp', 'docente.id_persona', '=', 'dp.id')
            ->join('postulante', 'asistencia.registro_postulante', '=', 'postulante.id')
            ->join('persona as pp', 'postulante.id_persona', '=', 'pp.id')
            ->select(
                'asistencia.codigo',
                'asistencia.fecha',
                'asistencia.codigo_docente',
                'asistencia.registro_postulante',
                DB::raw("dp.nombre || ' ' || dp.apellido as docente_nombre"),
                'postulante.registro',
                DB::raw("pp.nombre || ' ' || pp.apellido as postulante_nombre"),
                'asistencia.estado'
            )
            ->orderBy('asistencia.fecha', 'desc')
            ->orderBy('asistencia.codigo', 'desc')
            ->get();

        $postulantes = DB::table('asignacion_grupo')
            ->join('postulante', 'asignacion_grupo.postulante_id', '=', 'postulante.id')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->select(
                'postulante.id',
                'postulante.registro',
                'asignacion_grupo.grupo_codigo',
                DB::raw("persona.nombre || ' ' || persona.apellido as nombre_completo")
            )
            ->distinct()
            ->orderBy('postulante.registro')
            ->get();

        return Inertia::render('CU13Asistencia', [
            'grupos'      => $grupos,
            'asistencias' => $asistencias,
            'postulantes' => $postulantes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha'               => 'required|date',
            'codigo_docente'      => 'required|integer|exists:docente,id',
            'registro_postulante' => 'required|integer|exists:postulante,id',
            'estado'              => 'required|in:Presente,Ausente,Justificado',
        ]);

        $existing = DB::table('asistencia')
            ->where('fecha', $request->fecha)
            ->where('codigo_docente', $request->codigo_docente)
            ->where('registro_postulante', $request->registro_postulante)
            ->first();

        if ($existing) {
            DB::table('asistencia')->where('codigo', $existing->codigo)->update([
                'estado' => $request->estado,
            ]);
            return response()->json(['message' => 'Asistencia actualizada.']);
        }

        DB::table('asistencia')->insert([
            'fecha'               => $request->fecha,
            'codigo_docente'      => $request->codigo_docente,
            'registro_postulante' => $request->registro_postulante,
            'estado'              => $request->estado,
        ]);

        return response()->json(['message' => 'Asistencia registrada.']);
    }

    public function destroy($codigo)
    {
        DB::table('asistencia')->where('codigo', $codigo)->delete();
        return response()->json(['message' => 'Registro eliminado.']);
    }
}
