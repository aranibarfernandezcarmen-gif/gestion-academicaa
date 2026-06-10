<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\ImportacionMasiva;
use App\Services\BitacoraService;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CU15ImportacionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('CU15ImportacionMasiva', [
            'registro' => request()->query('registro'),
            'role' => request()->query('role'),
        ]);
    }

    /**
     * Obtener historial de importaciones
     */
    public function getHistorial(Request $request)
    {
        $importaciones = ImportacionMasiva::query()
            ->with('usuario')
            ->when($request->tipo_datos, fn($q) => $q->where('tipo_datos', $request->tipo_datos))
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($importaciones);
    }

    /**
     * Iniciar importación masiva
     */
    public function iniciarImportacion(Request $request)
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:csv,xlsx|max:5120',
            'tipo_datos' => 'required|in:Postulantes',
            'descripcion' => 'nullable|string',
        ]);

        try {
            // Obtener ID de usuario (Auth → sesión → registro de decano)
            $idUsuario = Auth::id() ?? session('persona_id');
            if (!$idUsuario && $request->has('registro')) {
                $registro = $request->get('registro');
                $decano = DB::table('decano')->where('codigo', $registro)->first();
                if ($decano) {
                    $idUsuario = $decano->id_persona;
                }
            }

            // Mapear extensión al enum aceptado por la BD
            $ext = strtolower($validated['archivo']->getClientOriginalExtension());
            $formatoArchivo = match($ext) {
                'xlsx' => 'Excel',
                'csv'  => 'CSV',
                default => 'CSV',
            };

            // Guardar archivo y crear registro
            $archivo = $validated['archivo'];
            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            $rutaArchivo = $archivo->storeAs('importaciones', $nombreArchivo, 'public');

            $importacion = DB::transaction(function () use ($validated, $nombreArchivo, $rutaArchivo, $idUsuario, $formatoArchivo) {
                return ImportacionMasiva::create([
                    'id_usuario' => $idUsuario,
                    'tipo_datos' => $validated['tipo_datos'],
                    'formato_archivo' => $formatoArchivo,
                    'nombre_archivo' => $nombreArchivo,
                    'ruta_archivo' => $rutaArchivo,
                    'estado' => 'Procesando',
                    'fecha_inicio' => now(),
                ]);
            });

            // Procesar DESPUÉS de guardar el registro
            $this->procesarSegunTipo($importacion);

            // Registrar en bitácora
            BitacoraService::registrar(
                "Importación {$importacion->id} completada - Tipo: {$validated['tipo_datos']}, Exitosos: {$importacion->registros_exitosos}, Fallidos: {$importacion->registros_fallidos}",
                request()->ip(),
                $idUsuario
            );

            return response()->json([
                'message' => 'Importación completada',
                'importacion' => $importacion->fresh(),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('CU15 Import Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error en importación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar importación (simular procesamiento de CSV)
     */
    public function procesarImportacion($id)
    {
        try {
            $importacion = ImportacionMasiva::findOrFail($id);

            if ($importacion->estado !== 'Pendiente') {
                return response()->json(['error' => 'Esta importación ya fue procesada'], 400);
            }

            $importacion->update([
                'estado' => 'Procesando',
                'fecha_inicio' => now(),
            ]);

            // Aquí iría la lógica de procesamiento real
            // Por ahora simularemos
            $this->procesarSegunTipo($importacion);

            BitacoraService::registrar(
                "Importación {$id} procesada - {$importacion->registros_exitosos} exitosos, {$importacion->registros_fallidos} fallidos",
                request()->ip(),
                Auth::id()
            );

            return response()->json([
                'message' => 'Importación completada',
                'importacion' => $importacion->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Procesar según el tipo de datos
     */
    private function procesarSegunTipo(ImportacionMasiva $importacion)
    {
        try {
            $archivo = Storage::disk('public')->get($importacion->ruta_archivo);

            if ($importacion->formato_archivo === 'CSV') {
                $datos = $this->parsearCSV($archivo);
            } elseif ($importacion->formato_archivo === 'Excel') {
                $datos = $this->parsearExcel($archivo);
            } else {
                $datos = json_decode($archivo, true) ?? [];
            }

            $total = count($datos);
            $exitosos = 0;
            $errores = [];

            switch ($importacion->tipo_datos) {
                case 'Postulantes':
                    foreach ($datos as $index => $fila) {
                        $resultado = $this->validarYGuardarPostulante($fila);
                        if ($resultado === true) {
                            $exitosos++;
                        } else {
                            $errores[] = "Fila " . ($index + 2) . ": " . ($resultado ?: "Error desconocido al guardar postulante");
                        }
                    }
                    break;

                case 'Calificaciones':
                    foreach ($datos as $index => $fila) {
                        $resultado = $this->validarYGuardarCalificacion($fila);
                        if ($resultado === true) {
                            $exitosos++;
                        } else {
                            $errores[] = "Fila " . ($index + 2) . ": " . ($resultado ?: "Error desconocido al guardar calificación");
                        }
                    }
                    break;

                default:
                    $exitosos = $total;
            }

            $importacion->update([
                'total_registros' => $total,
                'registros_exitosos' => $exitosos,
                'registros_fallidos' => $total - $exitosos,
                'estado' => count($errores) > 0 ? 'Completado con errores' : 'Completado',
                'errores' => $errores, // Laravel auto-encoda con cast 'json'
                'fecha_fin' => now(),
                'resumen' => [
                    'total_procesado' => $total,
                    'exitosos' => $exitosos,
                    'fallidos' => $total - $exitosos,
                    'porcentaje_exito' => $total > 0 ? round(($exitosos / $total) * 100, 2) : 0,
                ],
            ]);
        } catch (\Exception $e) {
            $importacion->update([
                'estado' => 'Error fatal',
                'errores' => [$e->getMessage()],
                'fecha_fin' => now(),
            ]);
        }
    }

    /**
     * Parsear archivo CSV (detecta delimitador automáticamente)
     */
    private function parsearCSV($contenido)
    {
        // Limpiar BOM y caracteres especiales mal codificados
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        
        $lineas = explode("\n", $contenido);
        $primeraLinea = array_shift($lineas);
        
        // Detectar delimitador (coma o punto y coma)
        $delimitador = strpos($primeraLinea, ';') !== false ? ';' : ',';
        
        // Parsear primera línea
        $encabezados = str_getcsv($primeraLinea, $delimitador);
        $encabezados = array_map('trim', $encabezados);
        
        // Remover columnas vacías al final
        while (end($encabezados) === '' && count($encabezados) > 0) {
            array_pop($encabezados);
        }
        
        // Detectar si la primera línea son encabezados o datos
        $tiposEsperados = ['CI', 'Nombre', 'Apellido', 'Fecha Nacimiento', 'Código Estudiante'];
        $esEncabezado = false;
        
        foreach ($tiposEsperados as $tipo) {
            if (in_array($tipo, $encabezados)) {
                $esEncabezado = true;
                break;
            }
        }
        
        // Si no son encabezados, agregamos encabezados por defecto y ponemos la línea de vuelta
        if (!$esEncabezado) {
            array_unshift($lineas, $primeraLinea);
            
            // Detectar tipo de datos por cantidad de columnas
            $numColumnas = count($encabezados);
            if ($numColumnas === 13) {
                $encabezados = ['CI', 'Nombre', 'Apellido', 'Fecha Nacimiento', 'Sexo', 'Direccion', 'Telefono', 'Email', 'Ciudad', 'Colegio', 'Titulo Bachiller', 'Carrera Primera Opcion', 'Carrera Segunda Opcion'];
            } elseif ($numColumnas === 8) {
                $encabezados = ['CI', 'Nombre', 'Apellido', 'Especialidad', 'Titulo', 'Telefono', 'Email', 'Facultad'];
            } elseif ($numColumnas === 4) {
                $encabezados = ['Codigo Estudiante', 'Codigo Materia', 'Calificacion', 'Fecha Evaluacion'];
            }
        }
        
        $datos = [];

        // Mapeo de columnas CSV a claves de base de datos
        $mapeo = [
            'CI' => 'ci',
            'Nombre' => 'nombre',
            'Apellido' => 'apellido',
            'Fecha Nacimiento' => 'fecha_nacimiento',
            'Sexo' => 'sexo',
            'Direccion' => 'direccion',
            'Telefono' => 'telefono',
            'Email' => 'email',
            'Ciudad' => 'ciudad',
            'Colegio' => 'colegio_procedencia',
            'Titulo Bachiller' => 'titulo_bachiller',
            'Carrera Primera Opcion' => 'carrera_primera_opcion',
            'Carrera Segunda Opcion' => 'carrera_segunda_opcion',
            'Otros Requisitos' => 'otros_requisitos',
            'Especialidad' => 'especialidad',
            'Titulo' => 'titulo',
            'Facultad' => 'facultad',
            'Codigo Estudiante' => 'codigo_estudiante',
            'Codigo Materia' => 'codigo_materia',
            'Calificacion' => 'calificacion',
            'Fecha Evaluacion' => 'fecha_evaluacion',
        ];

        foreach ($lineas as $linea) {
            if (empty(trim($linea))) continue;
            $valores = str_getcsv($linea, $delimitador);
            $valores = array_map('trim', $valores);
            
            // Remover columnas vacías al final para que coincidan con los encabezados
            while (end($valores) === '' && count($valores) > 0) {
                array_pop($valores);
            }
            
            if (count($valores) !== count($encabezados)) {
                continue; // Ignorar filas con formato incorrecto
            }
            
            $filaOriginal = array_combine($encabezados, $valores);
            $filaMapeada = [];
            
            // Mapear columnas según diccionario
            foreach ($filaOriginal as $encabezado => $valor) {
                $clave = $mapeo[$encabezado] ?? strtolower(str_replace(' ', '_', $encabezado));
                
                // Convertir fechas DD/MM/YYYY a YYYY-MM-DD
                if (in_array($clave, ['fecha_nacimiento', 'fecha_evaluacion', 'fecha_inscripcion'])) {
                    $valor = $this->convertirFecha($valor);
                }
                
                $filaMapeada[$clave] = $valor;
            }
            
            $datos[] = $filaMapeada;
        }

        return $datos;
    }

    /**
     * Convertir fecha (varios formatos y seriales Excel) a YYYY-MM-DD
     */
    private function convertirFecha($fecha)
    {
        if ($fecha === null || $fecha === '') return null;

        $fecha = trim((string)$fecha);
        if ($fecha === '') return null;

        // Excel almacena fechas como número serial (ej: 37723.0)
        if (is_numeric($fecha)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$fecha);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        // Formatos con y sin cero al frente (j=día sin cero, n=mes sin cero)
        $formatos = ['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'd.m.Y', 'j.n.Y', 'Y-m-d', 'Y/m/d'];
        foreach ($formatos as $formato) {
            $dt = \DateTime::createFromFormat($formato, $fecha);
            if ($dt !== false) {
                return $dt->format('Y-m-d');
            }
        }

        // Último recurso: strtotime
        $ts = strtotime($fecha);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * Parsear archivo Excel con PhpSpreadsheet
     */
    private function parsearExcel($contenido)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cu15_') . '.xlsx';
        file_put_contents($tmpFile, $contenido);

        try {
            $reader = new XlsxReader();
            // No usar setReadDataOnly(true): necesitamos los formatos de celda para leer fechas
            $spreadsheet = $reader->load($tmpFile);
            $hoja = $spreadsheet->getActiveSheet();
            $filas = $hoja->toArray(null, true, true, false);

            if (empty($filas)) return [];

            $mapeo = [
                'CI'                     => 'ci',
                'Nombre'                 => 'nombre',
                'Apellido'               => 'apellido',
                'Fecha Nacimiento'       => 'fecha_nacimiento',
                'Sexo'                   => 'sexo',
                'Direccion'              => 'direccion',
                'Dirección'              => 'direccion',
                'Teléfono'               => 'telefono',
                'Telefono'               => 'telefono',
                'Email'                  => 'email',
                'Ciudad'                 => 'ciudad',
                'Colegio'                => 'colegio_procedencia',
                'Titulo Bachiller'       => 'titulo_bachiller',
                'Carrera Primera Opcion' => 'carrera_primera_opcion',
                'Carrera Segunda Opcion' => 'carrera_segunda_opcion',
            ];

            // Encabezados por defecto para archivos sin cabecera (columnas en orden conocido)
            $encabezadosPorDefecto = [
                'CI', 'Nombre', 'Apellido', 'Fecha Nacimiento', 'Sexo',
                'Direccion', 'Telefono', 'Email', 'Ciudad', 'Colegio',
                'Titulo Bachiller', 'Carrera Primera Opcion', 'Carrera Segunda Opcion',
            ];

            // Buscar la fila que contiene 'CI' (puede haber filas de banner/instrucciones antes)
            $headerRowIndex = null;
            foreach ($filas as $i => $fila) {
                $valores = array_map(fn($v) => trim((string)($v ?? '')), $fila);
                if (in_array('CI', $valores)) {
                    $headerRowIndex = $i;
                    break;
                }
            }

            if ($headerRowIndex !== null) {
                // Hay fila de encabezados explícita
                $encabezados = array_map(fn($v) => trim((string)($v ?? '')), $filas[$headerRowIndex]);

                // Detectar si las filas siguientes son descripción/ejemplo (plantilla descargada)
                $primeraCeldaSiguiente = trim((string)($filas[$headerRowIndex + 1][0] ?? ''));
                if (!empty($primeraCeldaSiguiente) && !is_numeric($primeraCeldaSiguiente)) {
                    // Es la plantilla: fila descripción + fila ejemplo → saltar 2 filas
                    $dataStartIndex = $headerRowIndex + 3;
                } else {
                    $dataStartIndex = $headerRowIndex + 1;
                }
            } else {
                // No hay encabezados: el archivo empieza directo con datos
                // Detectar por si la primera celda es un número (CI)
                $primerValor = trim((string)($filas[0][0] ?? ''));
                if (is_numeric($primerValor)) {
                    $encabezados = $encabezadosPorDefecto;
                    $dataStartIndex = 0; // Todas las filas son datos
                } else {
                    // Usar fila 0 como encabezados como último recurso
                    $encabezados = array_map(fn($v) => trim((string)($v ?? '')), $filas[0]);
                    $dataStartIndex = 1;
                }
            }

            $datos = [];
            $numEncabezados = count($encabezados);

            foreach (array_slice($filas, $dataStartIndex) as $fila) {
                // Ignorar filas completamente vacías
                if (empty(array_filter($fila, fn($v) => $v !== null && $v !== ''))) continue;

                // Normalizar longitud de fila
                $fila = array_pad($fila, $numEncabezados, null);
                $fila = array_slice($fila, 0, $numEncabezados);

                $filaOriginal = array_combine($encabezados, $fila);
                $filaMapeada = [];
                foreach ($filaOriginal as $encabezado => $valor) {
                    if ($encabezado === '') continue; // ignorar columnas sin encabezado
                    $clave = $mapeo[$encabezado] ?? strtolower(str_replace(' ', '_', $encabezado));
                    if (in_array($clave, ['fecha_nacimiento'])) {
                        $valor = $this->convertirFecha((string)($valor ?? ''));
                    } else {
                        $valor = $valor !== null ? trim((string)$valor) : '';
                    }
                    $filaMapeada[$clave] = $valor;
                }
                $datos[] = $filaMapeada;
            }

            return $datos;
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * Validar y guardar postulante (retorna true o mensaje de error)
     */
    private function validarYGuardarPostulante($fila)
    {
        try {
            // Validar campos requeridos y limpiar espacios
            $ci = trim($fila['ci'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            $apellido = trim($fila['apellido'] ?? '');
            
            if (empty($ci)) return "CI vacío";
            if (empty($nombre)) return "Nombre vacío";
            if (empty($apellido)) return "Apellido vacío";

            // Verificar si existe - si existe, reutilizar; si no, crear
            $persona = DB::table('persona')->where('ci', $ci)->first();
            if ($persona) {
                $idPersona = $persona->id;
            } else {
                // Crear persona nueva
                $idPersona = DB::table('persona')->insertGetId([
                    'ci' => $ci,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'fecha_nacimiento' => $fila['fecha_nacimiento'] ?? null,
                    'sexo' => trim($fila['sexo'] ?? 'M'),
                    'direccion' => trim($fila['direccion'] ?? ''),
                    'telefono' => trim($fila['telefono'] ?? '') ?: null,
                    'correo_electronico' => trim($fila['email'] ?? ''),
                    'ciudad' => trim($fila['ciudad'] ?? ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Verificar si ya existe un postulante activo con esta persona (ignorar eliminados)
            $existePostulante = DB::table('postulante')
                ->where('id_persona', $idPersona)
                ->where('estado_asignacion', '<>', 'Eliminado')
                ->first();
            if ($existePostulante) {
                return "Postulante con CI {$ci} ya está registrado (Registro: {$existePostulante->registro})";
            }

            $carrera_primera_id = $this->buscarCarrera($fila['carrera_primera_opcion'] ?? '');
            $carrera_segunda_id = $this->buscarCarrera($fila['carrera_segunda_opcion'] ?? '');

            // Crear postulante y pago en una transacción atómica
            DB::transaction(function () use ($fila, $idPersona, $carrera_primera_id, $carrera_segunda_id) {
                $idPostulante = DB::table('postulante')->insertGetId([
                    'id_persona' => $idPersona,
                    'registro' => $this->generarCodigoRegistro(),
                    'colegio_procedencia' => trim($fila['colegio_procedencia'] ?? ''),
                    'ciudad' => trim($fila['ciudad'] ?? ''),
                    'titulo_bachiller' => trim($fila['titulo_bachiller'] ?? ''),
                    'otros_requisitos' => trim($fila['otros_requisitos'] ?? ''),
                    'codigo_inscripcion' => null,
                    'carrera_primera_opcion_id' => $carrera_primera_id,
                    'carrera_segunda_opcion_id' => $carrera_segunda_id,
                    'estado_asignacion' => 'Pendiente',
                ]);

                DB::table('pago')->insert([
                    'monto' => 150.00,
                    'fecha_pago' => now(),
                    'id_postulante' => $idPostulante,
                    'estado' => 'Pendiente',
                    'metodo_pago' => 'Otra',
                    'comprobante' => 'IMP-' . strtoupper(substr(md5(uniqid()), 0, 10)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return true;
        } catch (\Exception $e) {
            return "Excepción: " . $e->getMessage();
        }
    }

    /**
     * Normaliza texto: minúsculas, sin acentos, sin puntuación, espacios simples
     */
    private function normalizarTexto($texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = str_replace(
            ['á','é','í','ó','ú','ü','ñ'],
            ['a','e','i','o','u','u','n'],
            $texto
        );
        $texto = preg_replace('/[^a-z0-9\s]/i', ' ', $texto);
        return preg_replace('/\s+/', ' ', trim($texto));
    }

    /**
     * Busca una carrera por nombre con coincidencia flexible (exacta → parcial → palabras clave)
     */
    private function buscarCarrera($nombre): ?int
    {
        $nombre = trim((string)$nombre);
        if ($nombre === '') return null;

        // 1. Por código numérico
        if (is_numeric($nombre)) {
            $c = DB::table('carrera')->where('codigo', (int)$nombre)->first();
            if ($c) return $c->codigo;
        }

        // 2. Coincidencia exacta
        $c = DB::table('carrera')->where('nombre_carrera', $nombre)->first();
        if ($c) return $c->codigo;

        // 3. Coincidencia sin distinción de mayúsculas
        $c = DB::table('carrera')
            ->whereRaw('LOWER(TRIM(nombre_carrera)) = ?', [strtolower($nombre)])
            ->first();
        if ($c) return $c->codigo;

        // 4. Búsqueda normalizada por palabras clave
        $busqueda = $this->normalizarTexto($nombre);
        $palabras = array_filter(explode(' ', $busqueda), fn($p) => strlen($p) >= 3);

        if (empty($palabras)) return null;

        $carreras = DB::table('carrera')->get();
        $mejorScore = 0;
        $mejorId    = null;

        foreach ($carreras as $carrera) {
            $nombreNorm = $this->normalizarTexto($carrera->nombre_carrera);

            // Contiene o es contenido
            if (str_contains($nombreNorm, $busqueda) || str_contains($busqueda, $nombreNorm)) {
                return $carrera->codigo;
            }

            // Prefijo de cada palabra significativa (primeras 4 letras)
            $palabrasCarrera = explode(' ', $nombreNorm);
            $score = 0;
            foreach ($palabras as $palabra) {
                $prefijo = substr($palabra, 0, min(4, strlen($palabra)));
                foreach ($palabrasCarrera as $pc) {
                    if (str_starts_with($pc, $prefijo)) { $score++; break; }
                }
            }
            if ($score > $mejorScore) {
                $mejorScore = $score;
                $mejorId    = $carrera->codigo;
            }
        }

        // Para 1 palabra significativa exigir score≥1; para 2+ exigir score≥2
        $umbral = count($palabras) >= 2 ? 2 : 1;
        return ($mejorScore >= $umbral) ? $mejorId : null;
    }

    /**
     * Generar código de registro único
     */
    private function generarCodigoRegistro()
    {
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return "REG" . $timestamp . $random;
    }

    /**
     * Validar y guardar calificación
     */
    private function validarYGuardarCalificacion($fila)
    {
        try {
            // Implementar validación y guardado de calificaciones
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cancelar importación
     */
    public function cancelarImportacion($id)
    {
        try {
            $importacion = ImportacionMasiva::findOrFail($id);

            if ($importacion->estado === 'Completado' || $importacion->estado === 'Error fatal') {
                return response()->json(['error' => 'No se puede cancelar una importación completada'], 400);
            }

            $importacion->update(['estado' => 'Cancelado']);

            BitacoraService::registrar(
                "Importación {$id} cancelada por usuario",
                request()->ip(),
                Auth::id()
            );

            return response()->json(['message' => 'Importación cancelada']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Descargar plantilla Excel para Postulantes
     */
    public function descargarPlantilla(Request $request)
    {
        $columnas = [
            'CI'                     => 'Cédula de identidad (ej: 12345678)',
            'Nombre'                 => 'Nombre(s) del postulante',
            'Apellido'               => 'Apellido(s) del postulante',
            'Fecha Nacimiento'       => 'Formato DD/MM/YYYY (ej: 15/03/2005)',
            'Sexo'                   => 'M o F',
            'Direccion'              => 'Dirección de residencia',
            'Telefono'               => 'Número de teléfono (opcional)',
            'Email'                  => 'Correo electrónico',
            'Ciudad'                 => 'Ciudad de residencia',
            'Colegio'                => 'Nombre del colegio de procedencia',
            'Titulo Bachiller'       => 'Título de bachiller',
            'Carrera Primera Opcion' => 'Nombre exacto de la carrera (primera opción)',
            'Carrera Segunda Opcion' => 'Nombre exacto de la carrera (segunda opción, opcional)',
        ];

        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Postulantes');

        // Fila 1: instrucciones
        $hoja->mergeCells('A1:M1');
        $hoja->setCellValue('A1', 'PLANTILLA DE IMPORTACIÓN MASIVA - POSTULANTES | Complete todos los campos obligatorios. No elimine ni cambie los encabezados.');
        $hoja->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $hoja->getRowDimension(1)->setRowHeight(25);

        // Fila 2: encabezados
        $col = 1;
        foreach (array_keys($columnas) as $encabezado) {
            $celda = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2';
            $hoja->setCellValue($celda, $encabezado);
            $col++;
        }
        $hoja->getStyle('A2:M2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $hoja->getRowDimension(2)->setRowHeight(20);

        // Fila 3: descripciones como referencia
        $col = 1;
        foreach (array_values($columnas) as $desc) {
            $celda = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '3';
            $hoja->setCellValue($celda, $desc);
            $col++;
        }
        $hoja->getStyle('A3:M3')->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['rgb' => '6B7280'], 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $hoja->getRowDimension(3)->setRowHeight(18);

        // Fila 4: fila de ejemplo
        $ejemplo = ['12345678', 'Juan', 'Pérez', '15/03/2005', 'M', 'Av. Principal 123', '77712345', 'juan@correo.com', 'Santa Cruz', 'Colegio Nacional', 'Bachiller en Humanidades', 'Ingeniería Informática', 'Ingeniería Industrial'];
        $col = 1;
        foreach ($ejemplo as $valor) {
            $celda = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '4';
            $hoja->setCellValue($celda, $valor);
            $col++;
        }
        $hoja->getStyle('A4:M4')->applyFromArray([
            'font'      => ['color' => ['rgb' => '374151']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1FAE5']]],
        ]);

        // Bordes para todas las filas de datos (5-104 vacías para que el usuario llene)
        $hoja->getStyle('A5:M104')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
        ]);

        // Anchos de columna automáticos
        $anchos = [12, 15, 15, 18, 8, 25, 14, 28, 15, 28, 25, 28, 28];
        foreach ($anchos as $i => $ancho) {
            $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $hoja->getColumnDimension($letra)->setWidth($ancho);
        }

        // Congelar filas de encabezados
        $hoja->freezePane('A5');

        // Generar y descargar
        $writer = new XlsxWriter($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'plantilla_') . '.xlsx';
        $writer->save($tmpFile);

        return response()->download($tmpFile, 'plantilla_postulantes.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
