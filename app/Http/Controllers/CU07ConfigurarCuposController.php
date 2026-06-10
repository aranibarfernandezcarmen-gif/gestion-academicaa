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

        $postulantes = DB::table('calificacion')
            ->join('postulante', 'calificacion.registro_postulante', '=', 'postulante.id')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->leftJoin('carrera', 'postulante.carrera_primera_opcion_id', '=', 'carrera.codigo')
            ->select(
                'postulante.registro',
                'persona.ci',
                DB::raw("persona.nombre || ' ' || persona.apellido AS nombre_completo"),
                DB::raw("COALESCE(carrera.nombre_carrera, '—') AS carrera_ingresada"),
                'calificacion.promedio'
            )
            ->where('postulante.carrera_primera_opcion_id', $cupo->carrera_id)
            ->where('calificacion.promedio', '>=', 60)
            ->whereNotNull('calificacion.promedio')
            ->orderBy('calificacion.promedio', 'desc')
            ->get();

        return response()->json($postulantes);
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
