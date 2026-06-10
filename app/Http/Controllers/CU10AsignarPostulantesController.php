<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CU10AsignarPostulantesController extends Controller
{
    public function index()
    {
        $materias = DB::table('materia')
            ->select('codigo', 'sigla', 'nombre_materia')
            ->orderBy('codigo')
            ->get();

        $grupos = DB::table('grupo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->select('grupo.codigo', 'grupo.nombre_grupo', 'grupo.capacidad_maxima',
                     'materia.sigla as sigla_materia', 'materia.nombre_materia')
            ->selectRaw('(SELECT COUNT(*) FROM asignacion_grupo WHERE asignacion_grupo.grupo_codigo = grupo.codigo) AS inscritos')
            ->selectRaw('GREATEST(grupo.capacidad_maxima - (SELECT COUNT(*) FROM asignacion_grupo WHERE asignacion_grupo.grupo_codigo = grupo.codigo), 0) AS cupo_libre')
            ->orderBy('materia.codigo')
            ->orderBy('grupo.codigo')
            ->get();

        $postulantesList = DB::table('postulante')
            ->join('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->where('inscripcion.estado_pago', 'Pagado')
            ->select('postulante.id', 'postulante.registro', 'persona.nombre', 'persona.apellido')
            ->orderBy('postulante.registro')
            ->get();

        $ids = $postulantesList->pluck('id')->toArray();

        $asignaciones = empty($ids) ? collect() : DB::table('asignacion_grupo')
            ->join('grupo', 'asignacion_grupo.grupo_codigo', '=', 'grupo.codigo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->whereIn('asignacion_grupo.postulante_id', $ids)
            ->select(
                'asignacion_grupo.postulante_id',
                'grupo.codigo as grupo_codigo',
                'grupo.nombre_grupo',
                'materia.sigla',
                'materia.nombre_materia'
            )
            ->get()
            ->groupBy('postulante_id');

        $postulantes = $postulantesList->map(function ($p) use ($asignaciones, $materias) {
            $map = ($asignaciones->get($p->id) ?? collect())->keyBy('sigla');
            $p->grupos = $materias->map(fn($m) => [
                'sigla'          => $m->sigla,
                'nombre_materia' => $m->nombre_materia,
                'grupo_codigo'   => $map->get($m->sigla)?->grupo_codigo ?? null,
                'nombre_grupo'   => $map->get($m->sigla)?->nombre_grupo ?? null,
            ])->values();
            return $p;
        });

        return Inertia::render('CU10AsignarPostulantes', [
            'materias'    => $materias,
            'grupos'      => $grupos,
            'postulantes' => $postulantes,
        ]);
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'criterio' => 'required|in:registro_asc,fecha_inscripcion_asc',
        ]);

        $materias = DB::table('materia')->select('codigo', 'sigla')->orderBy('codigo')->get();

        $postulanteQuery = DB::table('postulante')
            ->join('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->where('inscripcion.estado_pago', 'Pagado')
            ->select('postulante.id', 'postulante.registro', 'inscripcion.fecha_inscripcion');

        if ($data['criterio'] === 'registro_asc') {
            $postulanteQuery->orderBy('postulante.registro');
        } else {
            $postulanteQuery->orderBy('inscripcion.fecha_inscripcion');
        }

        $postulantes = $postulanteQuery->get();

        if ($postulantes->isEmpty()) {
            return response()->json(['message' => 'No hay postulantes con pago completado.'], 422);
        }

        $gruposPorMateria = [];
        foreach ($materias as $materia) {
            $gruposPorMateria[$materia->sigla] = DB::table('grupo')
                ->where('codigo_materia', $materia->codigo)
                ->select('codigo', 'capacidad_maxima')
                ->orderBy('codigo')
                ->get()
                ->toArray();
        }

        $hayGrupos = collect($gruposPorMateria)->flatten(1)->isNotEmpty();
        if (!$hayGrupos) {
            return response()->json(['message' => 'No hay grupos creados. Ejecute CU09 primero.'], 422);
        }

        $asignados = 0;
        $sinCupo   = 0;

        DB::transaction(function () use ($postulantes, $materias, $gruposPorMateria, &$asignados, &$sinCupo) {
            DB::table('asignacion_grupo')
                ->whereIn('postulante_id', $postulantes->pluck('id')->toArray())
                ->delete();

            $counter = [];
            foreach ($gruposPorMateria as $grupos) {
                foreach ($grupos as $g) {
                    $counter[$g->codigo] = 0;
                }
            }

            foreach ($postulantes as $postulante) {
                $insertar = [];
                $bloqueOk = true;

                foreach ($materias as $materia) {
                    $grupos = $gruposPorMateria[$materia->sigla] ?? [];
                    if (empty($grupos)) { $bloqueOk = false; break; }

                    $mejor = null;
                    foreach ($grupos as $g) {
                        $count = $counter[$g->codigo];
                        if ($count >= $g->capacidad_maxima) continue;
                        if ($mejor === null
                            || $count < $counter[$mejor->codigo]
                            || ($count === $counter[$mejor->codigo] && $g->codigo < $mejor->codigo)) {
                            $mejor = $g;
                        }
                    }

                    if ($mejor === null) { $bloqueOk = false; break; }

                    $insertar[] = ['postulante_id' => $postulante->id, 'grupo_codigo' => $mejor->codigo];
                    $counter[$mejor->codigo]++;
                }

                if ($bloqueOk) {
                    foreach ($insertar as $row) {
                        DB::table('asignacion_grupo')->insert($row);
                    }
                    $asignados++;
                } else {
                    $sinCupo++;
                }
            }
        });

        return response()->json([
            'message'   => 'Asignación completada.',
            'asignados' => $asignados,
            'sin_cupo'  => $sinCupo,
        ]);
    }

    public function updatePostulante(Request $request, $id)
    {
        $request->validate([
            'grupos'   => 'required|array',
            'grupos.*' => 'nullable|integer|exists:grupo,codigo',
        ]);

        $postulante = DB::table('postulante')->where('id', $id)->first();
        if (!$postulante) {
            return response()->json(['message' => 'Postulante no encontrado.'], 404);
        }

        try {
            DB::transaction(function () use ($id, $request) {
                DB::table('asignacion_grupo')->where('postulante_id', $id)->delete();

                foreach ($request->grupos as $grupoCodigo) {
                    if (!$grupoCodigo) continue;
                    $grupo = DB::table('grupo')->where('codigo', $grupoCodigo)->first();
                    $count = DB::table('asignacion_grupo')->where('grupo_codigo', $grupoCodigo)->count();
                    if ($count >= $grupo->capacidad_maxima) {
                        throw new \Exception("El grupo {$grupo->nombre_grupo} ya está lleno.");
                    }
                    DB::table('asignacion_grupo')->insert([
                        'postulante_id' => $id,
                        'grupo_codigo'  => $grupoCodigo,
                    ]);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Asignación actualizada correctamente.']);
    }

    public function destroyPostulante($id)
    {
        $postulante = DB::table('postulante')->where('id', $id)->first();
        if (!$postulante) {
            return response()->json(['message' => 'Postulante no encontrado.'], 404);
        }

        DB::table('asignacion_grupo')->where('postulante_id', $id)->delete();

        return response()->json(['message' => 'Asignación eliminada correctamente.']);
    }
}
