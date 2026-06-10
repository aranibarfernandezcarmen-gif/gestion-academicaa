<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CU05RegistrarCalificacionesController extends Controller
{
    private function calificacionQuery()
    {
        return DB::table('calificacion')
            ->join('postulante', 'calificacion.registro_postulante', '=', 'postulante.id')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->join('grupo', 'calificacion.codigo_grupo', '=', 'grupo.codigo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->leftJoin('docente', 'grupo.codigo_docente', '=', 'docente.id')
            ->leftJoin('persona as dp', 'docente.id_persona', '=', 'dp.id')
            ->select(
                'calificacion.id',
                'postulante.registro',
                'persona.ci',
                'persona.nombre',
                'persona.apellido',
                'grupo.nombre_grupo',
                'materia.nombre_materia',
                DB::raw("COALESCE(dp.nombre || ' ' || dp.apellido, 'Sin docente') as docente_nombre"),
                'calificacion.nota1',
                'calificacion.nota2',
                'calificacion.nota3',
                'calificacion.promedio',
                'calificacion.estado'
            );
    }

    public function index()
    {
        $materias = DB::table('materia')
            ->select('codigo', 'sigla', 'nombre_materia')
            ->orderBy('codigo')
            ->get();

        $grupos = DB::table('grupo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->leftJoin('docente', 'grupo.codigo_docente', '=', 'docente.id')
            ->leftJoin('persona as dp', 'docente.id_persona', '=', 'dp.id')
            ->select(
                'grupo.codigo',
                'grupo.nombre_grupo',
                'grupo.codigo_materia',
                'materia.nombre_materia',
                DB::raw("COALESCE(dp.nombre || ' ' || dp.apellido, 'Sin docente asignado') as docente_nombre")
            )
            ->orderBy('materia.codigo')
            ->orderBy('grupo.codigo')
            ->get();

        // Estudiantes con al menos 1 nota faltante
        $postulantes = DB::table('asignacion_grupo')
            ->join('postulante', 'asignacion_grupo.postulante_id', '=', 'postulante.id')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->leftJoin('calificacion', function ($join) {
                $join->on('calificacion.registro_postulante', '=', 'postulante.id')
                     ->on('calificacion.codigo_grupo', '=', 'asignacion_grupo.grupo_codigo');
            })
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->where(function ($q) {
                $q->whereNull('calificacion.id')
                  ->orWhereNull('calificacion.nota1')
                  ->orWhereNull('calificacion.nota2')
                  ->orWhereNull('calificacion.nota3');
            })
            ->select(
                'postulante.id',
                'postulante.registro',
                'persona.ci',
                'persona.nombre',
                'persona.apellido',
                'asignacion_grupo.grupo_codigo',
                'calificacion.id as calificacion_id',
                'calificacion.nota1',
                'calificacion.nota2',
                'calificacion.nota3'
            )
            ->orderBy('postulante.registro')
            ->get();

        $calificaciones = $this->calificacionQuery()
            ->whereNotNull('calificacion.nota1')
            ->whereNotNull('calificacion.nota2')
            ->whereNotNull('calificacion.nota3')
            ->whereNotNull('calificacion.codigo_grupo')
            ->orderBy('postulante.registro')
            ->get();

        return Inertia::render('CU05RegistrarCalificaciones', [
            'materias'       => $materias,
            'grupos'         => $grupos,
            'postulantes'    => $postulantes,
            'calificaciones' => $calificaciones,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'grupo_codigo'  => 'required|integer|exists:grupo,codigo',
            'postulante_id' => 'required|integer|exists:postulante,id',
            'nota1'         => 'nullable|integer|min:0|max:100',
            'nota2'         => 'nullable|integer|min:0|max:100',
            'nota3'         => 'nullable|integer|min:0|max:100',
        ]);

        $existing = DB::table('calificacion')
            ->where('registro_postulante', $request->postulante_id)
            ->where('codigo_grupo', $request->grupo_codigo)
            ->first();

        DB::transaction(function () use ($request, $existing) {
            if ($existing) {
                $updates = [];
                if (is_null($existing->nota1) && !is_null($request->nota1)) $updates['nota1'] = $request->nota1;
                if (is_null($existing->nota2) && !is_null($request->nota2)) $updates['nota2'] = $request->nota2;
                if (is_null($existing->nota3) && !is_null($request->nota3)) $updates['nota3'] = $request->nota3;
                if (!empty($updates)) {
                    DB::table('calificacion')->where('id', $existing->id)->update($updates);
                }
            } else {
                $codigoExamen = DB::table('examen')->insertGetId([
                    'fecha_examen'        => date('Y-m-d'),
                    'registro_postulante' => $request->postulante_id,
                ], 'codigo');

                DB::table('calificacion')->insert([
                    'nota1'               => $request->nota1,
                    'nota2'               => $request->nota2,
                    'nota3'               => $request->nota3,
                    'registro_postulante' => $request->postulante_id,
                    'codigo_examen'       => $codigoExamen,
                    'codigo_grupo'        => $request->grupo_codigo,
                ]);
            }
        });

        $cal = $this->calificacionQuery()
            ->where('calificacion.registro_postulante', $request->postulante_id)
            ->where('calificacion.codigo_grupo', $request->grupo_codigo)
            ->first();

        $completo = !is_null($cal->nota1) && !is_null($cal->nota2) && !is_null($cal->nota3);

        return response()->json([
            'message'       => 'Calificaciones guardadas correctamente.',
            'completo'      => $completo,
            'calificacion'  => $cal,
            'postulante_id' => (int) $request->postulante_id,
            'grupo_codigo'  => (int) $request->grupo_codigo,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nota1' => 'nullable|integer|min:0|max:100',
            'nota2' => 'nullable|integer|min:0|max:100',
            'nota3' => 'nullable|integer|min:0|max:100',
        ]);

        $cal = DB::table('calificacion')->where('id', $id)->first();
        if (!$cal) {
            return response()->json(['message' => 'Calificación no encontrada.'], 404);
        }

        DB::table('calificacion')->where('id', $id)->update([
            'nota1' => $request->nota1 ?? $cal->nota1,
            'nota2' => $request->nota2 ?? $cal->nota2,
            'nota3' => $request->nota3 ?? $cal->nota3,
        ]);

        $updated = $this->calificacionQuery()->where('calificacion.id', $id)->first();

        return response()->json(['message' => 'Calificación actualizada.', 'calificacion' => $updated]);
    }

    public function destroy($id)
    {
        $deleted = DB::table('calificacion')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'Calificación no encontrada.'], 404);
        }
        return response()->json(['message' => 'Calificación eliminada correctamente.']);
    }
}
