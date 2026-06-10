<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Services\BitacoraService;
use App\Services\SessionManager;
use Inertia\Inertia;
use Inertia\Response;

class PostulacionController extends Controller
{
    public function index(): Response
    {
        $carreras = DB::table('carrera')
            ->select('codigo', 'nombre_carrera')
            ->orderBy('nombre_carrera')
            ->get();

        return Inertia::render('Postularse', [
            'carreras' => $carreras,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'apellido' => 'required|string|max:50',
            'ci' => 'required|string|max:20',
            'fecha_nacimiento' => 'required|date',
            'sexo' => 'required|string|max:10',
            'direccion' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'correo_electronico' => 'required|email|max:100',
            'ciudad' => 'required|string|max:50',
            'colegio_procedencia' => 'required|string|max:100',
            'titulo_bachiller' => 'required|string|max:50',
            'carrera_primera_opcion_id' => 'required|integer|exists:carrera,codigo',
            'carrera_segunda_opcion_id' => 'nullable|integer|exists:carrera,codigo',
            'payment_method' => 'required|in:virtual,physical',
        ]);

        $registro = null;

        DB::transaction(function () use ($validated, &$registro) {
            $personaId = DB::table('persona')->insertGetId([
                'ci' => $validated['ci'],
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'sexo' => $validated['sexo'],
                'direccion' => $validated['direccion'],
                'telefono' => $validated['telefono'] ?? null,
                'correo_electronico' => $validated['correo_electronico'],
                'ciudad' => $validated['ciudad'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $paymentId = DB::table('pago')->insertGetId([
                'monto' => 150.00,
                'fecha_pago' => now()->toDateString(),
                'comprobante' => 'REC-TEMP',
            ]);

            $comprobantePago = 'REC-' . str_pad($paymentId, 4, '0', STR_PAD_LEFT);
            DB::table('pago')
                ->where('id', $paymentId)
                ->update(['comprobante' => $comprobantePago]);

            $estadoPago = $validated['payment_method'] === 'virtual' ? 'Pagado' : 'Pendiente';
            $codigoPasarela = null;

            if ($validated['payment_method'] === 'virtual') {
                $codigoPasarela = DB::table('pasarela_pago')->insertGetId([
                    'monto' => 150.00,
                    'fecha_pago' => now()->toDateString(),
                    'comprobante' => 'YAPE-QR-' . str_pad($paymentId, 4, '0', STR_PAD_LEFT),
                    'codigo_pago' => $paymentId,
                ]);
            }

            $gestionAcademica = DB::table('gestion_academica')
                ->orderBy('codigo')
                ->value('codigo') ?: 1;

            $inscripcionId = DB::table('inscripcion')->insertGetId([
                'fecha_inscripcion' => now()->toDateString(),
                'estado_pago' => $estadoPago,
                'codigo_gestion_academica' => $gestionAcademica,
                'codigo_pago' => $paymentId,
                'codigo_pasarelaPago' => $codigoPasarela,
            ]);

            $postulanteId = DB::table('postulante')->insertGetId([
                'id_persona' => $personaId,
                'registro' => 'P000',
                'colegio_procedencia' => $validated['colegio_procedencia'],
                'ciudad' => $validated['ciudad'],
                'titulo_bachiller' => $validated['titulo_bachiller'],
                'otros_requisitos' => 'Ninguno',
                'codigo_inscripcion' => $inscripcionId,
                'codigo_grupo' => 1,
                'carrera_primera_opcion_id' => $validated['carrera_primera_opcion_id'],
                'carrera_segunda_opcion_id' => $validated['carrera_segunda_opcion_id'] ?: null,
                'carrera_asignada_id' => null,
                'estado_asignacion' => 'Pendiente',
            ]);

            $registro = 'P' . str_pad($postulanteId, 3, '0', STR_PAD_LEFT);
            DB::table('postulante')
                ->where('id', $postulanteId)
                ->update(['registro' => $registro]);

            BitacoraService::registrar(
                "Creación de postulante {$registro} - {$validated['nombre']} {$validated['apellido']}",
                request()->ip(),
                $personaId
            );
        });

        return Redirect::route('postulacion.success')->with([
            'success' => 'FELICIDADES YA ESTAS INSCRITO EN LOS CURSOS PREUNIVERSITARIOS PARA LA FICCT. TU NUMERO DE REGISTRO ES ' . $registro . ', YA PUEDES INGRESAR USANDO TU NUMERO DE REGISTRO Y TU CI COMO CONTRASEÑA.',
            'registro' => $registro,
            'ci' => $validated['ci'],
        ]);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('PostulacionSuccess', [
            'message' => $request->session()->get('success'),
            // Leer también de la query string: el flujo de pago físico navega con
            // window.location.href (carga completa) y pierde el flash de sesión.
            'registro' => $request->query('registro') ?: $request->session()->get('registro'),
            'ci' => $request->query('ci') ?: $request->session()->get('ci'),
        ]);
    }

    public function showLogin(Request $request): Response | \Illuminate\Http\RedirectResponse
    {
        // ✅ REQUERIMIENTO: Si ya hay sesión en este navegador, no pedir credenciales
        if ($request->session()->has('persona_id') && $request->session()->has('role')) {
            return Redirect::route('postulacion.dashboard', [
                'registro' => $request->session()->get('registro'),
                'role' => $request->session()->get('role'),
            ]);
        }

        return Inertia::render('PostulacionLogin', [
            'roles' => [
                'postulante' => 'Postulante',
                'docente' => 'Docente',
                'administrativo' => 'Administrativo',
                'coordinador' => 'Coordinador',
                'decano' => 'Decano',
            ],
        ]);
    }

    public function login(Request $request)
    {
        $validationRules = [
            'ci' => 'required|string|max:20',
            'registro' => 'required|string|max:20',
            'password' => 'required|string|max:50',
            'role' => 'required|in:postulante,docente,administrativo,coordinador,decano',
        ];

        $validated = $request->validate($validationRules);

        // **NEW LOGIC**: Buscar por REGISTRO en la tabla del rol, no por CI
        // For Decano, if no registro provided, search by CI instead
        $persona = null;
        $role = $validated['role'];
        $ciFromRequest = $validated['ci'];

        if ($role === 'postulante') {
            $registro = $validated['registro'];
            $postulante = DB::table('postulante')->where('registro', $registro)->first();
            if ($postulante) {
                $persona = DB::table('persona')->where('id', $postulante->id_persona)->first();
            }
        } elseif ($role === 'docente') {
            $registro = $validated['registro'];
            $docente = DB::table('docente')->where('codigo', $registro)->first();
            if ($docente) {
                $persona = DB::table('persona')->where('id', $docente->id_persona)->first();
            }
        } elseif ($role === 'administrativo') {
            $registro = $validated['registro'];
            $administrativo = DB::table('administrativo')->where('codigo', $registro)->first();
            if ($administrativo) {
                $persona = DB::table('persona')->where('id', $administrativo->id_persona)->first();
            }
        } elseif ($role === 'coordinador') {
            $registro = $validated['registro'];
            $coordinador = DB::table('coordinador')->where('codigo', $registro)->first();
            if ($coordinador) {
                $persona = DB::table('persona')->where('id', $coordinador->id_persona)->first();
            }
        } elseif ($role === 'decano') {
            // For Decano: first try by registro if provided, then by CI
            $registro = $validated['registro'] ?? null;
            if ($registro) {
                $decano = DB::table('decano')->where('codigo', $registro)->first();
                if ($decano) {
                    $persona = DB::table('persona')->where('id', $decano->id_persona)->first();
                }
            } else {
                // Search by CI
                $persona = DB::table('persona')->where('ci', $ciFromRequest)->first();
                if ($persona) {
                    // Get the decano record to get the codigo for session
                    $decano = DB::table('decano')->where('id_persona', $persona->id)->first();
                    $registro = $decano ? $decano->codigo : 'DEC-' . $ciFromRequest;
                }
            }
        }

        if (!$persona) {
            return Redirect::back()->withErrors(['ci' => 'No se encontró ninguna cuenta con esa identificación para el rol seleccionado.'])->withInput();
        }

        // Validar que el CI coincida
        if ($persona->ci !== $ciFromRequest) {
            return Redirect::back()->withErrors(['ci' => 'El CI no coincide con el registro.'])->withInput();
        }

        // Validar contraseña:
        // Si tiene temporary_password (restablecida), validar contra eso
        // Si no, validar que sea igual al CI (contraseña inicial)
        if ($persona->temporary_password) {
            if ($validated['password'] !== $persona->temporary_password) {
                return Redirect::back()->withErrors(['password' => 'La contraseña es incorrecta.'])->withInput();
            }
        } else {
            if ($validated['password'] !== $ciFromRequest) {
                return Redirect::back()->withErrors(['password' => 'La contraseña es incorrecta. Usa tu CI como contraseña inicial.'])->withInput();
            }
        }

        $request->session()->regenerate();
        $request->session()->put('persona_id', $persona->id);
        $request->session()->put('role', $role);
        $request->session()->put('registro', $registro);

        SessionManager::createUserSession(
            $persona->id,
            $request,
            $request->session()->getId()
        );

        BitacoraService::registrar(
            "Inicio de sesión como {$role} ({$registro})",
            $request->ip(),
            $persona->id
        );

        return Redirect::route('postulacion.dashboard', [
            'registro' => $registro,
            'role' => $role,
        ]);
    }

    public function dashboard(Request $request): Response | \Illuminate\Http\RedirectResponse
    {
        $registro = $request->query('registro');
        $role = $request->query('role');

        // 🔍 Logging para depuración - Postulante Dashboard
        \Illuminate\Support\Facades\Log::info('[PostulacionDashboard] Iniciando', [
            'registro' => $registro,
            'role' => $role,
            'session_persona_id' => $request->session()->get('persona_id'),
            'session_role' => $request->session()->get('role'),
            'session_registro' => $request->session()->get('registro'),
        ]);

        // ✅ FALLBACK: Si no hay query params pero sí hay sesión válida, usar sesión
        if (!$registro && $request->session()->has('registro')) {
            $registro = $request->session()->get('registro');
            $role = $request->session()->get('role');
            
            \Illuminate\Support\Facades\Log::info('[PostulacionDashboard] Usando datos de sesión como fallback', [
                'registro' => $registro,
                'role' => $role,
            ]);
        }

        $data = null;
        $roleLabel = 'Visitante';

        if ($role === 'postulante') {
            $data = DB::table('postulante')
                ->where('registro', $registro)
                ->first();

            \Illuminate\Support\Facades\Log::info('[PostulacionDashboard] Buscando postulante', [
                'registro' => $registro,
                'data_found' => !!$data,
                'data_id_persona' => $data?->id_persona ?? null,
            ]);

            if ($data) {
                $data->persona = DB::table('persona')->where('id', $data->id_persona)->first();
                $roleLabel = 'Postulante';

                // Grupos asignados al postulante (desde tabla asignacion_grupo)
                $gruposAsignados = DB::table('asignacion_grupo')
                    ->join('grupo', 'asignacion_grupo.grupo_codigo', '=', 'grupo.codigo')
                    ->leftJoin('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
                    ->leftJoin('docente', 'grupo.codigo_docente', '=', 'docente.id')
                    ->leftJoin('persona as pd', 'docente.id_persona', '=', 'pd.id')
                    ->leftJoin('horario', 'grupo.codigo_horario', '=', 'horario.codigo')
                    ->where('asignacion_grupo.postulante_id', $data->id)
                    ->select(
                        'grupo.codigo',
                        'grupo.nombre_grupo',
                        'materia.nombre_materia',
                        'materia.sigla',
                        'pd.nombre as docente_nombre',
                        'pd.apellido as docente_apellido',
                        'horario.dia',
                        'horario.hora_inicio',
                        'horario.hora_fin'
                    )
                    ->get();

                $data->grupos = $gruposAsignados;
                $data->grupo  = $gruposAsignados->first() ?: null;

                // Calificaciones con info de materia
                // examen.id_materia puede ser null en datos existentes;
                // como fallback se obtiene la materia desde el grupo actual del postulante
                $data->calificaciones = DB::table('calificacion')
                    ->leftJoin('examen', 'calificacion.codigo_examen', '=', 'examen.codigo')
                    ->leftJoin('materia as mex', 'examen.id_materia', '=', 'mex.codigo')
                    ->leftJoin('postulante as p', 'calificacion.registro_postulante', '=', 'p.id')
                    ->leftJoin('grupo as g', 'p.codigo_grupo', '=', 'g.codigo')
                    ->leftJoin('materia as mg', 'g.codigo_materia', '=', 'mg.codigo')
                    ->where('calificacion.registro_postulante', $data->id)
                    ->select(
                        'calificacion.id',
                        'calificacion.nota1',
                        'calificacion.nota2',
                        'calificacion.nota3',
                        'calificacion.promedio',
                        'calificacion.estado',
                        DB::raw('COALESCE(mex.nombre_materia, mg.nombre_materia) as nombre_materia'),
                        DB::raw('COALESCE(mex.sigla, mg.sigla) as sigla')
                    )
                    ->get();

                // Inscripcion con pago y gestión académica
                if ($data->codigo_inscripcion) {
                    $data->inscripcion = DB::table('inscripcion')
                        ->leftJoin('pago', 'inscripcion.codigo_pago', '=', 'pago.id')
                        ->leftJoin('gestion_academica', 'inscripcion.codigo_gestion_academica', '=', 'gestion_academica.codigo')
                        ->where('inscripcion.id', $data->codigo_inscripcion)
                        ->select(
                            'inscripcion.id',
                            'inscripcion.fecha_inscripcion',
                            'inscripcion.estado_pago',
                            'pago.monto',
                            'pago.comprobante',
                            'gestion_academica.anio',
                            'gestion_academica.gestion'
                        )
                        ->first();
                } else {
                    $data->inscripcion = null;
                }

                // Nombres de carreras
                $data->carrera_primera = $data->carrera_primera_opcion_id
                    ? DB::table('carrera')->where('codigo', $data->carrera_primera_opcion_id)->value('nombre_carrera')
                    : null;
                $data->carrera_segunda = $data->carrera_segunda_opcion_id
                    ? DB::table('carrera')->where('codigo', $data->carrera_segunda_opcion_id)->value('nombre_carrera')
                    : null;
                $data->carrera_asignada = $data->carrera_asignada_id
                    ? DB::table('carrera')->where('codigo', $data->carrera_asignada_id)->value('nombre_carrera')
                    : null;
            }
        } elseif ($role === 'docente') {
            $data = DB::table('docente')->where('codigo', $registro)->first();
            if ($data) {
                $data->persona = DB::table('persona')->where('id', $data->id_persona)->first();

                // Grupos asignados a este docente con materia, horario y conteo de estudiantes
                $data->grupos = DB::table('grupo')
                    ->leftJoin('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
                    ->leftJoin('horario', 'grupo.codigo_horario', '=', 'horario.codigo')
                    ->where('grupo.codigo_docente', $data->id)
                    ->select(
                        'grupo.codigo',
                        'grupo.nombre_grupo',
                        'materia.nombre_materia',
                        'materia.sigla',
                        'horario.dia',
                        'horario.hora_inicio',
                        'horario.hora_fin',
                        DB::raw('(SELECT COUNT(*) FROM postulante WHERE postulante.codigo_grupo = grupo.codigo) as total_estudiantes')
                    )
                    ->get();

                // CU05: Estudiantes de sus grupos con al menos 1 nota faltante
                $data->postulantesPendientes = DB::table('asignacion_grupo')
                    ->join('postulante', 'asignacion_grupo.postulante_id', '=', 'postulante.id')
                    ->join('persona', 'postulante.id_persona', '=', 'persona.id')
                    ->join('grupo', 'asignacion_grupo.grupo_codigo', '=', 'grupo.codigo')
                    ->leftJoin('calificacion', function ($join) {
                        $join->on('calificacion.registro_postulante', '=', 'postulante.id')
                             ->on('calificacion.codigo_grupo', '=', 'asignacion_grupo.grupo_codigo');
                    })
                    ->where('grupo.codigo_docente', $data->id)
                    ->where('postulante.estado_asignacion', '<>', 'Eliminado')
                    ->where(function ($q) {
                        $q->whereNull('calificacion.id')
                          ->orWhereNull('calificacion.nota1')
                          ->orWhereNull('calificacion.nota2')
                          ->orWhereNull('calificacion.nota3');
                    })
                    ->select(
                        'postulante.id',
                        'postulante.registro',
                        'persona.ci',
                        'persona.nombre',
                        'persona.apellido',
                        'asignacion_grupo.grupo_codigo',
                        'calificacion.id as calificacion_id',
                        'calificacion.nota1',
                        'calificacion.nota2',
                        'calificacion.nota3'
                    )
                    ->orderBy('postulante.registro')
                    ->get();

                // CU05: Calificaciones completas de sus grupos
                $data->calificacionesRegistradas = DB::table('calificacion')
                    ->join('postulante', 'calificacion.registro_postulante', '=', 'postulante.id')
                    ->join('persona', 'postulante.id_persona', '=', 'persona.id')
                    ->join('grupo', 'calificacion.codigo_grupo', '=', 'grupo.codigo')
                    ->join('materia', 'grupo.codigo_materia', '=', 'materia.codigo')
                    ->where('grupo.codigo_docente', $data->id)
                    ->whereNotNull('calificacion.nota1')
                    ->whereNotNull('calificacion.nota2')
                    ->whereNotNull('calificacion.nota3')
                    ->select(
                        'calificacion.id',
                        'postulante.registro',
                        'persona.nombre',
                        'persona.apellido',
                        'grupo.nombre_grupo',
                        'materia.nombre_materia',
                        'calificacion.nota1',
                        'calificacion.nota2',
                        'calificacion.nota3',
                        'calificacion.promedio',
                        'calificacion.estado'
                    )
                    ->orderBy('postulante.registro')
                    ->get();
            }
            $roleLabel = 'Docente';
        } elseif ($role === 'administrativo') {
            $data = DB::table('administrativo')
                ->where('codigo', $registro)
                ->first();
            if ($data) {
                $data->persona = DB::table('persona')->where('id', $data->id_persona)->first();
            }
            $roleLabel = 'Administrativo';
        } elseif ($role === 'coordinador') {
            $data = DB::table('coordinador')
                ->where('codigo', $registro)
                ->first();
            if ($data) {
                $data->persona = DB::table('persona')->where('id', $data->id_persona)->first();
            }
            $roleLabel = 'Coordinador';
        } elseif ($role === 'decano') {
            $data = DB::table('decano')
                ->where('codigo', $registro)
                ->first();
            if ($data) {
                $data->persona = DB::table('persona')->where('id', $data->id_persona)->first();
            }
            $roleLabel = 'Decano';
        }

        if (!$data) {
            return Redirect::route('postulacion.login')->withErrors(['registro' => 'No se encontró la cuenta para el rol seleccionado.']);
        }

        $allowedCus = null;
        if (in_array($roleLabel, ['Docente', 'Administrativo', 'Coordinador', 'Postulante'], true)) {
            $grupo = DB::table('rol_grupo')->where('nombre_grupo', $roleLabel)->first();
            if ($grupo) {
                $allowedCus = DB::table('rol_grupo_privilegio')
                    ->where('codigo_rol_grupo', $grupo->codigo)
                    ->pluck('codigo_cu')
                    ->toArray();
            }
        }

        return Inertia::render('PostulacionDashboard', [
            'role' => $roleLabel,
            'registro' => $registro,
            'data' => $data,
            'allowedCus' => $allowedCus,
        ]);
    }

    public function logout(Request $request)
    {
        $personaId = $request->session()->get('persona_id');
        $sessionId = $request->session()->getId();

        if ($personaId) {
            // ✅ Cerrar sesión en tabla user_sessions
            SessionManager::closeSession($sessionId);

            BitacoraService::registrar(
                'Cierre de sesión',
                $request->ip(),
                $personaId
            );
        }

        $request->session()->flush();
        
        // Crear respuesta de redirección a página principal
        $response = Redirect::to('/');
        
        // Agregar headers AGRESIVOS anti-caché en la respuesta de logout
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '-1');
        $response->headers->set('Surrogate-Control', 'no-store');
        
        // Headers de seguridad
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Forzar re-validación
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('ETag', '"' . md5(time()) . '"');
        
        return $response;
    }

    public function validarSesionAjax(Request $request)
    {
        // Endpoint para validar sesión desde AJAX (tiempo real)
        // Retorna JSON con status 200 (válido) o 401 (inválido)
        
        // Agregar headers anti-caché AGRESIVOS
        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
            'Pragma' => 'no-cache',
            'Expires' => '-1',
            'X-Content-Type-Options' => 'nosniff',
        ];
        
        try {
            // PASO 1: Verificar que hay sesión en request
            if (!$request->session()->has('persona_id')) {
                return response()->json(
                    ['valid' => false, 'message' => 'Sesión expirada (no encontrada)'],
                    401,
                    $headers
                );
            }

            $personaId = $request->session()->get('persona_id');
            $sessionId = $request->session()->getId();

            // PASO 2: Verificar que la sesión está en tabla user_sessions y está ACTIVA
            if (!SessionManager::isSessionValid($personaId, $sessionId)) {
                return response()->json(
                    ['valid' => false, 'message' => 'Sesión cerrada en otro dispositivo'],
                    401,
                    $headers
                );
            }

            // PASO 3: Actualizar última actividad
            SessionManager::updateLastActivity($personaId);

            // Sesión válida
            return response()->json(
                [
                    'valid' => true,
                    'message' => 'Sesión activa',
                    'persona_id' => $personaId,
                    'role' => $request->session()->get('role'),
                ],
                200,
                $headers
            );

        } catch (\Throwable $e) {
            // En caso de error, retornar 500
            return response()->json(
                ['valid' => false, 'message' => 'Error validando sesión'],
                500,
                $headers
            );
        }
    }

    public function actualizarPerfil(Request $request)
    {
        $personaId = $request->session()->get('persona_id');
        $role = $request->session()->get('role');
        $registro = $request->session()->get('registro');

        if (!$personaId || !$role) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        \Illuminate\Support\Facades\Log::info('[actualizarPerfil] Iniciando', [
            'personaId' => $personaId,
            'role' => $role,
            'registro' => $registro,
            'payload' => $request->all(),
        ]);

        try {
            $validated = $request->validate([
                'telefono' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:100',
                'ciudad' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:100',
                // postulante
                'colegio_procedencia' => 'nullable|string|max:100',
                // administrativo/coordinador
                'horario_trabajo' => 'nullable|string|max:100',
                // docente fields (not editable here per policy)
                'especialidad' => 'nullable|string|max:50',
                'area' => 'nullable|string|max:50',
                'maestria' => 'nullable|string|max:50',
                'diplomado' => 'nullable|string|max:50',
                // horario fields (not editable through this endpoint)
                'hora_inicio' => 'nullable|date_format:H:i',
                'hora_fin' => 'nullable|date_format:H:i',
                'dias' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('[actualizarPerfil] Error de validación', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        // Define allowed editable fields per role (fields mentioned in the request must remain non-editable)
        $personaAllowed = ['telefono', 'direccion', 'ciudad', 'email'];
        $roleAllowed = [];

        $roleLabel = $role; // role stored in session is like 'postulante' or 'docente'

        switch ($roleLabel) {
            case 'postulante':
                // Cannot edit: nombre, apellido, ci, fecha_nacimiento, titulo_bachiller, horario_trabajo, hora_inicio, hora_fin, dias
                $roleAllowed = array_merge($personaAllowed, ['colegio_procedencia']);
                break;
            case 'administrativo':
            case 'coordinador':
                // Cannot edit: nombre, apellido, ci, fecha_nacimiento, titulo_bachiller, horario_trabajo, hora_inicio, hora_fin, dias, profesion, nro_titulo
                $roleAllowed = $personaAllowed;
                break;
            case 'docente':
            case 'decano':
                // Cannot edit: nombre, apellido, ci, fecha_nacimiento, titulo_bachiller, especialidad, area, maestria, diplomado, grupos_asignados, horario_trabajo, hora_inicio, hora_fin, dias
                $roleAllowed = $personaAllowed;
                break;
            default:
                $roleAllowed = $personaAllowed;
        }

        // Keep only allowed keys from validated
        $payload = array_intersect_key($validated, array_flip($roleAllowed));

        \Illuminate\Support\Facades\Log::info('[actualizarPerfil] Payload después de intersect_key', [
            'payload' => $payload,
            'roleAllowed' => $roleAllowed,
        ]);

        try {
            DB::transaction(function () use ($payload, $personaId, $roleLabel, $registro) {
                // update persona contact fields only
                $personaUpdate = [];
                if (isset($payload['telefono'])) $personaUpdate['telefono'] = $payload['telefono'];
                if (isset($payload['direccion'])) $personaUpdate['direccion'] = $payload['direccion'];
                if (isset($payload['ciudad'])) $personaUpdate['ciudad'] = $payload['ciudad'];
                if (isset($payload['email'])) $personaUpdate['correo_electronico'] = $payload['email'];

                \Illuminate\Support\Facades\Log::info('[actualizarPerfil] personaUpdate array', [
                    'personaUpdate' => $personaUpdate,
                ]);

                if (!empty($personaUpdate)) {
                    $personaUpdate['updated_at'] = now();
                    $updated = DB::table('persona')->where('id', $personaId)->update($personaUpdate);
                    \Illuminate\Support\Facades\Log::info('[actualizarPerfil] Update result', [
                        'rows_updated' => $updated,
                    ]);
                }

                // role specific updates (only allowed ones per policy)
                if ($roleLabel === 'postulante') {
                    $post = [];
                    if (isset($payload['colegio_procedencia'])) $post['colegio_procedencia'] = $payload['colegio_procedencia'];
                    if (!empty($post)) {
                        DB::table('postulante')->where('id_persona', $personaId)->update($post);
                    }
                }

                if (in_array($roleLabel, ['administrativo', 'coordinador'], true)) {
                    // horario_trabajo no es editable - se ignora si viene en payload
                    $adm = [];
                    if (isset($payload['horario_trabajo'])) $adm['horario_trabajo'] = $payload['horario_trabajo'];
                    if (!empty($adm)) {
                        $table = $roleLabel === 'administrativo' ? 'administrativo' : 'coordinador';
                        if ($registro) {
                            DB::table($table)->where('codigo', $registro)->update($adm);
                        } else {
                            DB::table($table)->where('id_persona', $personaId)->update($adm);
                        }
                    }
                }

                BitacoraService::registrar(
                    "Actualización de perfil ({$roleLabel})",
                    request()->ip(),
                    $personaId
                );
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[actualizarPerfil] Error en transacción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return response()->json(['message' => 'Perfil actualizado correctamente']);
    }

    /**
     * Solicitar código de recuperación de contraseña
     * POST /postularse/solicitar-recuperacion
     */
    public function requestPasswordRecovery(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('[requestPasswordRecovery] Método llamado', [
            'role' => $request->input('role'),
            'registro' => $request->input('registro'),
            'email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);

        try {
            $validated = $request->validate([
                'role' => 'required|in:postulante,docente,administrativo,coordinador,decano',
                'registro' => 'required|string|max:20',
                'email' => 'required|email|max:100',
            ]);

            // 1. Buscar persona según el rol y registro
            $persona = null;
            $role = $validated['role'];
            $registro = $validated['registro'];

            if ($role === 'postulante') {
                $record = DB::table('postulante')
                    ->where('registro', $registro)
                    ->first();
                if ($record) {
                    $persona = DB::table('persona')->where('id', $record->id_persona)->first();
                }
            } elseif ($role === 'docente') {
                $record = DB::table('docente')
                    ->where('codigo', $registro)
                    ->first();
                if ($record) {
                    $persona = DB::table('persona')->where('id', $record->id_persona)->first();
                }
            } elseif ($role === 'administrativo') {
                $record = DB::table('administrativo')
                    ->where('codigo', $registro)
                    ->first();
                if ($record) {
                    $persona = DB::table('persona')->where('id', $record->id_persona)->first();
                }
            } elseif ($role === 'coordinador') {
                $record = DB::table('coordinador')
                    ->where('codigo', $registro)
                    ->first();
                if ($record) {
                    $persona = DB::table('persona')->where('id', $record->id_persona)->first();
                }
            } elseif ($role === 'decano') {
                $record = DB::table('decano')
                    ->where('codigo', $registro)
                    ->first();
                if ($record) {
                    $persona = DB::table('persona')->where('id', $record->id_persona)->first();
                }
            }

            \Illuminate\Support\Facades\Log::info('[requestPasswordRecovery] Búsqueda por rol/registro completada', [
                'role' => $role,
                'registro' => $registro,
                'found' => $persona ? true : false,
                'persona_id' => $persona?->id,
            ]);

            if (!$persona) {
                // No revelar si el registro existe o no (seguridad)
                \Illuminate\Support\Facades\Log::warning('[requestPasswordRecovery] Registro/rol no encontrado', [
                    'role' => $role,
                    'registro' => $registro,
                ]);
                return response()->json([
                    'message' => 'Si los datos coinciden con nuestros registros, recibirás un código de verificación.',
                ], 200);
            }

            // 2. Validar que el email coincide
            if ($persona->correo_electronico !== $validated['email']) {
                \Illuminate\Support\Facades\Log::warning('[requestPasswordRecovery] Email no coincide', [
                    'role' => $role,
                    'registro' => $registro,
                    'email_provided' => $validated['email'],
                    'email_registered' => $persona->correo_electronico,
                ]);
                return response()->json([
                    'error' => 'El correo electrónico no coincide con el registrado para esta cuenta.',
                ], 422);
            }

            // 3. Limpiar tokens anteriores no usados
            DB::table('password_recovery_tokens')
                ->where('id_persona', $persona->id)
                ->where('is_used', false)
                ->delete();

            // 4. Generar código y token
            $verificationCode = \App\Models\PasswordRecoveryToken::generateUniqueCode();
            $resetToken = \App\Models\PasswordRecoveryToken::generateUniqueResetToken();

            \Illuminate\Support\Facades\Log::info('[requestPasswordRecovery] Códigos generados', [
                'verification_code' => $verificationCode,
            ]);

            // 5. Crear registro en BD
            $recovery = DB::table('password_recovery_tokens')->insert([
                'id_persona' => $persona->id,
                'email' => $validated['email'],
                'verification_code' => $verificationCode,
                'reset_token' => $resetToken,
                'code_expires_at' => now()->addMinutes(15),
                'reset_expires_at' => now()->addHour(),
                'is_used' => false,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\Log::info('[requestPasswordRecovery] Token insertado en BD', [
                'insert_result' => $recovery,
            ]);

            // 6. Enviar email con código
            try {
                \Illuminate\Support\Facades\Mail::to($validated['email'])->send(
                    new \App\Mail\PasswordRecoveryCode($persona->nombre . ' ' . $persona->apellido, $verificationCode, 15)
                );
                \Illuminate\Support\Facades\Log::info('[requestPasswordRecovery] Email enviado exitosamente');
            } catch (\Exception $mailError) {
                \Illuminate\Support\Facades\Log::error('[requestPasswordRecovery] Error al enviar email', [
                    'error' => $mailError->getMessage(),
                ]);
                // Continuar de todas formas (el código está guardado en BD)
            }

            // 7. Registrar en bitácora
            BitacoraService::registrar(
                "Solicitud de recuperación de contraseña",
                $request->ip(),
                $persona->id
            );

            return response()->json([
                'message' => 'Se ha enviado un código de verificación a tu correo electrónico.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('[requestPasswordRecovery] Error de validación', [
                'errors' => $e->errors(),
            ]);
            return response()->json(['error' => 'Error de validación', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[requestPasswordRecovery] Error general', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Error al procesar solicitud: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verificar código de recuperación
     * POST /postularse/verificar-codigo-recuperacion
     */
    public function verifyRecoveryCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:100',
                'verification_code' => 'required|string|size:6',
            ]);

            \Illuminate\Support\Facades\Log::info('[verifyRecoveryCode] Verificando código', [
                'email' => $validated['email'],
                'code_length' => strlen($validated['verification_code']),
            ]);

            // 1. Buscar token válido
            $recovery = DB::table('password_recovery_tokens')
                ->where('email', $validated['email'])
                ->where('verification_code', $validated['verification_code'])
                ->where('is_used', false)
                ->first();

            if (!$recovery) {
                \Illuminate\Support\Facades\Log::warning('[verifyRecoveryCode] Token no encontrado', [
                    'email' => $validated['email'],
                ]);
                return response()->json(['error' => 'Código inválido o expirado'], 422);
            }

            // 2. Verificar expiración
            if (now()->isAfter($recovery->code_expires_at)) {
                \Illuminate\Support\Facades\Log::warning('[verifyRecoveryCode] Código expirado', [
                    'email' => $validated['email'],
                ]);
                DB::table('password_recovery_tokens')
                    ->where('id', $recovery->id)
                    ->update(['is_used' => true]);
                return response()->json(['error' => 'Código expirado. Solicita uno nuevo.'], 422);
            }

            // 3. Código válido, devolver reset_token
            return response()->json([
                'message' => 'Código verificado exitosamente',
                'reset_token' => $recovery->reset_token,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('[verifyRecoveryCode] Error de validación', [
                'errors' => $e->errors(),
            ]);
            return response()->json(['error' => 'Error de validación', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[verifyRecoveryCode] Error general', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Error al verificar código'], 500);
        }
    }

    /**
     * Restablecer contraseña
     * POST /postularse/restablecer-contraseña
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'reset_token' => 'required|string',
                'new_password' => 'required|string|min:4|max:50',
                'confirm_password' => 'required|string|same:new_password',
            ]);

            \Illuminate\Support\Facades\Log::info('[resetPassword] Iniciando restablecimiento', [
                'ip' => $request->ip(),
            ]);

            // 1. Buscar token válido
            $recovery = DB::table('password_recovery_tokens')
                ->where('reset_token', $validated['reset_token'])
                ->where('is_used', false)
                ->first();

            if (!$recovery) {
                \Illuminate\Support\Facades\Log::warning('[resetPassword] Token no encontrado');
                return response()->json(['error' => 'Token inválido'], 422);
            }

            // 2. Verificar expiración
            if (now()->isAfter($recovery->reset_expires_at)) {
                \Illuminate\Support\Facades\Log::warning('[resetPassword] Token expirado', [
                    'id_persona' => $recovery->id_persona,
                ]);
                DB::table('password_recovery_tokens')
                    ->where('id', $recovery->id)
                    ->update(['is_used' => true]);
                return response()->json(['error' => 'Token expirado. Solicita uno nuevo.'], 422);
            }

            // 3. Actualizar contraseña en persona
            DB::transaction(function () use ($recovery, $validated) {
                // Actualizar password (en este sistema, la contraseña = CI)
                // Pero aquí la guardaremos como contraseña real
                DB::table('persona')
                    ->where('id', $recovery->id_persona)
                    ->update([
                        'temporary_password' => $validated['new_password'],
                        'updated_at' => now(),
                    ]);

                // Marcar token como usado
                DB::table('password_recovery_tokens')
                    ->where('id', $recovery->id)
                    ->update(['is_used' => true, 'updated_at' => now()]);

                // Registrar en bitácora
                BitacoraService::registrar(
                    "Restablecimiento de contraseña completado",
                    request()->ip(),
                    $recovery->id_persona
                );
            });

            \Illuminate\Support\Facades\Log::info('[resetPassword] Contraseña restablecida exitosamente', [
                'id_persona' => $recovery->id_persona,
            ]);

            return response()->json([
                'message' => 'Contraseña restablecida exitosamente',
                'redirect' => route('postulacion.login'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('[resetPassword] Error de validación', [
                'errors' => $e->errors(),
            ]);
            return response()->json(['error' => 'Error de validación', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[resetPassword] Error general', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Error al restablecer contraseña'], 500);
        }
    }

    /**
     * Obtener CI del postulante por su registro (API endpoint)
     * GET /api/postulante-ci/{registro}
     */
    public function getPostulanteCi($registro)
    {
        try {
            $postulante = DB::table('postulante')->where('registro', $registro)->first();
            if (!$postulante) return response()->json(['error' => 'Postulante no encontrado'], 404);
            
            $persona = DB::table('persona')->where('id', $postulante->id_persona)->select('ci')->first();
            if (!$persona) return response()->json(['error' => 'Persona no encontrada'], 404);
            
            return response()->json(['ci' => $persona->ci], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al buscar postulante'], 500);
        }
    }

    /**
     * Obtener CI del docente por su registro (API endpoint)
     * GET /api/docente-ci/{registro}
     */
    public function getDocenteCi($registro)
    {
        try {
            $docente = DB::table('docente')->where('codigo', $registro)->first();
            if (!$docente) return response()->json(['error' => 'Docente no encontrado'], 404);
            
            $persona = DB::table('persona')->where('id', $docente->id_persona)->select('ci')->first();
            if (!$persona) return response()->json(['error' => 'Persona no encontrada'], 404);
            
            return response()->json(['ci' => $persona->ci], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al buscar docente'], 500);
        }
    }

    /**
     * Obtener CI del administrativo por su registro (API endpoint)
     * GET /api/administrativo-ci/{registro}
     */
    public function getAdministrativoCi($registro)
    {
        try {
            $administrativo = DB::table('administrativo')->where('codigo', $registro)->first();
            if (!$administrativo) return response()->json(['error' => 'Administrativo no encontrado'], 404);
            
            $persona = DB::table('persona')->where('id', $administrativo->id_persona)->select('ci')->first();
            if (!$persona) return response()->json(['error' => 'Persona no encontrada'], 404);
            
            return response()->json(['ci' => $persona->ci], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al buscar administrativo'], 500);
        }
    }

    /**
     * Obtener CI del coordinador por su registro (API endpoint)
     * GET /api/coordinador-ci/{registro}
     */
    public function getCoordinadorCi($registro)
    {
        try {
            $coordinador = DB::table('coordinador')->where('codigo', $registro)->first();
            if (!$coordinador) return response()->json(['error' => 'Coordinador no encontrado'], 404);
            
            $persona = DB::table('persona')->where('id', $coordinador->id_persona)->select('ci')->first();
            if (!$persona) return response()->json(['error' => 'Persona no encontrada'], 404);
            
            return response()->json(['ci' => $persona->ci], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al buscar coordinador'], 500);
        }
    }

    /**
     * Obtener CI del decano por su registro (API endpoint)
     * GET /api/decano-ci/{registro}
     */
    public function getDecanoCi($registro)
    {
        try {
            $decano = DB::table('decano')->where('codigo', $registro)->first();
            if (!$decano) return response()->json(['error' => 'Decano no encontrado'], 404);
            
            $persona = DB::table('persona')->where('id', $decano->id_persona)->select('ci')->first();
            if (!$persona) return response()->json(['error' => 'Persona no encontrada'], 404);
            
            return response()->json(['ci' => $persona->ci], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al buscar decano'], 500);
        }
    }
}
