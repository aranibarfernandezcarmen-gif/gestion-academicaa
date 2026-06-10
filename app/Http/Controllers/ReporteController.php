<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    // Obtener todos los reportes
    public function index()
    {
        $reportes = DB::table('reporte')
            ->leftJoin('persona', 'reporte.id_persona', '=', 'persona.id')
            ->select('reporte.*', 'persona.nombre', 'persona.apellido')
            ->get();
        return response()->json($reportes);
    }

    // Obtener reportes de una persona
    public function porPersona($id_persona)
    {
        $reportes = DB::table('reporte')
            ->where('id_persona', $id_persona)
            ->orderBy('fecha_generacion', 'desc')
            ->get();
        return response()->json($reportes);
    }

    // Obtener reportes por tipo
    public function porTipo($tipo_reporte)
    {
        $reportes = DB::table('reporte')
            ->where('tipo_reporte', $tipo_reporte)
            ->orderBy('fecha_generacion', 'desc')
            ->get();
        return response()->json($reportes);
    }

    // Crear reporte individual
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tipo_reporte' => 'required|string|max:50',
                'id_persona' => 'required|integer',
                'descripcion' => 'nullable|string',
                'formato' => 'required|string|max:20',
                'filtros' => 'nullable|json',
            ]);

            DB::table('reporte')->insert([
                'tipo_reporte' => $validated['tipo_reporte'],
                'id_persona' => $validated['id_persona'],
                'descripcion' => $validated['descripcion'] ?? null,
                'fecha_generacion' => now()->format('Y-m-d'),
                'formato' => $validated['formato'],
                'estado' => 'generado',
                'filtros' => $validated['filtros'] ?? null,
                'cantidad_registros' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Reporte creado exitosamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Generar reporte (PA)
    public function generar(Request $request)
    {
        try {
            $validated = $request->validate([
                'tipo_reporte' => 'required|string|max:50',
                'id_persona' => 'required|integer',
                'filtros' => 'nullable|json',
            ]);

            $filtros = $validated['filtros'] ?? json_encode([]);

            DB::statement("CALL sp_generar_reporte(?, ?, ?)", [
                $validated['tipo_reporte'],
                $validated['id_persona'],
                $filtros
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reporte generado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Actualizar estado del reporte
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'estado' => 'required|in:generado,enviado,visto,archivado',
            ]);

            DB::table('reporte')->where('codigo', $id)->update([
                'estado' => $validated['estado'],
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Reporte actualizado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Eliminar reporte
    public function destroy($id)
    {
        try {
            DB::table('reporte')->where('codigo', $id)->delete();
            return response()->json(['success' => true, 'message' => 'Reporte eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
