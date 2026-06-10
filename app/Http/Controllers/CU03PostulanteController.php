<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\BitacoraService;
use Inertia\Inertia;
use Inertia\Response;

class CU03PostulanteController extends Controller
{
    public function index(): Response
    {
        $carreras = DB::table('carrera')
            ->select('codigo', 'nombre_carrera')
            ->orderBy('nombre_carrera')
            ->get();

        return Inertia::render('CU03GestionarPostulantes', [
            'carreras' => $carreras,
        ]);
    }

    public function getPostulantes(Request $request)
    {
        $estado = $request->query('estado', 'Pagado');

        $query = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->leftJoin('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
            ->leftJoin('carrera as cp', 'postulante.carrera_primera_opcion_id', '=', 'cp.codigo')
            ->leftJoin('carrera as cs', 'postulante.carrera_segunda_opcion_id', '=', 'cs.codigo')
            ->select(
                'postulante.id',
                'postulante.registro',
                'postulante.colegio_procedencia',
                'postulante.titulo_bachiller',
                'postulante.estado_asignacion',
                'postulante.carrera_primera_opcion_id',
                'postulante.carrera_segunda_opcion_id',
                'postulante.id_persona',
                'postulante.observaciones_rechazo',
                'persona.ci',
                'persona.nombre',
                'persona.apellido',
                'persona.direccion',
                'persona.telefono',
                'persona.correo_electronico',
                'persona.ciudad',
                'persona.fecha_nacimiento',
                'persona.sexo',
                DB::raw("COALESCE(inscripcion.estado_pago, 'Pendiente') as estado_pago"),
                'inscripcion.fecha_inscripcion',
                'cp.nombre_carrera as primera_opcion',
                'cs.nombre_carrera as segunda_opcion'
            )
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->orderBy('postulante.id', 'desc');

        if ($estado === 'Rechazado') {
            $query->where('postulante.estado_asignacion', 'Rechazado');
        } else {
            $query->where('postulante.estado_asignacion', '<>', 'Rechazado')
                  ->whereRaw("COALESCE(inscripcion.estado_pago, 'Pendiente') = ?", [$estado]);
        }

        return response()->json($query->get());
    }

    public function buscarPorRegistro(Request $request)
    {
        $registro = strtoupper(trim($request->query('registro', '')));

        if (!$registro) {
            return response()->json(['error' => 'Debe proporcionar un número de registro.'], 400);
        }

        // Buscar el postulante por registro (no eliminado)
        $postulante = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->leftJoin('carrera as cp', 'postulante.carrera_primera_opcion_id', '=', 'cp.codigo')
            ->leftJoin('carrera as cs', 'postulante.carrera_segunda_opcion_id', '=', 'cs.codigo')
            ->select(
                'postulante.id',
                'postulante.registro',
                'postulante.id_persona',
                'postulante.titulo_bachiller',
                'postulante.colegio_procedencia',
                'postulante.carrera_primera_opcion_id',
                'postulante.carrera_segunda_opcion_id',
                'postulante.estado_asignacion',
                'persona.ci',
                'persona.nombre',
                'persona.apellido',
                'persona.fecha_nacimiento',
                'persona.sexo',
                'persona.direccion',
                'persona.telefono',
                'persona.correo_electronico',
                'persona.ciudad'
            )
            ->where('postulante.registro', $registro)
            ->where('postulante.estado_asignacion', '<>', 'Eliminado')
            ->orderBy('postulante.id', 'desc')
            ->first();

        if (!$postulante) {
            return response()->json(['error' => 'No se encontró ningún postulante con el registro "' . $registro . '".'], 404);
        }

        // Verificar si es reprobado según la lógica de CU06:
        // Tiene calificaciones en todos los grupos Y al menos una materia con promedio < 60
        $numGrupos = DB::table('grupo')->count();

        $calificaciones = DB::table('calificacion')
            ->where('registro_postulante', $postulante->id)
            ->whereNotNull('codigo_grupo')
            ->get();

        $numCalificados = $calificaciones->count();
        $tieneExamenes  = $numGrupos > 0 && $numCalificados >= $numGrupos;

        if ($tieneExamenes) {
            $esReprobado = $calificaciones->contains(function ($cal) {
                return (($cal->nota1 + $cal->nota2 + $cal->nota3) / 3) < 60;
            });

            if (!$esReprobado) {
                return response()->json([
                    'error' => 'El postulante "' . $registro . '" aprobó el proceso y no necesita re-postularse.',
                ], 422);
            }
        } else {
            // No tiene resultados de examen completos — no puede re-postularse
            return response()->json([
                'error' => 'El postulante "' . $registro . '" no figura como reprobado en el sistema (aún no tiene resultados de examen o no completó todas las materias).',
            ], 422);
        }

        return response()->json($postulante);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ci'                        => 'required|string|max:20',
            'nombre'                    => 'required|string|max:50',
            'apellido'                  => 'required|string|max:50',
            'fecha_nacimiento'          => 'required|date',
            'sexo'                      => 'required|in:M,F',
            'direccion'                 => 'required|string|max:100',
            'telefono'                  => 'nullable|digits_between:7,10',
            'correo_electronico'        => 'required|email|max:100',
            'ciudad'                    => 'required|string|max:50',
            'colegio_procedencia'       => 'required|string|max:100',
            'titulo_bachiller'          => 'required|string|max:50',
            'carrera_primera_opcion_id' => 'required|integer|exists:carrera,codigo',
            'carrera_segunda_opcion_id' => 'required|integer|exists:carrera,codigo',
            'es_repostulacion'          => 'nullable|boolean',
            'id_persona_anterior'       => 'nullable|integer',
        ]);

        $esRepostulacion   = !empty($validated['es_repostulacion']);
        $idPersonaAnterior = $validated['id_persona_anterior'] ?? null;

        // --- Validaciones de unicidad y opciones ---
        $observaciones = [];

        if (!$esRepostulacion) {
            if (DB::table('persona')->where('ci', $validated['ci'])->exists()) {
                $observaciones[] = 'El CI "' . $validated['ci'] . '" ya se encuentra registrado en el sistema.';
            }
            if (DB::table('postulante')->where('titulo_bachiller', $validated['titulo_bachiller'])->exists()) {
                $observaciones[] = 'El Nro. de Título Bachiller "' . $validated['titulo_bachiller'] . '" ya se encuentra registrado en el sistema.';
            }
        }

        if ($validated['carrera_primera_opcion_id'] == $validated['carrera_segunda_opcion_id']) {
            $observaciones[] = 'La primera y segunda opción de carrera no pueden ser la misma carrera.';
        }

        $rechazado        = count($observaciones) > 0;
        $estadoAsignacion = $rechazado ? 'Rechazado' : 'Pendiente';
        $registro         = null;

        DB::transaction(function () use ($validated, $estadoAsignacion, $observaciones, $rechazado, &$registro, $esRepostulacion, $idPersonaAnterior) {
            if ($esRepostulacion && $idPersonaAnterior) {
                // Reutilizar persona existente y actualizar datos de contacto
                DB::table('persona')->where('id', $idPersonaAnterior)->update([
                    'direccion'          => $validated['direccion'],
                    'telefono'           => $validated['telefono'] ?? null,
                    'correo_electronico' => $validated['correo_electronico'],
                    'ciudad'             => $validated['ciudad'],
                    'updated_at'         => now(),
                ]);
                $personaId = $idPersonaAnterior;
            } else {
                $personaId = DB::table('persona')->insertGetId([
                    'ci'                  => $validated['ci'],
                    'nombre'              => $validated['nombre'],
                    'apellido'            => $validated['apellido'],
                    'fecha_nacimiento'    => $validated['fecha_nacimiento'],
                    'sexo'                => $validated['sexo'],
                    'direccion'           => $validated['direccion'],
                    'telefono'            => $validated['telefono'] ?? null,
                    'correo_electronico'  => $validated['correo_electronico'],
                    'ciudad'              => $validated['ciudad'],
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $inscripcionId = DB::table('inscripcion')->insertGetId([
                'fecha_inscripcion'        => now()->toDateString(),
                'estado_pago'              => 'Pendiente',
                'codigo_gestion_academica' => DB::table('gestion_academica')->orderBy('codigo')->value('codigo') ?: 1,
                'codigo_pago'              => null,
                'codigo_pasarelaPago'      => null,
            ]);

            $postulanteId = DB::table('postulante')->insertGetId([
                'id_persona'                => $personaId,
                'registro'                  => 'P000',
                'colegio_procedencia'       => $validated['colegio_procedencia'],
                'ciudad'                    => $validated['ciudad'],
                'titulo_bachiller'          => $validated['titulo_bachiller'],
                'otros_requisitos'          => 'Ninguno',
                'codigo_inscripcion'        => $inscripcionId,
                'codigo_grupo'              => null,
                'carrera_primera_opcion_id' => $validated['carrera_primera_opcion_id'],
                'carrera_segunda_opcion_id' => $validated['carrera_segunda_opcion_id'],
                'carrera_asignada_id'       => null,
                'estado_asignacion'         => $estadoAsignacion,
                'observaciones_rechazo'     => $rechazado ? implode(' | ', $observaciones) : null,
            ]);

            $registro = 'P' . str_pad($postulanteId, 3, '0', STR_PAD_LEFT);
            DB::table('postulante')->where('id', $postulanteId)->update(['registro' => $registro]);

            BitacoraService::registrar(
                'Registro de postulante ' . $registro . ($rechazado ? ' (RECHAZADO)' : ''),
                request()->ip(),
                $personaId
            );
        });

        // Enviar correo si fue rechazado
        if ($rechazado) {
            try {
                $nombre   = $validated['nombre'] . ' ' . $validated['apellido'];
                $listaObs = implode("\n", array_map(fn ($o) => "  • {$o}", $observaciones));

                Mail::raw(
                    "Estimado/a {$nombre},\n\n" .
                    "Le informamos que su postulación al proceso de admisión ha sido registrada " .
                    "con el estado RECHAZADO debido a las siguientes observaciones:\n\n" .
                    "{$listaObs}\n\n" .
                    "Le solicitamos respetuosamente que se apersone a la Unidad de Admisión de " .
                    "la Facultad para regularizar su situación y encontrar una solución al problema.\n\n" .
                    "Datos de su postulación:\n" .
                    "  • Fecha: " . now()->format('d/m/Y') . "\n\n" .
                    "Atentamente,\n" .
                    "Sistema de Gestión Académica — CUP",
                    function ($message) use ($validated, $nombre) {
                        $message->to($validated['correo_electronico'], $nombre)
                                ->subject('Observaciones en su postulación — Gestión Académica CUP');
                    }
                );
            } catch (\Exception $e) {
                \Log::error('Error enviando correo de rechazo a ' . $validated['correo_electronico'] . ': ' . $e->getMessage());
            }

            return response()->json([
                'message'      => 'Postulante registrado con estado Rechazado. Se ha enviado un correo con las observaciones.',
                'registro'     => $registro,
                'rechazado'    => true,
                'observaciones' => $observaciones,
            ], 201);
        }

        return response()->json([
            'message'   => 'Postulante registrado correctamente.',
            'registro'  => $registro,
            'rechazado' => false,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $postulante = DB::table('postulante')->where('id', $id)->first();

        if (!$postulante) {
            return response()->json(['message' => 'Postulante no encontrado.'], 404);
        }

        $validated = $request->validate([
            'direccion'                 => 'required|string|max:100',
            'telefono'                  => 'nullable|digits_between:7,10',
            'correo_electronico'        => 'required|email|max:100',
            'ciudad'                    => 'required|string|max:50',
            'colegio_procedencia'       => 'required|string|max:100',
            'titulo_bachiller'          => 'required|string|max:50',
            'carrera_primera_opcion_id' => 'required|integer|exists:carrera,codigo',
            'carrera_segunda_opcion_id' => 'nullable|integer|exists:carrera,codigo',
        ]);

        DB::transaction(function () use ($validated, $postulante) {
            DB::table('persona')
                ->where('id', $postulante->id_persona)
                ->update([
                    'direccion'           => $validated['direccion'],
                    'telefono'            => $validated['telefono'] ?? null,
                    'correo_electronico'  => $validated['correo_electronico'],
                    'ciudad'              => $validated['ciudad'],
                    'updated_at'          => now(),
                ]);

            DB::table('postulante')
                ->where('id', $postulante->id)
                ->update([
                    'colegio_procedencia'       => $validated['colegio_procedencia'],
                    'titulo_bachiller'          => $validated['titulo_bachiller'],
                    'carrera_primera_opcion_id' => $validated['carrera_primera_opcion_id'],
                    'carrera_segunda_opcion_id' => $validated['carrera_segunda_opcion_id'] ?? null,
                ]);

            BitacoraService::registrar(
                'Actualización de postulante ' . $postulante->registro,
                request()->ip(),
                $postulante->id_persona
            );
        });

        return response()->json(['message' => 'Postulante actualizado correctamente.']);
    }

    public function destroy($id)
    {
        $postulante = DB::table('postulante')->where('id', $id)->first();

        if (!$postulante) {
            return response()->json(['message' => 'Postulante no encontrado.'], 404);
        }

        DB::table('postulante')
            ->where('id', $id)
            ->update(['estado_asignacion' => 'Eliminado']);

        BitacoraService::registrar(
            'Eliminación de postulante ' . $postulante->registro,
            request()->ip(),
            $postulante->id_persona
        );

        return response()->json(['message' => 'Postulante marcado como eliminado.']);
    }
}
