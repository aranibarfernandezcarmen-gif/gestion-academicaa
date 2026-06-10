<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseOperationsService;
use Inertia\Inertia;

class CU09CalcularGruposController extends Controller
{
    private const CAPACIDAD_POR_GRUPO = 70;

    private function calcularGruposNecesarios(): int
    {
        $totalInscritos = DB::table('postulante')
            ->join('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->where('inscripcion.estado_pago', 'Pagado')
            ->count();

        return max(1, (int) ceil($totalInscritos / self::CAPACIDAD_POR_GRUPO));
    }

    public function index()
    {
        $totalInscritos = DB::table('postulante')
            ->join('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->where('inscripcion.estado_pago', 'Pagado')
            ->count();

        $gruposNecesarios = max(1, (int) ceil($totalInscritos / self::CAPACIDAD_POR_GRUPO));
        $totalMaterias    = DB::table('materia')->count();
        $gruposExistentes = DB::table('grupo')->count();

        $materias = DB::table('materia')
            ->select('codigo', 'sigla', 'nombre_materia')
            ->selectRaw('(SELECT COUNT(*) FROM asignacion_grupo JOIN grupo ON asignacion_grupo.grupo_codigo = grupo.codigo WHERE grupo.codigo_materia = materia.codigo) AS inscritos')
            ->selectRaw('? AS grupos_necesarios', [$gruposNecesarios])
            ->selectRaw('(SELECT COUNT(*) FROM grupo WHERE grupo.codigo_materia = materia.codigo) AS grupos_actuales')
            ->orderBy('codigo')
            ->get();

        $grupos = DB::table('grupo')
            ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
            ->select(
                'grupo.codigo',
                'grupo.nombre_grupo',
                'grupo.capacidad_maxima',
                'materia.sigla AS sigla_materia',
                'materia.nombre_materia AS nombre_materia'
            )
            ->selectRaw('(SELECT COUNT(*) FROM asignacion_grupo WHERE asignacion_grupo.grupo_codigo = grupo.codigo) AS inscritos')
            ->orderBy('materia.codigo')
            ->orderBy('grupo.codigo')
            ->get();

        return Inertia::render('CU09CalcularGrupos', [
            'totalInscritos'        => $totalInscritos,
            'gruposNecesarios'      => $gruposNecesarios,
            'gruposNecesariosTotales' => $totalMaterias * $gruposNecesarios,
            'gruposExistentes'      => $gruposExistentes,
            'capacidadPorGrupo'     => self::CAPACIDAD_POR_GRUPO,
            'materias'              => $materias,
            'grupos'                => $grupos,
        ]);
    }

    public function calcularYCrearGrupos()
    {
        $gruposNecesarios = $this->calcularGruposNecesarios();

        // Prefijo por sigla de materia
        $prefijos = ['COM' => 'C', 'FIS' => 'F', 'MAT' => 'M', 'ING' => 'I'];
        $materias = DB::table('materia')->select('codigo', 'sigla', 'nombre_materia')->orderBy('codigo')->get();

        DB::transaction(function () use ($materias, $gruposNecesarios, $prefijos) {
            // Desasignar postulantes de sus grupos actuales
            DB::table('postulante')->whereNotNull('codigo_grupo')->update(['codigo_grupo' => null]);

            // Eliminar todos los grupos existentes
            DB::table('grupo')->delete();

            // Crear grupos con nomenclatura: "Computación C1", "Física F1", etc.
            $year = date('Y');
            foreach ($materias as $materia) {
                $prefijo = $prefijos[$materia->sigla] ?? strtoupper(substr($materia->sigla, 0, 1));
                for ($i = 1; $i <= $gruposNecesarios; $i++) {
                    DB::table('grupo')->insert([
                        'nombre_grupo'    => $prefijo . $i . '-' . $materia->sigla . '-' . $year,
                        'capacidad_maxima' => self::CAPACIDAD_POR_GRUPO,
                        'codigo_materia'  => $materia->codigo,
                    ]);
                }
            }
        });

        return response()->json([
            'message'          => 'Grupos calculados y creados correctamente.',
            'grupos_por_materia' => $gruposNecesarios,
            'total_grupos'     => count($materias) * $gruposNecesarios,
        ]);
    }

    public function update(Request $request, $codigo)
    {
        $data = $request->validate([
            'nombre_grupo'    => 'required|string|max:50',
            'capacidad_maxima' => 'required|integer|min:1',
        ]);

        $updated = DB::table('grupo')->where('codigo', $codigo)->update($data);

        if (!$updated) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        return response()->json(['message' => 'Grupo actualizado correctamente']);
    }

    public function destroy($codigo)
    {
        // Desasignar postulantes del grupo antes de eliminar
        DB::table('postulante')->where('codigo_grupo', $codigo)->update(['codigo_grupo' => null]);

        $deleted = DB::table('grupo')->where('codigo', $codigo)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        return response()->json(['message' => 'Grupo eliminado correctamente']);
    }

    public function inscritos($codigo)
    {
        $estudiantes = DB::table('asignacion_grupo')
            ->join('postulante', 'asignacion_grupo.postulante_id', '=', 'postulante.id')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->where('asignacion_grupo.grupo_codigo', $codigo)
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->select('postulante.registro', 'persona.ci', 'persona.nombre', 'persona.apellido')
            ->get();

        return response()->json(['estudiantes' => $estudiantes]);
    }

    public function validateConflict(Request $request)
    {
        try {
            $validated = $request->validate([
                'codigo_horario'  => 'required|integer|exists:horario,codigo',
                'codigo_aula_id'  => 'nullable|integer',
            ]);

            $dbOperations = new DatabaseOperationsService();
            $conflicto = $dbOperations->detectarConflictoGrupo(
                $validated['codigo_horario'],
                $validated['codigo_aula_id'] ?? null
            );

            return response()->json([
                'conflicto' => $conflicto,
                'mensaje'   => $conflicto ? 'Existe conflicto de horario' : 'Sin conflictos de horario',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en validateConflict: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
