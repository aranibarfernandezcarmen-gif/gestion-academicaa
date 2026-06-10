<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Pago;
use App\Services\BitacoraService;
use Inertia\Inertia;
use Inertia\Response;

class CU04PagosController extends Controller
{
    public function index(): Response
    {
        // Obtener postulantes con estado_pago = 'Pendiente'
        $postulantes = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->leftJoin('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
            ->select('postulante.id', 'persona.nombre', 'persona.apellido', 'postulante.registro')
            ->whereRaw("COALESCE(inscripcion.estado_pago, 'Pendiente') = 'Pendiente'")
            ->where('postulante.estado_asignacion', '!=', 'Eliminado')
            ->orderBy('persona.nombre')
            ->get();

        return Inertia::render('CU04PagosYValidacion', [
            'postulantes' => $postulantes,
            'registro' => request()->query('registro'),
            'role' => request()->query('role'),
        ]);
    }

    /**
     * Obtener todos los pagos con información del postulante
     */
    /**
     * Query base de postulantes con su estado de pago (inscripción), con filtros.
     * La lista y el reporte se basan en POSTULANTES (CU03), no solo en la tabla 'pago'.
     */
    private function postulantesPagosQuery(Request $request)
    {
        $query = DB::table('postulante')
            ->join('persona', 'postulante.id_persona', '=', 'persona.id')
            ->leftJoin('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
            ->where('postulante.estado_asignacion', '!=', 'Eliminado');

        if ($request->filled('estado')) {
            switch ($request->estado) {
                case 'Completado':
                    $query->where('inscripcion.estado_pago', 'Pagado');
                    break;
                case 'Rechazado':
                    $query->where('inscripcion.estado_pago', 'Rechazado');
                    break;
                case 'Pendiente':
                    $query->where(function ($q) {
                        $q->whereNull('inscripcion.estado_pago')
                          ->orWhereNotIn('inscripcion.estado_pago', ['Pagado', 'Rechazado']);
                    });
                    break;
                default:
                    // Procesando/Cancelado no aplican al modelo de inscripción
                    $query->whereRaw('1 = 0');
            }
        }
        if ($request->filled('id_postulante')) {
            $query->where('postulante.id', $request->id_postulante);
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('inscripcion.fecha_inscripcion', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('inscripcion.fecha_inscripcion', '<=', $request->fecha_fin);
        }

        return $query;
    }

    public function getPagos(Request $request)
    {
        // Lista basada en POSTULANTES (estado de pago de su inscripción = CU03),
        // no solo en la tabla 'pago'. Así aparecen los pagados y pendientes de CU03.
        $rows = $this->postulantesPagosQuery($request)
            ->select(
                'postulante.id',
                'postulante.registro',
                'persona.nombre',
                'persona.apellido',
                'persona.ci',
                'inscripcion.estado_pago',
                'inscripcion.fecha_inscripcion'
            )
            ->orderBy('postulante.id', 'desc')
            ->get();

        // Pago real (si existe) por postulante, para mostrar monto/fecha/hora/método
        $pagosByPostulante = DB::table('pago')
            ->whereNotNull('id_postulante')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('id_postulante');

        $pagos = $rows->map(function ($r) use ($pagosByPostulante) {
            $estadoPago = $r->estado_pago ?: 'Pendiente';
            $estado = $estadoPago === 'Pagado' ? 'Completado'
                    : ($estadoPago === 'Rechazado' ? 'Rechazado' : 'Pendiente');
            $pago = optional($pagosByPostulante->get($r->id))->first();
            return [
                'id'          => $r->id,            // id del postulante (para las acciones)
                'registro'    => $r->registro,
                'nombre'      => $r->nombre,
                'apellido'    => $r->apellido,
                'ci'          => $r->ci,
                'monto'       => $pago->monto ?? 150,
                'fecha_pago'  => $pago->fecha_pago ?? $r->fecha_inscripcion,
                'hora_pago'   => $pago->hora_pago ?? null,
                'estado'      => $estado,
                'metodo_pago' => $pago->metodo_pago ?? ($estado === 'Completado' ? 'Efectivo' : 'Físico'),
            ];
        })->values();

        // Estadísticas (basadas en inscripción, sin filtros)
        $base = DB::table('postulante')
            ->leftJoin('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
            ->where('postulante.estado_asignacion', '!=', 'Eliminado');
        $totalPostulantes = (clone $base)->count();
        $pagados    = (clone $base)->where('inscripcion.estado_pago', 'Pagado')->count();
        $rechazados = (clone $base)->where('inscripcion.estado_pago', 'Rechazado')->count();
        $pendientes = max(0, $totalPostulantes - $pagados - $rechazados);

        return response()->json([
            'pagos' => $pagos,
            'estadisticas' => [
                'total_pagos' => $totalPostulantes,
                'monto_total' => $pagados * 150,
                'pagos_completados' => $pagados,
                'pagos_pendientes' => $pendientes,
                'pagos_rechazados' => $rechazados,
            ]
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_postulante' => 'nullable|integer|exists:postulante,id',
                'monto' => 'required|numeric|min:0.01',
                'fecha_pago' => 'required|date',
                'hora_pago' => 'nullable|string',
                'comprobante' => 'nullable|string|max:50',
                'metodo_pago' => 'required|in:Efectivo',
                'numero_transaccion' => 'nullable|string|max:100',
                'descripcion' => 'nullable|string',
                'correo_electronico' => 'nullable|email|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validación fallida en CU04/store:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        try {
            // Generar comprobante si viene vacío
            if (empty($validated['comprobante'])) {
                $validated['comprobante'] = 'COMP-' . strtoupper(Str::random(8));
            }

            // Generar número de transacción único si viene vacío
            if (empty($validated['numero_transaccion'])) {
                do {
                    $validated['numero_transaccion'] = 'TRX-' . strtoupper(Str::random(10));
                } while (DB::table('pago')->where('referencia_transaccion', $validated['numero_transaccion'])->exists());
            }

            // Generar password temporal para el postulante
            $password = Str::random(10);

            $postulanteId = null;
            $registro = null;
            $credencialResponse = null;

            // Si hay email pero no id_postulante, crear usuario y postulante
            if (!empty($validated['correo_electronico']) && empty($validated['id_postulante'])) {
                DB::transaction(function () use ($validated, &$postulanteId, &$password, &$registro, &$credencialResponse) {
                    // Verificar si la persona ya existe por email
                    $persona = DB::table('persona')->where('correo_electronico', $validated['correo_electronico'])->first();
                    
                    if (!$persona) {
                        // Crear persona
                        $personaId = DB::table('persona')->insertGetId([
                            'ci' => 'SIN_CI_' . Str::random(6),
                            'nombre' => 'Usuario',
                            'apellido' => 'CU04',
                            'correo_electronico' => $validated['correo_electronico'],
                            'sexo' => 'M',
                            'direccion' => 'N/A',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $personaId = $persona->id;
                    }

                    // Generar credenciales si es nuevo postulante
                    $existingPostulante = DB::table('postulante')->where('id_persona', $personaId)->first();
                    
                    if (!$existingPostulante) {
                        $password = Str::random(10);
                        
                        // Crear inscripción primero (estado Pendiente)
                        $inscripcionId = DB::table('inscripcion')->insertGetId([
                            'fecha_inscripcion' => now()->toDateString(),
                            'estado_pago' => 'Pendiente',
                            'codigo_gestion_academica' => 1,
                        ]);
                        
                        // Crear postulante con referencia a inscripción
                        $tempPostulanteId = DB::table('postulante')->insertGetId([
                            'id_persona' => $personaId,
                            'registro' => 'P000',
                            'colegio_procedencia' => 'N/A',
                            'ciudad' => 'N/A',
                            'titulo_bachiller' => 'N/A',
                            'otros_requisitos' => 'Pagado en CU04',
                            'codigo_inscripcion' => $inscripcionId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Generar código
                        $registro = 'P' . str_pad($tempPostulanteId, 3, '0', STR_PAD_LEFT);
                        
                        DB::table('postulante')->where('id', $tempPostulanteId)->update(['registro' => $registro]);
                        
                        $postulanteId = $tempPostulanteId;
                        
                        // Preparar respuesta con credenciales
                        $credencialResponse = [
                            'registro' => $registro,
                            'password' => $password,
                            'email' => $validated['correo_electronico'],
                            'url_acceso' => 'http://127.0.0.1:8000/postularse/ingresar'
                        ];
                        
                        // Enviar credenciales por email
                        $this->sendCredentialsEmail($validated['correo_electronico'], 'Usuario CU04', $registro, $password);
                    } else {
                        $postulanteId = $existingPostulante->id;
                    }
                });
            } else {
                $postulanteId = $validated['id_postulante'] ?? null;
            }

            // Registrar el pago
            if ($postulanteId) {
                DB::transaction(function () use ($validated, $postulanteId, $password) {
                    // Crear pago
                    $pago = Pago::create([
                        'id_postulante' => $postulanteId,
                        'monto' => $validated['monto'],
                        'fecha_pago' => $validated['fecha_pago'],
                        'hora_pago' => $validated['hora_pago'] ?? \Carbon\Carbon::now('America/La_Paz')->format('H:i:s'),
                        'comprobante' => $validated['comprobante'],
                        'metodo_pago' => $validated['metodo_pago'],
                        'referencia_transaccion' => $validated['numero_transaccion'] ?? null,
                        'descripcion' => $validated['descripcion'] ?? null,
                        'estado' => 'Completado',
                    ]);

                    // Obtener datos del postulante para enviar email
                    $postulante = DB::table('postulante')
                        ->join('persona', 'postulante.id_persona', '=', 'persona.id')
                        ->select('persona.nombre', 'persona.apellido', 'persona.correo_electronico', 'persona.ci', 'postulante.registro', 'postulante.codigo_inscripcion')
                        ->where('postulante.id', $postulanteId)
                        ->first();

                    // Enviar email de confirmación de pago
                    if ($postulante && $postulante->correo_electronico) {
                        $nombreCompleto = trim($postulante->nombre . ' ' . $postulante->apellido);
                        // Usar CI como contraseña temporal
                        $this->sendPaymentConfirmation(
                            $postulante->correo_electronico,
                            $nombreCompleto,
                            $validated['monto'],
                            $validated['comprobante'],
                            $postulante->registro,
                            $postulante->ci
                        );
                    }

                    // Actualizar estado de la inscripción a Pagado
                    if ($postulante && $postulante->codigo_inscripcion) {
                        DB::table('inscripcion')
                            ->where('id', $postulante->codigo_inscripcion)
                            ->update(['estado_pago' => 'Pagado']);
                    } else if ($postulante && !$postulante->codigo_inscripcion) {
                        // Fallback: si no tiene codigo_inscripcion, crear inscripción con estado Pagado
                        $inscripcionId = DB::table('inscripcion')->insertGetId([
                            'fecha_inscripcion' => now()->toDateString(),
                            'estado_pago' => 'Pagado',
                            'codigo_gestion_academica' => 1,
                        ]);
                        DB::table('postulante')->where('id', $postulanteId)->update([
                            'codigo_inscripcion' => $inscripcionId
                        ]);
                    }

                    BitacoraService::registrar(
                        "Pago registrado en CU04 - Monto: {$validated['monto']}, Postulante: {$postulanteId}",
                        request()->ip(),
                        Auth::id()
                    );
                });

                return response()->json([
                    'message' => 'Pago registrado exitosamente. Credenciales enviadas al correo del postulante.',
                    'credenciales' => $credencialResponse
                ], 201);
            } else {
                return response()->json(['error' => 'No se pudo procesar el pago'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Enviar credenciales por email
     */
    private function sendCredentialsEmail($email, $nombre, $codigo, $password)
    {
        try {
            $mensaje = "
            <h2>Bienvenido a CUP</h2>
            <p>Hola $nombre,</p>
            <p>Tu inscripción ha sido procesada correctamente. Aquí están tus credenciales:</p>
            <p><strong>Código de Registro:</strong> $codigo</p>
            <p><strong>Contraseña Temporal:</strong> $password</p>
            <p><strong>URL de Acceso:</strong> <a href='" . url('/postularse/ingresar') . "'>Ingresar</a></p>
            <p>Por favor, cambia tu contraseña después de tu primer acceso.</p>
            ";

            \Illuminate\Support\Facades\Mail::html($mensaje, function ($message) use ($email) {
                $message->to($email)->subject('Credenciales de Acceso - CUP');
            });

            // Registrar en bitácora que se enviaron credenciales
            \Illuminate\Support\Facades\Log::info("Credenciales enviadas", [
                'email' => $email,
                'nombre' => $nombre,
                'codigo' => $codigo,
                'password' => $password
            ]);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando email de credenciales', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Enviar confirmación de pago
     */
    private function sendPaymentConfirmation($email, $nombre, $monto, $comprobante, $registro = null, $password = null)
    {
        try {
            $mensaje = "
            <h2>Confirmación de Pago - CUP</h2>
            <p>Hola $nombre,</p>
            <p>Tu pago ha sido registrado exitosamente.</p>
            <p><strong>Monto Pagado:</strong> Bs $monto</p>
            <p><strong>Comprobante:</strong> $comprobante</p>
            <p><strong>Estado:</strong> Completado</p>";
            
            if ($registro && $password) {
                $mensaje .= "
                <p><strong>Tus Credenciales de Acceso:</strong></p>
                <p><strong>Código de Registro:</strong> $registro</p>
                <p><strong>Contraseña Temporal:</strong> $password</p>";
            }
            
            $mensaje .= "
            <p>Por favor, cambia tu contraseña después del primer acceso.</p>
            <p><strong>URL de Acceso:</strong> <a href='" . url('/postularse/ingresar') . "'>Ingresar</a></p>
            ";

            \Illuminate\Support\Facades\Mail::html($mensaje, function ($message) use ($email) {
                $message->to($email)->subject('Confirmación de Pago - CUP');
            });

            // Registrar en bitácora que se envió confirmación
            \Illuminate\Support\Facades\Log::info("Confirmación de pago enviada", [
                'email' => $email,
                'nombre' => $nombre,
                'monto' => $monto,
                'comprobante' => $comprobante
            ]);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando confirmación de pago', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Actualizar estado de pago
     */
    public function updateEstado(Request $request, $id)
    {
        $validated = $request->validate([
            'estado' => 'required|in:Pendiente,Procesando,Completado,Rechazado,Cancelado',
            'comentario' => 'nullable|string',
        ]);

        try {
            $pago = Pago::findOrFail($id);
            $estadoAnterior = $pago->estado;

            $pago->update(['estado' => $validated['estado']]);

            // Registrar en bitácora
            BitacoraService::registrar(
                "Estado de pago actualizado: {$estadoAnterior} → {$validated['estado']}. ID Pago: {$id}",
                request()->ip(),
                Auth::id()
            );

            return response()->json(['message' => 'Estado de pago actualizado']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Validar pago (simular validación con pasarela)
     */
    public function validarPago(Request $request, $id)
    {
        // $id ahora es el ID del POSTULANTE (la lista es por postulantes).
        $validated = $request->validate([
            'validar' => 'required|boolean',
        ]);

        try {
            $postulante = DB::table('postulante')->where('id', $id)->first();
            if (!$postulante) {
                return response()->json(['error' => 'Postulante no encontrado'], 404);
            }

            $estadoInscripcion = $validated['validar'] ? 'Pagado' : 'Rechazado';

            DB::transaction(function () use ($postulante, $id, $validated, $estadoInscripcion) {
                // Actualizar el estado de pago de la inscripción (fuente de los contadores)
                if ($postulante->codigo_inscripcion) {
                    DB::table('inscripcion')
                        ->where('id', $postulante->codigo_inscripcion)
                        ->update(['estado_pago' => $estadoInscripcion]);
                } else {
                    $inscripcionId = DB::table('inscripcion')->insertGetId([
                        'fecha_inscripcion' => now()->toDateString(),
                        'estado_pago' => $estadoInscripcion,
                        'codigo_gestion_academica' => 1,
                    ]);
                    DB::table('postulante')->where('id', $id)->update(['codigo_inscripcion' => $inscripcionId]);
                }

                // Reflejar también en la tabla pago (si el postulante tiene registros)
                DB::table('pago')->where('id_postulante', $id)
                    ->update(['estado' => $validated['validar'] ? 'Completado' : 'Rechazado']);

                BitacoraService::registrar(
                    "Pago " . ($validated['validar'] ? 'validado' : 'rechazado') . " (postulante {$id})",
                    request()->ip(),
                    request()->session()->get('persona_id')
                );
            });

            return response()->json(['message' => $validated['validar'] ? 'Pago validado' : 'Pago rechazado']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Descargar reporte de pagos
     */
    public function descargarReporte(Request $request)
    {
        // Reporte basado en POSTULANTES (incluye pendientes y pagados de CU03),
        // respetando los mismos filtros de la lista.
        $rows = $this->postulantesPagosQuery($request)
            ->select(
                'postulante.id',
                'postulante.registro',
                'persona.nombre',
                'persona.apellido',
                'persona.ci',
                'inscripcion.estado_pago',
                'inscripcion.fecha_inscripcion'
            )
            ->orderBy('postulante.id', 'desc')
            ->get();

        $csv = "Registro,Postulante,CI,Monto,Fecha,Estado\n";
        foreach ($rows as $r) {
            $estadoPago = $r->estado_pago ?: 'Pendiente';
            $estado = $estadoPago === 'Pagado' ? 'Completado'
                    : ($estadoPago === 'Rechazado' ? 'Rechazado' : 'Pendiente');
            $nombre = trim(($r->nombre ?? '') . ' ' . ($r->apellido ?? ''));
            $monto = $estado === 'Completado' ? '150' : '0';
            $fecha = $r->fecha_inscripcion ?? '';
            $csv .= "{$r->registro},{$nombre},{$r->ci},{$monto},{$fecha},{$estado}\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="reporte_pagos.csv"');
    }

    /**
     * Obtener correo del postulante
     */
    public function getCorreoPostulante($id)
    {
        try {
            $postulante = DB::table('postulante')
                ->join('persona', 'postulante.id_persona', '=', 'persona.id')
                ->where('postulante.id', $id)
                ->select('persona.correo_electronico')
                ->first();

            if ($postulante) {
                return response()->json(['correo_electronico' => $postulante->correo_electronico]);
            }
            return response()->json(['correo_electronico' => null]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
