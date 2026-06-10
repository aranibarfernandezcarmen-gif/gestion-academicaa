<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EvaluacionController extends Controller
{
    // Panel Decano/Admin: lista todos los formularios con conteo de respuestas
    public function index(Request $request)
    {
        $formularios = DB::table('formulario_evaluacion')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($f) {
                $f->preguntas       = json_decode($f->preguntas, true);
                $f->total_respuestas = DB::table('evaluacion_enviada')
                    ->where('formulario_id', $f->id)
                    ->count();
                return $f;
            });

        return Inertia::render('EvaluacionPanel', [
            'formularios' => $formularios,
            'registro'    => $request->query('registro'),
            'role'        => $request->query('role'),
        ]);
    }

    // Crear nuevo formulario
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo'      => 'required|string|max:150',
            'tipo'        => 'required|in:postulante_a_docente,docente_a_curso',
            'descripcion' => 'nullable|string',
            'preguntas'   => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string|max:300',
        ]);

        $preguntas = array_values(array_map(function ($p, $idx) {
            return ['id' => $idx + 1, 'texto' => trim($p['texto'])];
        }, $validated['preguntas'], array_keys($validated['preguntas'])));

        $id = DB::table('formulario_evaluacion')->insertGetId([
            'titulo'      => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo'        => $validated['tipo'],
            'preguntas'   => json_encode($preguntas),
            'activo'      => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['message' => 'Formulario creado.', 'id' => $id], 201);
    }

    // Activar / desactivar formulario
    public function toggle($id)
    {
        $formulario = DB::table('formulario_evaluacion')->find($id);
        if (!$formulario) {
            return response()->json(['message' => 'No encontrado.'], 404);
        }

        DB::table('formulario_evaluacion')
            ->where('id', $id)
            ->update(['activo' => !$formulario->activo, 'updated_at' => now()]);

        return response()->json(['activo' => !$formulario->activo]);
    }

    // Eliminar formulario (solo si no tiene respuestas)
    public function eliminar($id)
    {
        $tiene = DB::table('evaluacion_enviada')->where('formulario_id', $id)->exists();
        if ($tiene) {
            return response()->json(['message' => 'No se puede eliminar: ya tiene respuestas enviadas.'], 422);
        }

        DB::table('formulario_evaluacion')->where('id', $id)->delete();
        return response()->json(['message' => 'Formulario eliminado.']);
    }

    // Página para responder un formulario (Postulante o Docente)
    public function responder($id, Request $request)
    {
        $registro = $request->query('registro');
        $role     = $request->query('role');

        $formulario = DB::table('formulario_evaluacion')->find($id);
        if (!$formulario || !$formulario->activo) {
            abort(404, 'Formulario no disponible.');
        }

        $formulario->preguntas = json_decode($formulario->preguntas, true);

        // Verificar si ya envió esta evaluación
        $yaEnvio = DB::table('evaluacion_enviada')
            ->where('formulario_id', $id)
            ->where('tipo_evaluador', $role)
            ->where('registro_evaluador', $registro)
            ->exists();

        $contexto = null;
        $sinGrupo = false;

        if ($role === 'Postulante' && $formulario->tipo === 'postulante_a_docente') {
            $postulante = DB::table('postulante')->where('registro', $registro)->first();

            if ($postulante && $postulante->codigo_grupo) {
                $info = DB::table('grupo')
                    ->join('docente', 'grupo.codigo_docente', '=', 'docente.id')
                    ->join('persona', 'docente.id_persona', '=', 'persona.id')
                    ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
                    ->where('grupo.codigo', $postulante->codigo_grupo)
                    ->select(
                        'grupo.codigo as grupo_codigo',
                        'grupo.nombre_grupo',
                        'docente.id as docente_id',
                        'docente.codigo as docente_codigo',
                        'persona.nombre as docente_nombre',
                        'persona.apellido as docente_apellido',
                        'materia.nombre_materia'
                    )
                    ->first();

                if ($info) {
                    $contexto = [
                        'tipo'             => 'docente',
                        'nombre'           => $info->docente_nombre . ' ' . $info->docente_apellido,
                        'detalle'          => $info->nombre_materia . ' — ' . $info->nombre_grupo,
                        'id_docente'       => $info->docente_id,
                        'id_grupo'         => $info->grupo_codigo,
                    ];
                }
            } else {
                $sinGrupo = true;
            }
        } elseif ($role === 'Docente' && $formulario->tipo === 'docente_a_curso') {
            $docente = DB::table('docente')->where('codigo', $registro)->first();

            if ($docente) {
                $grupo = DB::table('grupo')
                    ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
                    ->where('grupo.codigo_docente', $docente->id)
                    ->select('grupo.codigo', 'grupo.nombre_grupo', 'materia.nombre_materia')
                    ->first();

                if ($grupo) {
                    $contexto = [
                        'tipo'     => 'curso',
                        'nombre'   => $grupo->nombre_materia,
                        'detalle'  => $grupo->nombre_grupo,
                        'id_docente' => null,
                        'id_grupo'   => $grupo->codigo,
                    ];
                } else {
                    $sinGrupo = true;
                }
            }
        }

        return Inertia::render('EvaluacionResponder', [
            'formulario' => $formulario,
            'contexto'   => $contexto,
            'yaEnvio'    => $yaEnvio,
            'sinGrupo'   => $sinGrupo,
            'registro'   => $registro,
            'role'       => $role,
        ]);
    }

    // Guardar respuestas enviadas
    public function guardarRespuesta($id, Request $request)
    {
        $validated = $request->validate([
            'registro'   => 'required|string',
            'role'       => 'required|in:Postulante,Docente',
            'respuestas' => 'required|array|min:1',
            'respuestas.*.pregunta_id'    => 'required|integer',
            'respuestas.*.texto_pregunta' => 'required|string',
            'respuestas.*.puntuacion'     => 'required|integer|between:1,5',
            'id_docente' => 'nullable|integer',
            'id_grupo'   => 'nullable|integer',
        ]);

        $formulario = DB::table('formulario_evaluacion')->find($id);
        if (!$formulario || !$formulario->activo) {
            return response()->json(['message' => 'Formulario no disponible.'], 422);
        }

        // Prevenir doble envío
        $yaEnvio = DB::table('evaluacion_enviada')
            ->where('formulario_id', $id)
            ->where('tipo_evaluador', $validated['role'])
            ->where('registro_evaluador', $validated['registro'])
            ->exists();

        if ($yaEnvio) {
            return response()->json(['message' => 'Ya enviaste esta evaluación.'], 422);
        }

        DB::table('evaluacion_enviada')->insert([
            'formulario_id'       => $id,
            'tipo_evaluador'      => $validated['role'],
            'registro_evaluador'  => $validated['registro'],
            'id_docente_evaluado' => $validated['id_docente'] ?? null,
            'id_grupo_evaluado'   => $validated['id_grupo'] ?? null,
            'respuestas'          => json_encode($validated['respuestas']),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return response()->json(['message' => 'Evaluación enviada correctamente.']);
    }

    // Resultados de un formulario (Decano/Admin)
    public function resultados($id, Request $request)
    {
        $formulario = DB::table('formulario_evaluacion')->find($id);
        if (!$formulario) {
            abort(404);
        }

        $formulario->preguntas = json_decode($formulario->preguntas, true);

        $enviadas = DB::table('evaluacion_enviada')
            ->where('formulario_id', $id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($e) {
                $e->respuestas_parsed = json_decode($e->respuestas, true);
                return $e;
            });

        // Calcular promedio por pregunta
        $promediosPorPregunta = array_map(function ($pregunta) use ($enviadas) {
            $puntuaciones = [];
            $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

            foreach ($enviadas as $e) {
                foreach ($e->respuestas_parsed as $ans) {
                    if ($ans['pregunta_id'] == $pregunta['id']) {
                        $p = (int)$ans['puntuacion'];
                        $puntuaciones[] = $p;
                        if (isset($dist[$p])) $dist[$p]++;
                    }
                }
            }

            return [
                'pregunta_id'     => $pregunta['id'],
                'texto'           => $pregunta['texto'],
                'promedio'        => count($puntuaciones) > 0
                    ? round(array_sum($puntuaciones) / count($puntuaciones), 2) : 0,
                'total_respuestas' => count($puntuaciones),
                'distribucion'    => $dist,
            ];
        }, $formulario->preguntas);

        $promedioGeneral = count($promediosPorPregunta) > 0
            ? round(array_sum(array_column($promediosPorPregunta, 'promedio')) / count($promediosPorPregunta), 2)
            : 0;

        // Info de docentes evaluados (para tipo postulante_a_docente)
        $docentesInfo = [];
        if ($formulario->tipo === 'postulante_a_docente') {
            $docIds = $enviadas->pluck('id_docente_evaluado')->unique()->filter()->values();
            foreach ($docIds as $docId) {
                $doc = DB::table('docente')
                    ->join('persona', 'docente.id_persona', '=', 'persona.id')
                    ->where('docente.id', $docId)
                    ->select('docente.id', 'docente.codigo', 'persona.nombre', 'persona.apellido')
                    ->first();
                if ($doc) $docentesInfo[$docId] = $doc;
            }
        }

        return Inertia::render('EvaluacionResultados', [
            'formulario'           => $formulario,
            'totalRespuestas'      => $enviadas->count(),
            'promedioGeneral'      => $promedioGeneral,
            'promediosPorPregunta' => $promediosPorPregunta,
            'enviadas'             => $enviadas,
            'docentesInfo'         => array_values($docentesInfo),
            'registro'             => $request->query('registro'),
            'role'                 => $request->query('role'),
        ]);
    }

    // API: evaluaciones pendientes para el dashboard del usuario
    public function pendientes(Request $request)
    {
        $registro = strtoupper(trim($request->query('registro', '')));
        $role     = $request->query('role', '');

        if (!$registro || !in_array($role, ['Postulante', 'Docente'])) {
            return response()->json([]);
        }

        // Formularios activos del tipo correspondiente
        $tipo = $role === 'Postulante' ? 'postulante_a_docente' : 'docente_a_curso';

        $activos = DB::table('formulario_evaluacion')
            ->where('activo', true)
            ->where('tipo', $tipo)
            ->get();

        // Filtrar los que ya envió
        $yaEnviados = DB::table('evaluacion_enviada')
            ->where('tipo_evaluador', $role)
            ->where('registro_evaluador', $registro)
            ->pluck('formulario_id')
            ->toArray();

        $pendientes = $activos->filter(fn($f) => !in_array($f->id, $yaEnviados));

        // Para Postulante: solo mostrar si tiene grupo asignado
        if ($role === 'Postulante') {
            $postulante = DB::table('postulante')->where('registro', $registro)->first();
            if (!$postulante || !$postulante->codigo_grupo) {
                return response()->json([]);
            }
        }

        return response()->json(
            $pendientes->values()->map(fn($f) => [
                'id'     => $f->id,
                'titulo' => $f->titulo,
                'tipo'   => $f->tipo,
            ])
        );
    }
}
