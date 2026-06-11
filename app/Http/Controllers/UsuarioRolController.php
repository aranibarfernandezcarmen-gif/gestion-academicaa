<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\BitacoraService;
use App\Services\DatabaseOperationsService;
use App\Mail\CredencialesDocente;
use App\Mail\CredencialesAdministrativo;
use App\Mail\CredencialesCoordinador;
use Inertia\Inertia;

class UsuarioRolController extends Controller
{
    public function index()
    {
        $grupos = DB::table('rol_grupo')->get();
        $permisos = DB::table('rol_grupo_privilegio')
            ->get()
            ->groupBy('codigo_rol_grupo')
            ->map(function ($items) {
                return $items->pluck('codigo_cu')->values();
            })
            ->toArray();

        return Inertia::render('CU02UsuariosRoles', [
            'grupos' => $grupos,
            'permisos' => $permisos,
        ]);
    }

    public function registrarCuenta(Request $request)
    {
        $validated = $request->validate([
            'rol' => 'required|in:docente,administrativo,coordinador',
            'nombre' => 'required|string|max:50',
            'apellido' => 'required|string|max:50',
            'ci' => 'required|string|max:20|unique:persona,ci',
            'fecha_nacimiento' => 'required|date',
            'sexo' => 'required|string|max:10',
            'direccion' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|max:100',
            'ciudad' => 'required|string|max:50',
            'colegio' => 'nullable|string|max:100',
            'titulo_bachiller' => 'nullable|string|max:50',
            'especialidad' => 'nullable|string|max:50',
            'area' => 'nullable|string|max:50',
            'maestria' => 'nullable|string|max:50',
            'diplomado' => 'nullable|string|max:50',
            'profesion' => 'nullable|string|max:100',
            'nro_titulo' => 'nullable|string|max:50',
            'horario_trabajo' => 'nullable',
            'dias_trabajo' => 'nullable|array',
            'hora_entrada' => 'nullable|string|max:5',
            'hora_salida' => 'nullable|string|max:5',
        ]);

        $credenciales = null;
        DB::transaction(function () use ($validated, &$credenciales) {
            // Resetear secuencia de persona antes de insertar
            DB::statement("SELECT setval(pg_get_serial_sequence('persona', 'id'), (SELECT MAX(id) FROM persona) + 1)");
            
            $personaId = DB::table('persona')->insertGetId([
                'ci' => $validated['ci'],
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'sexo' => $validated['sexo'],
                'direccion' => $validated['direccion'],
                'telefono' => $validated['telefono'],
                'correo_electronico' => $validated['email'],
                'ciudad' => $validated['ciudad'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            switch ($validated['rol']) {
                case 'docente':
                    $codigo = 'DOC' . str_pad($personaId, 3, '0', STR_PAD_LEFT);
                    
                    // Verificar si ya existe
                    $existe = DB::table('docente')->where('id_persona', $personaId)->exists();
                    if (!$existe) {
                        DB::table('docente')->insert([
                            'codigo' => $codigo,
                            'id_persona' => $personaId,
                            'especialidad' => $validated['especialidad'] ?? '',
                            'profesional_area' => $validated['area'] ?? '',
                            'maestria' => $validated['maestria'] ?? '',
                            'diplomado_educacion_superior' => $validated['diplomado'] ?? '',
                            'cantidad_grupos_asignados' => 0,
                        ]);
                    }
                    $credenciales = [
                        'registro' => $codigo,
                        'ci' => $validated['ci'],
                    ];
                    
                    // Enviar email con credenciales
                    Mail::to($validated['email'])->send(new CredencialesDocente(
                        $validated['nombre'],
                        $validated['apellido'],
                        $codigo,
                        $validated['ci']
                    ));
                    break;

                case 'administrativo':
                    $codigo = 'ADM' . str_pad($personaId, 3, '0', STR_PAD_LEFT);
                    $existe = DB::table('administrativo')->where('id_persona', $personaId)->exists();
                    if (!$existe) {
                        // Preparar datos de horario en formato JSON
                        $horario = null;
                        if (!empty($validated['dias_trabajo']) && !empty($validated['hora_entrada']) && !empty($validated['hora_salida'])) {
                            $horario = json_encode([
                                'dias' => $validated['dias_trabajo'],
                                'hora_entrada' => $validated['hora_entrada'],
                                'hora_salida' => $validated['hora_salida'],
                            ]);
                        }
                        
                        DB::table('administrativo')->insert([
                            'codigo' => $codigo,
                            'id_persona' => $personaId,
                            'profesion' => $validated['profesion'] ?? '',
                            'nro_titulo' => $validated['nro_titulo'] ?? '',
                            'horario_trabajo' => $horario,
                        ]);
                    }
                    
                    $credenciales = [
                        'registro' => $codigo,
                        'ci' => $validated['ci'],
                    ];
                    
                    // Enviar email con credenciales
                    Mail::to($validated['email'])->send(new CredencialesAdministrativo(
                        $validated['nombre'],
                        $validated['apellido'],
                        $codigo,
                        $validated['ci'],
                        $validated['profesion'] ?? '',
                        $validated['nro_titulo'] ?? ''
                    ));
                    break;

                case 'coordinador':
                    $codigo = 'COORD' . str_pad($personaId, 3, '0', STR_PAD_LEFT);
                    $existe = DB::table('coordinador')->where('id_persona', $personaId)->exists();
                    if (!$existe) {
                        DB::table('coordinador')->insert([
                            'codigo' => $codigo,
                            'id_persona' => $personaId,
                            'horario_trabajo' => $validated['horario_trabajo'],
                            'profesion' => $validated['profesion'] ?? '',
                            'nro_titulo' => $validated['nro_titulo'] ?? '',
                        ]);
                    }
                    
                    $credenciales = [
                        'registro' => $codigo,
                        'ci' => $validated['ci'],
                    ];
                    
                    // Enviar email con credenciales
                    Mail::to($validated['email'])->send(new CredencialesCoordinador(
                        $validated['nombre'],
                        $validated['apellido'],
                        $codigo,
                        $validated['ci']
                    ));
                    break;
            }

            BitacoraService::registrar(
                "Registro de cuenta {$validated['rol']} para {$validated['nombre']} {$validated['apellido']}",
                request()->ip(),
                $personaId
            );
        });

        if ($credenciales) {
            return response()->json([
                'success' => true,
                'message' => 'Cuenta registrada correctamente',
                'credenciales' => $credenciales
            ], 201);
        }
        return back()->with('success', 'Cuenta registrada correctamente');
    }

    public function obtenerPersonas($tipo)
    {
        $tipos = [
            'docente' => 'docente',
            'administrativo' => 'administrativo',
            'coordinador' => 'coordinador',
            'postulante' => 'postulante',
        ];

        if (!isset($tipos[$tipo])) {
            return response()->json(['error' => 'Tipo inválido'], 400);
        }

        $tableName = strtolower($tipo);
        
        // Construir SELECT dinámico según el tipo
        $selectColumns = ['persona.*'];
        
        // Solo agregar código si la tabla lo tiene
        if (in_array($tableName, ['docente', 'administrativo', 'coordinador'])) {
            $selectColumns[] = "{$tableName}.codigo";
        }
        
        // Para docente, agregar el área
        if ($tableName === 'docente') {
            $selectColumns[] = "{$tableName}.profesional_area as area";
        }
        
        // Para postulante, agregar registro si existe
        if ($tableName === 'postulante') {
            $selectColumns[] = "{$tableName}.registro";
            $selectColumns[] = DB::raw("COALESCE(inscripcion.estado_pago, 'Pendiente') as estado_pago");
        }

        $query = DB::table('persona')
            ->join($tableName, 'persona.id', '=', "{$tableName}.id_persona");
        
        // Para postulante, hacer leftJoin con inscripcion y filtrar por estado_pago = 'Pagado'
        if ($tableName === 'postulante') {
            $query->leftJoin('inscripcion', 'postulante.codigo_inscripcion', '=', 'inscripcion.id')
                  ->whereRaw("COALESCE(inscripcion.estado_pago, 'Pendiente') = ?", ['Pagado']);
        }
        
        $personas = $query->select($selectColumns)
            ->get();

        return response()->json($personas);
    }

    public function actualizarPersona(Request $request, $tipo, $personaId)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'apellido' => 'required|string|max:50',
            'ci' => 'required|string|max:20|unique:persona,ci,' . $personaId,
            'email' => 'required|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($validated, $personaId, $tipo) {
            DB::table('persona')->where('id', $personaId)->update([
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'ci' => $validated['ci'],
                'telefono' => $validated['telefono'],
                'direccion' => $validated['direccion'],
                'ciudad' => $validated['ciudad'],
                'correo_electronico' => $validated['email'],
                'updated_at' => now(),
            ]);

            BitacoraService::registrar(
                "Actualización de persona {$personaId} ({$tipo}): {$validated['nombre']} {$validated['apellido']}",
                request()->ip(),
                $personaId
            );
        });

        return response()->json(['message' => 'Persona actualizada correctamente']);
    }

    public function eliminarPersona($tipo, $personaId)
    {
        try {
            $tiposValidos = ['docente', 'administrativo', 'coordinador', 'postulante'];
            if (!in_array($tipo, $tiposValidos)) {
                return response()->json(['error' => 'Tipo inválido'], 400);
            }

            DB::transaction(function () use ($tipo, $personaId) {
                // Deshabilitar el trigger de auditoría antes de eliminar
                DB::statement('ALTER TABLE persona DISABLE TRIGGER USER');
                
                try {
                    // Registrar ANTES de eliminar
                    try {
                        BitacoraService::registrar(
                            "Eliminación de persona {$personaId} tipo {$tipo}",
                            request()->ip(),
                            $personaId
                        );
                    } catch (\Throwable $e) {
                        // Ignorar errores de bitácora
                        \Log::warning("No se pudo registrar eliminación en bitácora: " . $e->getMessage());
                    }
                    
                    // Eliminar registros de bitacora que referencian esta persona
                    DB::table('bitacora')->where('id_persona', $personaId)->delete();
                    
                    // Luego eliminar el tipo específico
                    DB::table($tipo)->where('id_persona', $personaId)->delete();
                    
                    // Finalmente eliminar la persona
                    DB::table('persona')->where('id', $personaId)->delete();
                } finally {
                    // Reabilitar el trigger
                    DB::statement('ALTER TABLE persona ENABLE TRIGGER USER');
                }
            });

            return response()->json(['success' => true, 'message' => 'Persona eliminada correctamente']);
        } catch (\Throwable $e) {
            \Log::error('Error al eliminar persona: ' . $e->getMessage(), ['personaId' => $personaId, 'tipo' => $tipo]);
            return response()->json(
                ['error' => 'No se pudo eliminar la persona: ' . $e->getMessage()],
                500
            );
        }
    }

    public function asignarPermisos(Request $request)
    {
        $validated = $request->validate([
            'grupo_rol_id' => 'required|exists:rol_grupo,codigo',
            'cus' => 'required|array',
        ]);

        // Deshabilitar triggers durante operación de permisos
        DB::statement('ALTER TABLE rol_grupo_privilegio DISABLE TRIGGER USER');
        
        try {
            // Borrar permisos antiguos
            DB::table('rol_grupo_privilegio')
                ->where('codigo_rol_grupo', $validated['grupo_rol_id'])
                ->delete();

            // Insertar nuevos permisos
            foreach ($validated['cus'] as $cu) {
                DB::table('rol_grupo_privilegio')->insert([
                    'codigo_rol_grupo' => $validated['grupo_rol_id'],
                    'codigo_cu' => $cu['codigo'],
                    'descripcion_cu' => $cu['descripcion'],
                ]);
            }
        } finally {
            // Re-habilitar triggers
            DB::statement('ALTER TABLE rol_grupo_privilegio ENABLE TRIGGER USER');
        }

        return response()->json(['message' => 'Permisos asignados correctamente'], 200);
    }

    // =========================================================================
    // MÉTODOS MASIVOS - INTEGRACIÓN DE PA Y FUNCIONES FASE 2
    // =========================================================================

    /**
     * Crear múltiples usuarios masivamente desde JSON
     * POST /cu02/usuarios-masivos
     * Body: { "usuarios": [{"name":"Juan","email":"juan@example.com","password":"hash123"}] }
     */
    public function crearUsuariosMasivos(Request $request)
    {
        $validated = $request->validate([
            'usuarios' => 'required|array|min:1',
            'usuarios.*.name' => 'required|string|max:255',
            'usuarios.*.email' => 'required|email|max:255|unique:users',
            'usuarios.*.password' => 'required|string|min:8',
        ]);

        $dbOps = new DatabaseOperationsService();
        $jsonUsuarios = json_encode($validated['usuarios']);

        $resultado = $dbOps->crearUsuariosMasivos($jsonUsuarios);

        BitacoraService::registrar(
            "Carga masiva de usuarios: {$resultado['exitosos']} exitosos, {$resultado['fallidos']} fallidos",
            request()->ip()
        );

        return response()->json([
            'success' => $resultado['success'],
            'message' => $resultado['message'],
            'total' => $resultado['total'],
            'exitosos' => $resultado['exitosos'],
            'fallidos' => $resultado['fallidos']
        ], $resultado['success'] ? 201 : 400);
    }

    /**
     * Importar múltiples personas masivamente desde JSON
     * POST /cu02/personas-masivas
     * Body: { "personas": [{"ci":"1234567","nombre":"Juan",...}] }
     */
    public function importarPersonasMasivas(Request $request)
    {
        $validated = $request->validate([
            'personas' => 'required|array|min:1',
            'personas.*.ci' => 'required|string|max:20|unique:persona,ci',
            'personas.*.nombre' => 'required|string|max:50',
            'personas.*.apellido' => 'required|string|max:50',
            'personas.*.fecha_nacimiento' => 'required|date',
            'personas.*.sexo' => 'required|string|max:10',
            'personas.*.direccion' => 'required|string|max:100',
            'personas.*.telefono' => 'nullable|string|max:20',
            'personas.*.correo_electronico' => 'required|email|max:100|unique:persona,correo_electronico',
            'personas.*.ciudad' => 'required|string|max:50',
        ]);

        $dbOps = new DatabaseOperationsService();
        $jsonPersonas = json_encode($validated['personas']);

        $resultado = $dbOps->importarPersonasMasivas($jsonPersonas);

        BitacoraService::registrar(
            "Importación masiva de personas: {$resultado['exitosos']} exitosos, {$resultado['fallidos']} fallidos",
            request()->ip()
        );

        return response()->json([
            'success' => $resultado['success'],
            'message' => $resultado['message'],
            'total' => $resultado['total'],
            'exitosos' => $resultado['exitosos'],
            'fallidos' => $resultado['fallidos']
        ], $resultado['success'] ? 201 : 400);
    }

    /**
     * Asignar múltiples permisos masivamente desde JSON
     * POST /cu02/permisos-masivos
     * Body: { "asignaciones": [{"codigo_rol_grupo":1,"codigo_cu":"CU01","descripcion_cu":"..."}] }
     */
    public function asignarPermisosMasivos(Request $request)
    {
        $validated = $request->validate([
            'asignaciones' => 'required|array|min:1',
            'asignaciones.*.codigo_rol_grupo' => 'required|integer|exists:rol_grupo,codigo',
            'asignaciones.*.codigo_cu' => 'required|string|regex:/^CU\d{2}$/',
            'asignaciones.*.descripcion_cu' => 'nullable|string|max:255',
        ]);

        $dbOps = new DatabaseOperationsService();
        $jsonAsignaciones = json_encode($validated['asignaciones']);

        $resultado = $dbOps->asignarPermisosMasivos($jsonAsignaciones);

        BitacoraService::registrar(
            "Asignación masiva de permisos: {$resultado['exitosos']} exitosos, {$resultado['fallidos']} fallidos",
            request()->ip()
        );

        return response()->json([
            'success' => $resultado['success'],
            'message' => $resultado['message'],
            'total' => $resultado['total'],
            'exitosos' => $resultado['exitosos'],
            'fallidos' => $resultado['fallidos']
        ], $resultado['success'] ? 201 : 400);
    }

    /**
     * Calcular asistencia para un período académico
     * GET /cu02/asistencia-periodo?inicio=2026-01-01&fin=2026-06-30
     */
    public function calcularAsistenciaPeriodo(Request $request)
    {
        $validated = $request->validate([
            'inicio' => 'required|date_format:Y-m-d',
            'fin' => 'required|date_format:Y-m-d|after_or_equal:inicio',
        ]);

        $dbOps = new DatabaseOperationsService();
        $resultado = $dbOps->calcularAsistenciaPeriodo($validated['inicio'], $validated['fin']);

        BitacoraService::registrar(
            "Cálculo de asistencia por período: {$validated['inicio']} a {$validated['fin']}",
            request()->ip()
        );

        return response()->json([
            'success' => $resultado['success'],
            'message' => $resultado['message'],
            'datos' => $resultado['datos'] ?? []
        ], $resultado['success'] ? 200 : 400);
    }
}
