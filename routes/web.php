<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::post('/contactar-coordinadores', [App\Http\Controllers\ContactoController::class, 'enviar'])->name('contacto.enviar');

Route::get('/postularse', [App\Http\Controllers\PostulacionController::class, 'index'])->name('postulacion.form');
Route::post('/postularse', [App\Http\Controllers\PostulacionController::class, 'store'])->name('postulacion.store');
Route::get('/postularse/exito', [App\Http\Controllers\PostulacionController::class, 'success'])->name('postulacion.success');
Route::get('/postularse/ingresar', [App\Http\Controllers\PostulacionController::class, 'showLogin'])->name('postulacion.login');
Route::post('/postularse/ingresar', [App\Http\Controllers\PostulacionController::class, 'login'])->name('postulacion.login.submit');

// Rutas de recuperación de contraseña (sin autenticación)
Route::post('/postularse/solicitar-recuperacion', [App\Http\Controllers\PostulacionController::class, 'requestPasswordRecovery'])->name('postulacion.requestPasswordRecovery');
Route::post('/postularse/verificar-codigo-recuperacion', [App\Http\Controllers\PostulacionController::class, 'verifyRecoveryCode'])->name('postulacion.verifyRecoveryCode');
Route::post('/postularse/restablecer-contraseña', [App\Http\Controllers\PostulacionController::class, 'resetPassword'])->name('postulacion.resetPassword');

// Rutas protegidas - SessionCheck middleware verifica automáticamente por nombre de ruta
Route::get('/postularse/entrada', [App\Http\Controllers\PostulacionController::class, 'dashboard'])->name('postulacion.dashboard');
Route::post('/postularse/actualizar-perfil', [App\Http\Controllers\PostulacionController::class, 'actualizarPerfil'])->name('postulacion.actualizarPerfil');

// Endpoint AJAX para validar sesión (sin middleware session.check para devolver 401 correctamente)
Route::get('/api/validar-sesion', [App\Http\Controllers\PostulacionController::class, 'validarSesionAjax'])->name('api.validar-sesion');

// Endpoint AJAX para obtener CI del postulante por su registro (sin autenticación)
Route::get('/api/postulante-ci/{registro}', [App\Http\Controllers\PostulacionController::class, 'getPostulanteCi'])->name('api.postulante-ci');
Route::get('/api/docente-ci/{registro}', [App\Http\Controllers\PostulacionController::class, 'getDocenteCi'])->name('api.docente-ci');
Route::get('/api/administrativo-ci/{registro}', [App\Http\Controllers\PostulacionController::class, 'getAdministrativoCi'])->name('api.administrativo-ci');
Route::get('/api/coordinador-ci/{registro}', [App\Http\Controllers\PostulacionController::class, 'getCoordinadorCi'])->name('api.coordinador-ci');
Route::get('/api/decano-ci/{registro}', [App\Http\Controllers\PostulacionController::class, 'getDecanoCi'])->name('api.decano-ci');

Route::get('/postularse/logout', [App\Http\Controllers\PostulacionController::class, 'logout'])->name('postulacion.logout');

// Rutas de pago con PayPal
Route::post('/postulacion/create-payment', [App\Http\Controllers\PaymentController::class, 'createPayment'])->name('payment.create');
Route::get('/postulacion/success', [App\Http\Controllers\PaymentController::class, 'executePayment'])->name('payment.success');
Route::get('/postulacion/cancel', [App\Http\Controllers\PaymentController::class, 'cancelPayment'])->name('payment.cancel');

// Pago físico
Route::post('/postulacion/create-physical-payment', [App\Http\Controllers\PaymentController::class, 'createPhysicalPayment'])->name('payment.physical');

Route::get('/decano', function () {
    $datos = DB::table('decano')->get();
    return $datos;
});

Route::get('/grupo', function () {
    $datos = DB::table('grupo')->get();
    return $datos;
});

Route::get('/{tabla}', function ($tabla) {
    $allowedTables = [
        'persona', 'rol', 'privilegio', 'facultad', 'horario', 'aula',
        'gestion_academica', 'carrera', 'materia', 'cupo_carrera', 'pago',
        'docente', 'decano', 'coordinador', 'administrativo', 'postulante',
        'grupo', 'rol_grupo', 'inscripcion', 'asistencia', 'pasarela_pago', 'documentos',
        'carga_horaria_docente', 'examen', 'calificacion',
        'configuracion_porcentajes', 'reporte', 'bitacora', 'lote_usuarios',
        'estadistica', 'control_asignacion_carrera',
    ];

    if (!in_array($tabla, $allowedTables, true)) {
        abort(404);
    }

    return DB::table($tabla)->get();
})->where('tabla', '^(persona|rol|privilegio|facultad|horario|aula|gestion_academica|carrera|materia|cupo_carrera|pago|docente|decano|coordinador|administrativo|postulante|grupo|inscripcion|asistencia|pasarela_pago|documentos|carga_horaria_docente|examen|calificacion|configuracion_porcentajes|reporte|bitacora|lote_usuarios|estadistica|control_asignacion_carrera)$');

Route::get('/tablas', function () {
    return [
        'tablas' => [
            'persona', 'rol', 'privilegio', 'facultad', 'horario', 'aula',
            'gestion_academica', 'carrera', 'materia', 'cupo_carrera', 'pago',
            'docente', 'decano', 'coordinador', 'administrativo', 'postulante',
            'grupo', 'inscripcion', 'asistencia', 'pasarela_pago', 'documentos',
            'carga_horaria_docente', 'examen', 'calificacion',
            'configuracion_porcentajes', 'reporte', 'bitacora', 'lote_usuarios',
            'estadistica', 'control_asignacion_carrera',
        ],
    ];
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// CU02 - Gestionar Usuarios y Roles
Route::get('/cu02/usuarios-roles', [App\Http\Controllers\UsuarioRolController::class, 'index'])->name('cu02.index');
Route::post('/cu02/registrar-cuenta', [App\Http\Controllers\UsuarioRolController::class, 'registrarCuenta'])->name('cu02.registrar');
Route::get('/cu02/personas/{tipo}', [App\Http\Controllers\UsuarioRolController::class, 'obtenerPersonas'])->name('cu02.personas');
Route::delete('/cu02/persona/{tipo}/{personaId}', [App\Http\Controllers\UsuarioRolController::class, 'eliminarPersona'])->name('cu02.eliminar');
Route::patch('/cu02/persona/{tipo}/{personaId}', [App\Http\Controllers\UsuarioRolController::class, 'actualizarPersona'])->name('cu02.actualizar');
Route::post('/cu02/asignar-permisos', [App\Http\Controllers\UsuarioRolController::class, 'asignarPermisos'])->name('cu02.permisos');

// CU02 - FASE 2 MASIVOS
Route::post('/cu02/usuarios-masivos', [App\Http\Controllers\UsuarioRolController::class, 'crearUsuariosMasivos'])->name('cu02.usuarios-masivos');
Route::post('/cu02/personas-masivas', [App\Http\Controllers\UsuarioRolController::class, 'importarPersonasMasivas'])->name('cu02.personas-masivas');
Route::post('/cu02/permisos-masivos', [App\Http\Controllers\UsuarioRolController::class, 'asignarPermisosMasivos'])->name('cu02.permisos-masivos');
Route::get('/cu02/asistencia-periodo', [App\Http\Controllers\UsuarioRolController::class, 'calcularAsistenciaPeriodo'])->name('cu02.asistencia-periodo');

// API - Buscar postulante por registro (para re-postulación)
Route::get('/api/postulante/buscar', [App\Http\Controllers\CU03PostulanteController::class, 'buscarPorRegistro'])->name('api.postulante.buscar');

// CU03 - Gestionar Postulantes
Route::get('/cu03/gestionar-postulantes', [App\Http\Controllers\CU03PostulanteController::class, 'index'])->name('cu03.index');
Route::get('/cu03/postulantes', [App\Http\Controllers\CU03PostulanteController::class, 'getPostulantes'])->name('cu03.postulantes');
Route::post('/cu03/postulantes', [App\Http\Controllers\CU03PostulanteController::class, 'store'])->name('cu03.postulantes.store');
Route::patch('/cu03/postulante/{id}', [App\Http\Controllers\CU03PostulanteController::class, 'update'])->name('cu03.postulante.update');
Route::delete('/cu03/postulante/{id}', [App\Http\Controllers\CU03PostulanteController::class, 'destroy'])->name('cu03.postulante.destroy');

// CU04 - Seguimiento de Pagos y Validación
Route::get('/cu04/pagos-validacion', [App\Http\Controllers\CU04PagosController::class, 'index'])->name('cu04.index');
Route::get('/api/cu04/pagos', [App\Http\Controllers\CU04PagosController::class, 'getPagos'])->name('cu04.pagos.get');
Route::post('/api/cu04/pagos', [App\Http\Controllers\CU04PagosController::class, 'store'])->name('cu04.pagos.store');
Route::post('/api/cu04/pagos/{id}/validar', [App\Http\Controllers\CU04PagosController::class, 'validarPago'])->name('cu04.pagos.validar');
Route::patch('/api/cu04/pagos/{id}/estado', [App\Http\Controllers\CU04PagosController::class, 'updateEstado'])->name('cu04.pagos.estado');
Route::get('/api/cu04/pagos/reporte', [App\Http\Controllers\CU04PagosController::class, 'descargarReporte'])->name('cu04.pagos.reporte');
Route::get('/api/cu04/postulante/{id}/correo', [App\Http\Controllers\CU04PagosController::class, 'getCorreoPostulante'])->name('cu04.postulante.correo');

// CU15 - Importación Masiva de Datos (CSV/Excel)
Route::get('/cu15/importacion-masiva', [App\Http\Controllers\CU15ImportacionController::class, 'index'])->name('cu15.index');
Route::get('/api/cu15/historial', [App\Http\Controllers\CU15ImportacionController::class, 'getHistorial'])->name('cu15.historial');
Route::post('/api/cu15/importacion', [App\Http\Controllers\CU15ImportacionController::class, 'iniciarImportacion'])->name('cu15.iniciar');
Route::post('/api/cu15/importacion/{id}/procesar', [App\Http\Controllers\CU15ImportacionController::class, 'procesarImportacion'])->name('cu15.procesar');
Route::post('/api/cu15/importacion/{id}/cancelar', [App\Http\Controllers\CU15ImportacionController::class, 'cancelarImportacion'])->name('cu15.cancelar');
Route::get('/api/cu15/plantilla', [App\Http\Controllers\CU15ImportacionController::class, 'descargarPlantilla'])->name('cu15.plantilla');

// CU05 - Registrar Calificaciones
Route::get('/cu05/registrar-calificaciones', [App\Http\Controllers\CU05RegistrarCalificacionesController::class, 'index'])->name('cu05.index');
Route::post('/cu05/registrar-calificaciones', [App\Http\Controllers\CU05RegistrarCalificacionesController::class, 'store'])->name('cu05.store');
Route::patch('/cu05/calificacion/{id}', [App\Http\Controllers\CU05RegistrarCalificacionesController::class, 'update'])->name('cu05.calificacion.update');
Route::delete('/cu05/calificacion/{id}', [App\Http\Controllers\CU05RegistrarCalificacionesController::class, 'destroy'])->name('cu05.calificacion.destroy');

// CU06 - Calcular Promedio
Route::get('/cu06/calcular-promedio', [App\Http\Controllers\CU06CalcularPromedioController::class, 'index'])->name('cu06.index');
Route::post('/cu06/recalcular-promedios', [App\Http\Controllers\CU06CalcularPromedioController::class, 'recalculate'])->name('cu06.recalculate');

// CU07 - Configurar Cupos
Route::get('/cu07/configurar-cupos', [App\Http\Controllers\CU07ConfigurarCuposController::class, 'index'])->name('cu07.index');
Route::post('/cu07/configurar-cupos', [App\Http\Controllers\CU07ConfigurarCuposController::class, 'store'])->name('cu07.store');
Route::put('/cu07/configurar-cupos/{cupo_codigo}', [App\Http\Controllers\CU07ConfigurarCuposController::class, 'update'])->name('cu07.update');
Route::delete('/cu07/configurar-cupos/{cupo_codigo}', [App\Http\Controllers\CU07ConfigurarCuposController::class, 'destroy'])->name('cu07.destroy');
Route::get('/cu07/cupo/{cupo_codigo}/aceptados', [App\Http\Controllers\CU07ConfigurarCuposController::class, 'aceptados'])->name('cu07.aceptados');

// CU08 - Asignar Cupos
Route::get('/cu08/asignar-cupos', [App\Http\Controllers\CU08AsignarCuposController::class, 'index'])->name('cu08.index');
Route::post('/cu08/asignar-cupos', [App\Http\Controllers\CU08AsignarCuposController::class, 'assign'])->name('cu08.assign');

// CU09 - Calcular Grupos
Route::get('/cu09/calcular-grupos', [App\Http\Controllers\CU09CalcularGruposController::class, 'index'])->name('cu09.index');
Route::post('/cu09/calcular-grupos', [App\Http\Controllers\CU09CalcularGruposController::class, 'calcularYCrearGrupos'])->name('cu09.calcular');
Route::patch('/cu09/grupo/{codigo}', [App\Http\Controllers\CU09CalcularGruposController::class, 'update'])->name('cu09.grupo.update');
Route::delete('/cu09/grupo/{codigo}', [App\Http\Controllers\CU09CalcularGruposController::class, 'destroy'])->name('cu09.grupo.destroy');
Route::get('/cu09/grupo/{codigo}/inscritos', [App\Http\Controllers\CU09CalcularGruposController::class, 'inscritos'])->name('cu09.grupo.inscritos');
Route::post('/cu09/validar-conflicto', [App\Http\Controllers\CU09CalcularGruposController::class, 'validateConflict'])->name('cu09.validar-conflicto');

// CU10 - Asignar Postulantes
Route::get('/cu10/asignar-postulantes', [App\Http\Controllers\CU10AsignarPostulantesController::class, 'index'])->name('cu10.index');
Route::post('/cu10/asignar-postulantes', [App\Http\Controllers\CU10AsignarPostulantesController::class, 'assign'])->name('cu10.assign');
Route::patch('/cu10/postulante/{id}', [App\Http\Controllers\CU10AsignarPostulantesController::class, 'updatePostulante'])->name('cu10.postulante.update');
Route::delete('/cu10/postulante/{id}', [App\Http\Controllers\CU10AsignarPostulantesController::class, 'destroyPostulante'])->name('cu10.postulante.destroy');

// FASE 3 - Carreras, Materias, Exámenes, Estadísticas y Reportes
// Carreras
Route::get('/fase3/carreras', [App\Http\Controllers\CarreraController::class, 'index'])->name('fase3.carreras.index');
Route::post('/fase3/carreras', [App\Http\Controllers\CarreraController::class, 'store'])->name('fase3.carreras.store');
Route::post('/fase3/carreras-masivas', [App\Http\Controllers\CarreraController::class, 'crearMasivas'])->name('fase3.carreras.masivas');
Route::patch('/fase3/carreras/{id}', [App\Http\Controllers\CarreraController::class, 'update'])->name('fase3.carreras.update');
Route::delete('/fase3/carreras/{id}', [App\Http\Controllers\CarreraController::class, 'destroy'])->name('fase3.carreras.destroy');

// Materias
Route::get('/fase3/materias', [App\Http\Controllers\MateriaController::class, 'index'])->name('fase3.materias.index');
Route::get('/fase3/materias/carrera/{id_carrera}', [App\Http\Controllers\MateriaController::class, 'porCarrera'])->name('fase3.materias.carrera');
Route::post('/fase3/materias', [App\Http\Controllers\MateriaController::class, 'store'])->name('fase3.materias.store');
Route::post('/fase3/materias-masivas', [App\Http\Controllers\MateriaController::class, 'crearMasivas'])->name('fase3.materias.masivas');
Route::patch('/fase3/materias/{id}', [App\Http\Controllers\MateriaController::class, 'update'])->name('fase3.materias.update');
Route::delete('/fase3/materias/{id}', [App\Http\Controllers\MateriaController::class, 'destroy'])->name('fase3.materias.destroy');

// Exámenes
Route::get('/fase3/examenes', [App\Http\Controllers\ExamenController::class, 'index'])->name('fase3.examenes.index');
Route::get('/fase3/examenes/postulante/{registro_postulante}', [App\Http\Controllers\ExamenController::class, 'examenesPostulante'])->name('fase3.examenes.postulante');
Route::post('/fase3/examenes', [App\Http\Controllers\ExamenController::class, 'store'])->name('fase3.examenes.store');
Route::post('/fase3/examenes-masivos', [App\Http\Controllers\ExamenController::class, 'crearMasivos'])->name('fase3.examenes.masivos');
Route::patch('/fase3/examenes/{id}', [App\Http\Controllers\ExamenController::class, 'update'])->name('fase3.examenes.update');
Route::delete('/fase3/examenes/{id}', [App\Http\Controllers\ExamenController::class, 'destroy'])->name('fase3.examenes.destroy');

// Estadísticas
Route::get('/fase3/estadisticas', [App\Http\Controllers\EstadisticaController::class, 'index'])->name('fase3.estadisticas.index');
Route::get('/fase3/estadisticas/carrera/{id_carrera}', [App\Http\Controllers\EstadisticaController::class, 'porCarrera'])->name('fase3.estadisticas.carrera');
Route::get('/fase3/estadisticas/periodo/{periodo}', [App\Http\Controllers\EstadisticaController::class, 'porPeriodo'])->name('fase3.estadisticas.periodo');
Route::post('/fase3/estadisticas/calcular/{id_carrera}', [App\Http\Controllers\EstadisticaController::class, 'calcularCarrera'])->name('fase3.estadisticas.calcular');
Route::post('/fase3/estadisticas/calcular-periodo', [App\Http\Controllers\EstadisticaController::class, 'calcularPeriodo'])->name('fase3.estadisticas.periodo.calcular');

// Reportes
Route::get('/fase3/reportes', [App\Http\Controllers\ReporteController::class, 'index'])->name('fase3.reportes.index');
Route::get('/fase3/reportes/persona/{id_persona}', [App\Http\Controllers\ReporteController::class, 'porPersona'])->name('fase3.reportes.persona');
Route::get('/fase3/reportes/tipo/{tipo_reporte}', [App\Http\Controllers\ReporteController::class, 'porTipo'])->name('fase3.reportes.tipo');
Route::post('/fase3/reportes', [App\Http\Controllers\ReporteController::class, 'store'])->name('fase3.reportes.store');
Route::post('/fase3/reportes/generar', [App\Http\Controllers\ReporteController::class, 'generar'])->name('fase3.reportes.generar');
Route::patch('/fase3/reportes/{id}', [App\Http\Controllers\ReporteController::class, 'update'])->name('fase3.reportes.update');
Route::delete('/fase3/reportes/{id}', [App\Http\Controllers\ReporteController::class, 'destroy'])->name('fase3.reportes.destroy');

// CU18 - Auditoría de Operaciones mediante Bitácora (Solo para Decano)
Route::get('/cu18/auditoria', [App\Http\Controllers\CU18AuditoriaController::class, 'index'])
    ->name('cu18.index')
    ->middleware([\App\Http\Middleware\SessionCheck::class]);
Route::get('/cu18/auditoria/stream', [App\Http\Controllers\CU18AuditoriaController::class, 'stream'])
    ->name('cu18.stream')
    ->middleware([\App\Http\Middleware\SessionCheck::class]);

// CU11 - Asignación de Recursos Físicos
Route::get('/cu11/recursos-fisicos', [App\Http\Controllers\CU11RecursosFisicosController::class, 'index'])->name('cu11.index');
Route::patch('/cu11/grupo/{codigo}', [App\Http\Controllers\CU11RecursosFisicosController::class, 'update'])->name('cu11.grupo.update');

// CU12 - Programación de Carga Horaria Docente
Route::get('/cu12/carga-horaria', [App\Http\Controllers\CU12CargaHorariaController::class, 'index'])->name('cu12.index');
Route::post('/cu12/asignar-docente', [App\Http\Controllers\CU12CargaHorariaController::class, 'asignarDocente'])->name('cu12.asignar');
Route::delete('/cu12/asignacion/{grupo_codigo}', [App\Http\Controllers\CU12CargaHorariaController::class, 'eliminarAsignacion'])->name('cu12.eliminar-asignacion');

// CU13 - Gestión de Asistencia
Route::get('/cu13/asistencia', [App\Http\Controllers\CU13AsistenciaController::class, 'index'])->name('cu13.index');
Route::post('/cu13/asistencia', [App\Http\Controllers\CU13AsistenciaController::class, 'store'])->name('cu13.store');
Route::delete('/cu13/asistencia/{codigo}', [App\Http\Controllers\CU13AsistenciaController::class, 'destroy'])->name('cu13.destroy');

// CU14 - Gestión de Calendario Académico (CUP)
Route::get('/cu14/calendario', [App\Http\Controllers\CU14CalendarioController::class, 'index'])->name('cu14.index');
Route::post('/cu14/gestion', [App\Http\Controllers\CU14CalendarioController::class, 'storeGestion'])->name('cu14.gestion.store');
Route::patch('/cu14/gestion/{codigo}', [App\Http\Controllers\CU14CalendarioController::class, 'updateGestion'])->name('cu14.gestion.update');
Route::delete('/cu14/gestion/{codigo}', [App\Http\Controllers\CU14CalendarioController::class, 'destroyGestion'])->name('cu14.gestion.destroy');
Route::post('/cu14/horario', [App\Http\Controllers\CU14CalendarioController::class, 'storeHorario'])->name('cu14.horario.store');
Route::delete('/cu14/horario/{codigo}', [App\Http\Controllers\CU14CalendarioController::class, 'destroyHorario'])->name('cu14.horario.destroy');
Route::post('/cu14/actividad', [App\Http\Controllers\CU14CalendarioController::class, 'storeActividad'])->name('cu14.actividad.store');
Route::patch('/cu14/actividad/{id}', [App\Http\Controllers\CU14CalendarioController::class, 'updateActividad'])->name('cu14.actividad.update');
Route::delete('/cu14/actividad/{id}', [App\Http\Controllers\CU14CalendarioController::class, 'destroyActividad'])->name('cu14.actividad.destroy');

// CU16 - Gestión de Reportes
Route::get('/cu16/reportes', [App\Http\Controllers\CU16ReportesController::class, 'index'])->name('cu16.index');
Route::post('/cu16/reportes', [App\Http\Controllers\CU16ReportesController::class, 'store'])->name('cu16.store');
Route::post('/cu16/exportar', [App\Http\Controllers\CU16ReportesController::class, 'exportar'])->name('cu16.exportar');
Route::delete('/cu16/reportes/{codigo}', [App\Http\Controllers\CU16ReportesController::class, 'destroy'])->name('cu16.destroy');

// CU17 - Evaluación de Desempeño
Route::get('/cu17/desempeno', [App\Http\Controllers\CU17DesempenoController::class, 'index'])->name('cu17.index');
Route::post('/cu17/calcular', [App\Http\Controllers\CU17DesempenoController::class, 'calcular'])->name('cu17.calcular');

// Evaluaciones de Docentes / Cursos
Route::get('/evaluaciones', [App\Http\Controllers\EvaluacionController::class, 'index'])->name('evaluaciones.index');
Route::post('/evaluaciones', [App\Http\Controllers\EvaluacionController::class, 'store'])->name('evaluaciones.store');
Route::patch('/evaluaciones/{id}/toggle', [App\Http\Controllers\EvaluacionController::class, 'toggle'])->name('evaluaciones.toggle');
Route::delete('/evaluaciones/{id}', [App\Http\Controllers\EvaluacionController::class, 'eliminar'])->name('evaluaciones.eliminar');
Route::get('/evaluaciones/{id}/responder', [App\Http\Controllers\EvaluacionController::class, 'responder'])->name('evaluaciones.responder');
Route::post('/evaluaciones/{id}/responder', [App\Http\Controllers\EvaluacionController::class, 'guardarRespuesta'])->name('evaluaciones.guardar');
Route::get('/evaluaciones/{id}/resultados', [App\Http\Controllers\EvaluacionController::class, 'resultados'])->name('evaluaciones.resultados');
Route::get('/api/evaluaciones/pendientes', [App\Http\Controllers\EvaluacionController::class, 'pendientes'])->name('api.evaluaciones.pendientes');

require __DIR__.'/auth.php';

Route::redirect('/register', '/postularse');
