<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriaController extends Controller
{
    // Obtener todas las materias
    public function index()
    {
        $materias = DB::table('materia')
            ->leftJoin('carrera', 'materia.id_carrera', '=', 'carrera.codigo')
            ->select('materia.*', 'carrera.nombre_carrera')
            ->get();
        return response()->json($materias);
    }

    // Obtener materias por carrera
    public function porCarrera($id_carrera)
    {
        $materias = DB::table('materia')
            ->where('id_carrera', $id_carrera)
            ->get();
        return response()->json($materias);
    }

    // Crear materia individual
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'sigla' => 'required|string|max:10',
                'nombre_materia' => 'required|string|max:100',
                'id_carrera' => 'required|integer',
                'descripcion' => 'nullable|string',
                'creditos' => 'nullable|integer',
                'horas_teorica' => 'nullable|integer',
                'horas_practica' => 'nullable|integer',
                'estado' => 'nullable|in:activa,inactiva',
            ]);

            DB::table('materia')->insert([
                'sigla' => $validated['sigla'],
                'nombre_materia' => $validated['nombre_materia'],
                'id_carrera' => $validated['id_carrera'],
                'descripcion' => $validated['descripcion'] ?? null,
                'creditos' => $validated['creditos'] ?? 0,
                'horas_teorica' => $validated['horas_teorica'] ?? 0,
                'horas_practica' => $validated['horas_practica'] ?? 0,
                'estado' => $validated['estado'] ?? 'activa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Materia creada exitosamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Crear materias masivas
    public function crearMasivas(Request $request)
    {
        try {
            $json_materias = json_encode($request->input('materias', []));
            DB::statement("CALL sp_crear_materias_masivas(?)", [$json_materias]);
            
            return response()->json([
                'success' => true,
                'message' => 'Materias creadas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Actualizar materia
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre_materia' => 'nullable|string|max:100',
                'descripcion' => 'nullable|string',
                'creditos' => 'nullable|integer',
                'horas_teorica' => 'nullable|integer',
                'horas_practica' => 'nullable|integer',
                'estado' => 'nullable|in:activa,inactiva',
            ]);

            DB::table('materia')->where('codigo', $id)->update([
                ...$validated,
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Materia actualizada exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Eliminar materia
    public function destroy($id)
    {
        try {
            DB::table('materia')->where('codigo', $id)->delete();
            return response()->json(['success' => true, 'message' => 'Materia eliminada exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
