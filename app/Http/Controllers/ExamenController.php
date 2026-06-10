<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamenController extends Controller
{
    // Obtener todos los exámenes
    public function index()
    {
        $examenes = DB::table('examen')
            ->leftJoin('materia', 'examen.id_materia', '=', 'materia.codigo')
            ->leftJoin('postulante', 'examen.registro_postulante', '=', 'postulante.id')
            ->select('examen.*', 'materia.nombre_materia', 'postulante.registro')
            ->get();
        return response()->json($examenes);
    }

    // Obtener exámenes de un postulante
    public function examenesPostulante($registro_postulante)
    {
        $examenes = DB::table('examen')
            ->where('registro_postulante', $registro_postulante)
            ->leftJoin('materia', 'examen.id_materia', '=', 'materia.codigo')
            ->select('examen.*', 'materia.nombre_materia')
            ->get();
        return response()->json($examenes);
    }

    // Crear examen individual
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha_examen' => 'required|date',
                'id_materia' => 'required|integer',
                'registro_postulante' => 'required|integer',
                'tipo_examen' => 'nullable|string|max:50',
                'aula_examen' => 'nullable|string|max:50',
                'hora_inicio' => 'nullable|date_format:H:i',
                'hora_fin' => 'nullable|date_format:H:i',
                'puntaje_maximo' => 'nullable|numeric',
                'estado' => 'nullable|in:programado,realizado,cancelado',
            ]);

            DB::table('examen')->insert([
                'fecha_examen' => $validated['fecha_examen'],
                'id_materia' => $validated['id_materia'],
                'registro_postulante' => $validated['registro_postulante'],
                'tipo_examen' => $validated['tipo_examen'] ?? 'parcial',
                'aula_examen' => $validated['aula_examen'] ?? null,
                'hora_inicio' => $validated['hora_inicio'] ?? null,
                'hora_fin' => $validated['hora_fin'] ?? null,
                'puntaje_maximo' => $validated['puntaje_maximo'] ?? 100,
                'estado' => $validated['estado'] ?? 'programado',
                'created_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Examen creado exitosamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Crear exámenes masivos
    public function crearMasivos(Request $request)
    {
        try {
            $json_examenes = json_encode($request->input('examenes', []));
            DB::statement("CALL sp_crear_examenes_masivos(?)", [$json_examenes]);
            
            return response()->json([
                'success' => true,
                'message' => 'Exámenes creados exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Actualizar examen
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'estado' => 'nullable|in:programado,realizado,cancelado',
                'aula_examen' => 'nullable|string|max:50',
                'hora_inicio' => 'nullable|date_format:H:i',
                'hora_fin' => 'nullable|date_format:H:i',
            ]);

            DB::table('examen')->where('codigo', $id)->update($validated);

            return response()->json(['success' => true, 'message' => 'Examen actualizado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Eliminar examen
    public function destroy($id)
    {
        try {
            DB::table('examen')->where('codigo', $id)->delete();
            return response()->json(['success' => true, 'message' => 'Examen eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
