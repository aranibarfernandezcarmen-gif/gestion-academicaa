<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CU14CalendarioController extends Controller
{
    public function index()
    {
        $gestiones = DB::table('gestion_academica')
            ->orderBy('anio', 'desc')
            ->orderBy('codigo', 'desc')
            ->get();

        $actividades = DB::table('calendario_actividad')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        return Inertia::render('CU14Calendario', [
            'gestiones'   => $gestiones,
            'actividades' => $actividades,
        ]);
    }

    public function storeGestion(Request $request)
    {
        $request->validate([
            'anio'         => 'required|integer|min:2020|max:2100',
            'gestion'      => 'required|string|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        DB::table('gestion_academica')->insert([
            'anio'         => $request->anio,
            'gestion'      => $request->gestion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
        ]);

        return response()->json(['message' => 'Gestión académica registrada.']);
    }

    public function updateGestion(Request $request, $codigo)
    {
        $request->validate([
            'anio'         => 'required|integer|min:2020|max:2100',
            'gestion'      => 'required|string|max:100',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        DB::table('gestion_academica')->where('codigo', $codigo)->update([
            'anio'         => $request->anio,
            'gestion'      => $request->gestion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
        ]);

        return response()->json(['message' => 'Gestión académica actualizada.']);
    }

    public function destroyGestion($codigo)
    {
        DB::table('gestion_academica')->where('codigo', $codigo)->delete();
        return response()->json(['message' => 'Gestión académica eliminada.']);
    }

    // ---- Horario (se mantiene para compatibilidad con grupos existentes) ----
    public function storeHorario(Request $request)
    {
        $request->validate([
            'dia'         => 'required|string|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
        ]);

        DB::table('horario')->insert([
            'dia'         => $request->dia,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin'    => $request->hora_fin,
        ]);

        return response()->json(['message' => 'Horario registrado.']);
    }

    public function destroyHorario($codigo)
    {
        $inUse = DB::table('grupo')->where('codigo_horario', $codigo)->exists();
        if ($inUse) {
            return response()->json(['message' => 'No se puede eliminar: el horario está asignado a un grupo.'], 422);
        }
        DB::table('horario')->where('codigo', $codigo)->delete();
        return response()->json(['message' => 'Horario eliminado.']);
    }

    // ---- Actividades del calendario ----
    public function storeActividad(Request $request)
    {
        $request->validate([
            'fecha'                => 'required|date',
            'titulo'               => 'required|string|max:200',
            'descripcion'          => 'nullable|string',
            'color'                => 'required|string|max:30',
            'gestion_academica_id' => 'nullable|integer|exists:gestion_academica,codigo',
        ]);

        $id = DB::table('calendario_actividad')->insertGetId([
            'fecha'                => $request->fecha,
            'titulo'               => $request->titulo,
            'descripcion'          => $request->descripcion,
            'color'                => $request->color,
            'gestion_academica_id' => $request->gestion_academica_id,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $actividad = DB::table('calendario_actividad')->where('id', $id)->first();
        return response()->json(['message' => 'Actividad registrada.', 'actividad' => $actividad]);
    }

    public function updateActividad(Request $request, $id)
    {
        $request->validate([
            'fecha'                => 'required|date',
            'titulo'               => 'required|string|max:200',
            'descripcion'          => 'nullable|string',
            'color'                => 'required|string|max:30',
            'gestion_academica_id' => 'nullable|integer|exists:gestion_academica,codigo',
        ]);

        DB::table('calendario_actividad')->where('id', $id)->update([
            'fecha'                => $request->fecha,
            'titulo'               => $request->titulo,
            'descripcion'          => $request->descripcion,
            'color'                => $request->color,
            'gestion_academica_id' => $request->gestion_academica_id,
            'updated_at'           => now(),
        ]);

        $actividad = DB::table('calendario_actividad')->where('id', $id)->first();
        return response()->json(['message' => 'Actividad actualizada.', 'actividad' => $actividad]);
    }

    public function destroyActividad($id)
    {
        DB::table('calendario_actividad')->where('id', $id)->delete();
        return response()->json(['message' => 'Actividad eliminada.']);
    }
}
