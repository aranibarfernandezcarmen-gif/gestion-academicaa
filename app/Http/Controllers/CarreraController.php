<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarreraController extends Controller
{
    // Obtener todas las carreras
    public function index()
    {
        $carreras = DB::table('carrera')->get();
        return response()->json($carreras);
    }

    // Crear carrera individual
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'sigla' => 'required|string|max:10',
                'nombre_carrera' => 'required|string|max:100',
                'facultad_sigla' => 'required|string|max:10',
                'descripcion' => 'nullable|string',
                'estado' => 'nullable|in:activa,inactiva,suspendida',
            ]);

            DB::table('carrera')->insert([
                'sigla' => $validated['sigla'],
                'nombre_carrera' => $validated['nombre_carrera'],
                'facultad_sigla' => $validated['facultad_sigla'],
                'descripcion' => $validated['descripcion'] ?? null,
                'estado' => $validated['estado'] ?? 'activa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Carrera creada exitosamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Crear carreras masivas
    public function crearMasivas(Request $request)
    {
        try {
            $json_carreras = json_encode($request->input('carreras', []));
            DB::statement("CALL sp_crear_carreras_masivas(?)", [$json_carreras]);
            
            return response()->json([
                'success' => true,
                'message' => 'Carreras creadas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Actualizar carrera
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre_carrera' => 'nullable|string|max:100',
                'descripcion' => 'nullable|string',
                'estado' => 'nullable|in:activa,inactiva,suspendida',
                'total_grupos' => 'nullable|integer',
            ]);

            DB::table('carrera')->where('codigo', $id)->update([
                ...$validated,
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Carrera actualizada exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Eliminar carrera
    public function destroy($id)
    {
        try {
            DB::table('carrera')->where('codigo', $id)->delete();
            return response()->json(['success' => true, 'message' => 'Carrera eliminada exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
