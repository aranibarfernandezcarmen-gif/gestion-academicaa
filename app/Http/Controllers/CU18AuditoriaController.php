<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CU18AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $this->assertDecano($request);

        $bitacora = $this->buildQuery($request)
            ->orderBy('bitacora.fecha_hora', 'desc')
            ->paginate(50);

        $usuarios = DB::table('persona')
            ->select('id', 'nombre', 'apellido')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('CU18Auditoria', [
            'bitacora' => $bitacora,
            'usuarios' => $usuarios,
            'filtros' => [
                'usuario_id' => $request->usuario_id,
                'accion'     => $request->accion,
                'fecha_desde' => $request->fecha_desde,
                'fecha_hasta' => $request->fecha_hasta,
            ]
        ]);
    }

    public function stream(Request $request)
    {
        $this->assertDecano($request);

        $bitacora = $this->buildQuery($request)
            ->orderBy('bitacora.fecha_hora', 'desc')
            ->paginate(50);

        return response()->json($bitacora);
    }

    private function buildQuery(Request $request)
    {
        $query = DB::table('bitacora')
            ->leftJoin('persona', 'bitacora.id_persona', '=', 'persona.id')
            ->select(
                'bitacora.codigo',
                'bitacora.accion',
                'bitacora.fecha_hora',
                'bitacora.ip_origen',
                'bitacora.id_persona',
                'persona.nombre',
                'persona.apellido',
                DB::raw("CASE
                    WHEN EXISTS (SELECT 1 FROM decano WHERE decano.id_persona = persona.id) THEN 'decano'
                    WHEN EXISTS (SELECT 1 FROM docente WHERE docente.codigo = persona.ci) THEN 'docente'
                    WHEN EXISTS (SELECT 1 FROM administrativo WHERE administrativo.id_persona = persona.id) THEN 'administrativo'
                    WHEN EXISTS (SELECT 1 FROM coordinador WHERE coordinador.id_persona = persona.id) THEN 'coordinador'
                    WHEN EXISTS (SELECT 1 FROM postulante WHERE postulante.id_persona = persona.id) THEN 'postulante'
                    ELSE 'sistema'
                END as rol")
            );

        if ($request->filled('usuario_id')) {
            $query->where('bitacora.id_persona', $request->usuario_id);
        }
        if ($request->filled('accion')) {
            $query->where('bitacora.accion', 'like', '%' . $request->accion . '%');
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('bitacora.fecha_hora', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('bitacora.fecha_hora', '<=', $request->fecha_hasta);
        }

        return $query;
    }

    private function assertDecano(Request $request)
    {
        $role     = $request->session()->get('role');
        $personaId = $request->session()->get('persona_id');

        if ($role !== 'decano' || !$personaId) {
            abort(403, 'Acceso restringido solo para Decano');
        }

        if (!DB::table('decano')->where('id_persona', $personaId)->exists()) {
            abort(403, 'Acceso restringido solo para Decano');
        }
    }
}
