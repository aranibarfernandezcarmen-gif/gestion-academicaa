<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\BitacoraService;
use Inertia\Inertia;

class CU07ConfigurarCuposController extends Controller
{
    public function index()
    {
        $carreras = DB::table('carrera')
            ->join('facultad', 'carrera.facultad_sigla', '=', 'facultad.sigla')
            ->select(
                'carrera.codigo',
                'carrera.sigla',
                'carrera.nombre_carrera',
                'carrera.facultad_sigla',
                'facultad.nombre_facultad'
            )
            ->get();

        $cupos = DB::table('cupo_carrera')
            ->join('carrera', 'cupo_carrera.carrera_id', '=', 'carrera.codigo')
            ->join('facultad', 'carrera.facultad_sigla', '=', 'facultad.sigla')
            ->join('gestion_academica', 'cupo_carrera.gestion_academica_id', '=', 'gestion_academica.codigo')
            ->select(
                'cupo_carrera.codigo as cupo_codigo',
                'carrera.codigo',
                'carrera.sigla',
                'carrera.nombre_carrera',
                'carrera.facultad_sigla',
                'cupo_carrera.cupo_maximo',
                'cupo_carrera.cupos_disponibles',
                'gestion_academica.codigo as gestion_codigo',
                'gestion_academica.gestion',
                'gestion_academica.anio'
            )
            ->orderBy('gestion_academica.codigo', 'desc')
            ->orderBy('carrera.codigo')
            ->get();

        $gestiones = DB::table('gestion_academica')
            ->select('codigo', 'gestion', 'anio')
            ->orderBy('codigo')
            ->get();

        return Inertia::render('CU07ConfigurarCupos', [
            'carreras'  => $carreras,
            'cupos'     => $cupos,
            'gestiones' => $gestiones,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gestion_codigo' => 'required|exists:gestion_academica,codigo',
            'carrera_id'     => 'required|exists:carrera,codigo',
            'cupo_maximo'    => 'required|integer|min:1',
            'cupos_disponibles' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::table('cupo_carrera')->insert([
                'carrera_id'          => $request->carrera_id,
                'gestion_academica_id' => $request->gestion_codigo,
                'cupo_maximo'         => $request->cupo_maximo,
                'cupos_disponibles'   => $request->cupos_disponibles,
            ]);

            BitacoraService::registrar(
                "Creación de cupo para carrera {$request->carrera_id} gestión {$request->gestion_codigo}",
                $request->ip()
            );

            return response()->json(['message' => 'Cupo creado correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el cupo: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $cupo_codigo)
    {
        $validator = Validator::make($request->all(), [
            'gestion_codigo' => 'required|exists:gestion_academica,codigo',
            'cupo_maximo'    => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::table('cupo_carrera')
                ->where('codigo', $cupo_codigo)
                ->update([
                    'gestion_academica_id' => $request->gestion_codigo,
                    'cupo_maximo'          => $request->cupo_maximo,
                    'cupos_disponibles'    => $request->cupo_maximo,
                ]);

            BitacoraService::registrar(
                "Actualización de cupo {$cupo_codigo}",
                $request->ip()
            );

            return response()->json(['message' => 'Cupo actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el cupo: ' . $e->getMessage()], 500);
        }
    }

    public function aceptados($cupo_codigo)
    {
        $cupo = DB::table('cupo_carrera')->where('codigo', $cupo_codigo)->first();
        if (!$cupo) {
            return response()->json(['message' => 'Cupo no encontrado'], 404);
        }

        // Un postulante tiene "Promedio Final" (CU06) solo si tiene nota en TODAS
        // las materias (grupos). Aquí calculamos cuántos grupos hay para exigirlo.
        $numGrupos = (int) DB::table('grupo')->count();

        // Postulantes ACEPTADOS de esta carrera: tienen Promedio Final (notas en las
        // 4 materias) y ese promedio final es >= 60. Se agrupa por postulante.
        $postulantes = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->join('calificacion', function ($join) {
                $join->on('calificacion.registro_postulante', '=', 'postulante.id')
                     ->whereNotNull('calificacion.promedio');
            })
            ->leftJoin('carrera', 'postulante.carrera_primera_opcion_id', '=', 'carrera.codigo')
            ->where('postulante.carrera_primera_opcion_id', $cupo->carrera_id)
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->groupBy(
                'postulante.id', 'postulante.registro', 'postulante.carrera_asignada_id',
                'persona.ci', 'persona.nombre', 'persona.apellido', 'carrera.nombre_carrera'
            )
            ->havingRaw('COUNT(DISTINCT calificacion.codigo_grupo) >= ? AND AVG(calificacion.promedio) >= 60', [$numGrupos])
            ->select(
                'postulante.id',
                'postulante.registro',
                'postulante.carrera_asignada_id',
                'persona.ci',
                DB::raw("persona.nombre || ' ' || persona.apellido AS nombre_completo"),
                DB::raw("COALESCE(carrera.nombre_carrera, '—') AS carrera_ingresada"),
                DB::raw('ROUND(AVG(calificacion.promedio), 2) AS promedio')
            )
            ->orderByRaw('AVG(calificacion.promedio) DESC')
            ->get();

        // Descontar 1 cupo por cada aceptado que AÚN NO está asignado a esta carrera.
        // Es idempotente: si se vuelve a dar "Mostrar", los ya marcados no restan otra vez.
        DB::transaction(function () use ($postulantes, $cupo) {
            foreach ($postulantes as $p) {
                $yaAsignado = (int) ($p->carrera_asignada_id ?? 0) === (int) $cupo->carrera_id;
                if (! $yaAsignado) {
                    DB::table('cupo_carrera')
                        ->where('codigo', $cupo->codigo)
                        ->where('cupos_disponibles', '>', 0)
                        ->decrement('cupos_disponibles');

                    // Marca de idempotencia: queda asignado a esta carrera, así un
                    // segundo "Mostrar" no vuelve a descontar cupo por el mismo postulante.
                    DB::table('postulante')->where('id', $p->id)->update([
                        'carrera_asignada_id' => $cupo->carrera_id,
                    ]);
                }
            }
        });

        $cuposDisponibles = DB::table('cupo_carrera')->where('codigo', $cupo_codigo)->value('cupos_disponibles');

        return response()->json([
            'postulantes'       => $postulantes,
            'cupos_disponibles' => $cuposDisponibles,
        ]);
    }

    public function destroy($cupo_codigo)
    {
        try {
            DB::table('cupo_carrera')->where('codigo', $cupo_codigo)->delete();

            BitacoraService::registrar(
                "Eliminación de cupo {$cupo_codigo}",
                request()->ip()
            );

            return response()->json(['message' => 'Cupo eliminado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el cupo: ' . $e->getMessage()], 500);
        }
    }
}
