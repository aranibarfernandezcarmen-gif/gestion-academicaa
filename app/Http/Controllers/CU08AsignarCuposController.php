<?php

namespace App\Http\Controllers;

use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CU08AsignarCuposController extends Controller
{
    public function index(): Response
    {
        $cupos = DB::table('cupo_carrera')
            ->join('carrera', 'cupo_carrera.carrera_id', '=', 'carrera.codigo')
            ->join('gestion_academica', 'cupo_carrera.gestion_academica_id', '=', 'gestion_academica.codigo')
            ->select(
                'cupo_carrera.codigo as cupo_codigo',
                'cupo_carrera.carrera_id',
                'carrera.codigo',
                'carrera.sigla',
                'carrera.nombre_carrera',
                'cupo_carrera.cupo_maximo',
                'cupo_carrera.cupos_disponibles',
                'gestion_academica.anio',
                'gestion_academica.gestion'
            )
            ->orderBy('carrera.codigo')
            ->get();

        $carreras  = DB::table('carrera')->select('codigo', 'sigla', 'nombre_carrera')->orderBy('codigo')->get();
        $gestiones = DB::table('gestion_academica')->select('codigo', 'anio', 'gestion')->get();

        return Inertia::render('CU08AsignarCupos', [
            'cupos'      => $cupos,
            'carreras'   => $carreras,
            'gestiones'  => $gestiones,
            'ingresados' => $this->getIngresados(),
        ]);
    }

    public function assign(Request $request)
    {
        $summary = ['procesados' => 0, 'asignados' => 0, 'sin_cupo' => 0];
        $details = [];

        // Número de grupos del sistema (igual que CU06)
        $numGrupos = DB::table('grupo')->count();

        // --- RESET PREVIO: limpiar asignaciones anteriores ---
        // Restaurar cupos por cada postulante previamente asignado
        $yaAsignados = DB::table('postulante')
            ->whereNotNull('carrera_asignada_id')
            ->where('estado_asignacion', 'Asignado')
            ->select('id', 'carrera_asignada_id')
            ->get();

        foreach ($yaAsignados as $pa) {
            DB::table('cupo_carrera')
                ->where('carrera_id', $pa->carrera_asignada_id)
                ->increment('cupos_disponibles');
        }

        // Limpiar estado de los postulantes asignados (excepto Eliminados)
        DB::table('postulante')
            ->where('estado_asignacion', 'Asignado')
            ->update([
                'estado_asignacion'  => 'Pendiente',
                'carrera_asignada_id' => null,
            ]);

        // Limpiar tabla de control de asignación
        DB::table('control_asignacion_carrera')->delete();

        // Todos los postulantes no eliminados (el reset ya limpió los 'Asignado')
        $postulantesBase = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->select(
                'postulante.id',
                'postulante.registro',
                'postulante.id_persona',
                'postulante.carrera_primera_opcion_id',
                'postulante.carrera_segunda_opcion_id',
                DB::raw("persona.nombre || ' ' || persona.apellido as nombre_completo"),
                'persona.ci'
            )
            ->get();

        $postIds = $postulantesBase->pluck('id');

        // Calificaciones con grupo asignado (misma lógica que CU06)
        $calificaciones = DB::table('calificacion')
            ->whereIn('registro_postulante', $postIds)
            ->whereNotNull('codigo_grupo')
            ->select('registro_postulante', 'codigo_grupo', 'nota1', 'nota2', 'nota3')
            ->get()
            ->groupBy('registro_postulante');

        // Filtrar solo APROBADOS según CU06:
        // - tiene calificaciones en TODOS los grupos
        // - TODAS las notas individuales >= 60
        // - calcular promedio_final = promedio de promedios por materia
        $postulantesOrdenados = $postulantesBase->filter(function ($p) use ($calificaciones, $numGrupos) {
            $pcals = $calificaciones->get($p->id, collect());
            if ($pcals->count() < $numGrupos) return false; // no completo
            // Aprobado si el promedio de CADA materia >= 60 (igual que CU06)
            foreach ($pcals as $c) {
                if (($c->nota1 + $c->nota2 + $c->nota3) / 3 < 60) return false;
            }
            return true;
        })->map(function ($p) use ($calificaciones, $numGrupos) {
            $pcals = $calificaciones->get($p->id, collect());
            $promediosPorMateria = $pcals->map(fn ($c) => ($c->nota1 + $c->nota2 + $c->nota3) / 3);
            $p->promedio_final = round($promediosPorMateria->avg(), 2);
            return $p;
        })->sortByDesc('promedio_final')->values();

        DB::transaction(function () use ($postulantesOrdenados, &$summary, &$details, $request) {
            $cupos     = DB::table('cupo_carrera')->get()->keyBy('carrera_id');
            $gestionId = DB::table('gestion_academica')->orderBy('codigo', 'desc')->value('codigo') ?? 1;

            foreach ($postulantesOrdenados as $postulante) {
                $summary['procesados']++;
                $assignedCarreraId = null;
                $esSegundaOpcion   = false;

                // Primera opción
                if ($postulante->carrera_primera_opcion_id
                    && isset($cupos[$postulante->carrera_primera_opcion_id])
                    && $cupos[$postulante->carrera_primera_opcion_id]->cupos_disponibles > 0) {
                    $assignedCarreraId = $postulante->carrera_primera_opcion_id;
                }
                // Segunda opción
                elseif ($postulante->carrera_segunda_opcion_id
                    && isset($cupos[$postulante->carrera_segunda_opcion_id])
                    && $cupos[$postulante->carrera_segunda_opcion_id]->cupos_disponibles > 0) {
                    $assignedCarreraId = $postulante->carrera_segunda_opcion_id;
                    $esSegundaOpcion   = true;
                }

                if ($assignedCarreraId === null) {
                    DB::table('postulante')->where('id', $postulante->id)
                        ->update(['estado_asignacion' => 'Sin cupo']);
                    $summary['sin_cupo']++;
                    $details[] = [
                        'registro' => $postulante->registro,
                        'nombre'   => $postulante->nombre_completo,
                        'ci'       => $postulante->ci,
                        'promedio' => $postulante->promedio_final,
                        'resultado' => 'Sin cupo disponible',
                        'opcion'    => '—',
                        'carrera_id' => null,
                    ];
                    continue;
                }

                // Asignar postulante
                DB::table('postulante')->where('id', $postulante->id)->update([
                    'carrera_asignada_id' => $assignedCarreraId,
                    'estado_asignacion'   => 'Asignado',
                ]);

                // Decrementar cupo en BD
                DB::table('cupo_carrera')
                    ->where('carrera_id', $assignedCarreraId)
                    ->where('gestion_academica_id', $gestionId)
                    ->decrement('cupos_disponibles');

                // Sincronizar cupo local
                if (isset($cupos[$assignedCarreraId])) {
                    $cupos[$assignedCarreraId]->cupos_disponibles--;
                }

                // Registrar en control_asignacion_carrera
                $existe = DB::table('control_asignacion_carrera')
                    ->where('postulante_id', $postulante->id)->exists();
                if (!$existe) {
                    DB::statement("SELECT setval('control_asignacion_carrera_codigo_seq', COALESCE((SELECT MAX(codigo) FROM control_asignacion_carrera), 1))");
                    DB::table('control_asignacion_carrera')->insert([
                        'postulante_id'       => $postulante->id,
                        'carrera_asignada_id' => $assignedCarreraId,
                        'fecha_asignacion'    => now()->toDateString(),
                        'es_segunda_opcion'   => $esSegundaOpcion,
                        'prioridad'           => $esSegundaOpcion ? 2 : 1,
                        'observacion'         => $esSegundaOpcion
                            ? 'Asignado a segunda opción'
                            : 'Asignado a primera opción',
                    ]);
                }

                $summary['asignados']++;
                $details[] = [
                    'registro'   => $postulante->registro,
                    'nombre'     => $postulante->nombre_completo,
                    'ci'         => $postulante->ci,
                    'promedio'   => $postulante->promedio_final,
                    'resultado'  => $esSegundaOpcion ? '2da Opción' : '1ra Opción',
                    'opcion'     => $esSegundaOpcion ? '2da' : '1ra',
                    'carrera_id' => $assignedCarreraId,
                ];

                BitacoraService::registrar(
                    "CU08 Asignación: {$postulante->registro} → carrera {$assignedCarreraId} (" .
                        ($esSegundaOpcion ? '2da' : '1ra') . " opción, Prom: {$postulante->promedio_final})",
                    $request->ip(),
                    $postulante->id_persona
                );
            }
        });

        $updatedCupos = DB::table('cupo_carrera')
            ->join('carrera', 'cupo_carrera.carrera_id', '=', 'carrera.codigo')
            ->join('gestion_academica', 'cupo_carrera.gestion_academica_id', '=', 'gestion_academica.codigo')
            ->select(
                'cupo_carrera.codigo as cupo_codigo', 'cupo_carrera.carrera_id',
                'carrera.codigo', 'carrera.sigla', 'carrera.nombre_carrera',
                'cupo_carrera.cupo_maximo', 'cupo_carrera.cupos_disponibles',
                'gestion_academica.anio', 'gestion_academica.gestion'
            )
            ->orderBy('carrera.codigo')
            ->get();

        return response()->json([
            'message'    => "Asignación completada. Procesados: {$summary['procesados']}, Asignados: {$summary['asignados']}, Sin cupo: {$summary['sin_cupo']}.",
            'summary'    => $summary,
            'details'    => $details,
            'cupos'      => $updatedCupos,
            'ingresados' => $this->getIngresados(),
        ]);
    }

    private function getIngresados(): \Illuminate\Support\Collection
    {
        $ingresados = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->join('carrera as ca', 'postulante.carrera_asignada_id', '=', 'ca.codigo')
            ->where('postulante.estado_asignacion', 'Asignado')
            ->select(
                'postulante.id',
                'postulante.registro',
                'persona.nombre',
                'persona.apellido',
                'persona.ci',
                'postulante.carrera_asignada_id',
                'postulante.carrera_primera_opcion_id',
                'ca.sigla as carrera_asignada_sigla',
                'ca.nombre_carrera as carrera_asignada_nombre'
            )
            ->get();

        if ($ingresados->isEmpty()) {
            return collect();
        }

        $postIds   = $ingresados->pluck('id');
        $numGrupos = DB::table('grupo')->count();

        $cals = DB::table('calificacion')
            ->whereIn('registro_postulante', $postIds)
            ->whereNotNull('codigo_grupo')
            ->select('registro_postulante', 'codigo_grupo', 'nota1', 'nota2', 'nota3')
            ->get()
            ->groupBy('registro_postulante');

        return $ingresados->map(function ($p) use ($cals, $numGrupos) {
            $pcals = $cals->get($p->id, collect());
            // Promedio igual que CU06: promedio de promedios por materia
            $promediosPorMateria = $pcals->map(fn ($c) => ($c->nota1 + $c->nota2 + $c->nota3) / 3);
            $p->promedio_final = $promediosPorMateria->isNotEmpty()
                ? round($promediosPorMateria->avg(), 2)
                : null;
            $p->opcion = ($p->carrera_asignada_id === $p->carrera_primera_opcion_id) ? '1ra' : '2da';
            return $p;
        })->sortByDesc('promedio_final')->values();
    }
}
