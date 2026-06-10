<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Servicio para operaciones masivas y procedimientos almacenados
 * Encapsula el uso de funciones y PA de la base de datos
 */
class DatabaseOperationsService
{
    /**
     * Procesar inscripciones masivas para una gestión
     *
     * @param int $gestion_academica_id
     * @return array Resultado con total, exitosas y fallidas
     * @throws Exception
     */
    public function procesarInscripcionesMasivas(int $gestion_academica_id): array
    {
        try {
            $result = DB::select('CALL sp_procesar_inscripciones_masivas(?, ?, ?, ?)', [
                $gestion_academica_id,
                0, // OUT p_total_procesadas
                0, // OUT p_exitosas
                0  // OUT p_fallidas
            ]);

            if (empty($result)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo ejecutar el procedimiento',
                    'total' => 0,
                    'exitosas' => 0,
                    'fallidas' => 0
                ];
            }

            $datos = (array) $result[0];

            return [
                'success' => true,
                'message' => 'Inscripciones procesadas correctamente',
                'total' => $datos['p_total_procesadas'] ?? 0,
                'exitosas' => $datos['p_exitosas'] ?? 0,
                'fallidas' => $datos['p_fallidas'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al procesar inscripciones: ' . $e->getMessage(),
                'total' => 0,
                'exitosas' => 0,
                'fallidas' => 0
            ];
        }
    }

    /**
     * Recalcular promedios para una gestión académica
     *
     * @param int $gestion_academica_id
     * @return array Resultado con total, aprobadas y reprobadas
     * @throws Exception
     */
    public function recalcularPromediosGestion(int $gestion_academica_id): array
    {
        try {
            $result = DB::select('CALL sp_recalcular_promedios_gestion(?, ?, ?, ?)', [
                $gestion_academica_id,
                0, // OUT p_total_calificaciones
                0, // OUT p_aprobadas
                0  // OUT p_reprobadas
            ]);

            if (empty($result)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo ejecutar el procedimiento',
                    'total' => 0,
                    'aprobadas' => 0,
                    'reprobadas' => 0
                ];
            }

            $datos = (array) $result[0];

            return [
                'success' => true,
                'message' => 'Promedios recalculados correctamente',
                'total' => $datos['p_total_calificaciones'] ?? 0,
                'aprobadas' => $datos['p_aprobadas'] ?? 0,
                'reprobadas' => $datos['p_reprobadas'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al recalcular promedios: ' . $e->getMessage(),
                'total' => 0,
                'aprobadas' => 0,
                'reprobadas' => 0
            ];
        }
    }

    /**
     * Procesar pagos pendientes
     *
     * @return array Resultado con total, completados y fallidos
     * @throws Exception
     */
    public function procesarPagosPendientes(): array
    {
        try {
            $result = DB::select('CALL sp_procesar_pagos_pendientes(?, ?, ?)', [
                0, // OUT p_total_procesados
                0, // OUT p_completados
                0  // OUT p_fallidos
            ]);

            if (empty($result)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo ejecutar el procedimiento',
                    'total' => 0,
                    'completados' => 0,
                    'fallidos' => 0
                ];
            }

            $datos = (array) $result[0];

            return [
                'success' => true,
                'message' => 'Pagos procesados correctamente',
                'total' => $datos['p_total_procesados'] ?? 0,
                'completados' => $datos['p_completados'] ?? 0,
                'fallidos' => $datos['p_fallidos'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al procesar pagos: ' . $e->getMessage(),
                'total' => 0,
                'completados' => 0,
                'fallidos' => 0
            ];
        }
    }

    /**
     * Resetear cupos para una gestión
     *
     * @param int $gestion_academica_id
     * @return array Resultado con total de carreras reseteadas
     * @throws Exception
     */
    public function resetearCuposGestion(int $gestion_academica_id): array
    {
        try {
            $result = DB::select('CALL sp_resetear_cupos_gestion(?, ?)', [
                $gestion_academica_id,
                0 // OUT p_total_carreras
            ]);

            if (empty($result)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo ejecutar el procedimiento',
                    'total_carreras' => 0
                ];
            }

            $datos = (array) $result[0];

            return [
                'success' => true,
                'message' => 'Cupos reseteados correctamente',
                'total_carreras' => $datos['p_total_carreras'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al resetear cupos: ' . $e->getMessage(),
                'total_carreras' => 0
            ];
        }
    }

    /**
     * Calcular promedio de 3 notas
     * Usa la función fn_calcular_promedio de la BD
     *
     * @param int $nota1
     * @param int $nota2
     * @param int $nota3
     * @return float Promedio calculado
     */
    public function calcularPromedio(int $nota1, int $nota2, int $nota3): float
    {
        try {
            $result = DB::select('SELECT fn_calcular_promedio(?, ?, ?) as promedio', [
                $nota1, $nota2, $nota3
            ]);

            if (empty($result)) {
                return 0.0;
            }

            return (float) $result[0]->promedio;
        } catch (Exception $e) {
            \Log::error('Error al calcular promedio: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Validar un pago
     * Usa la función fn_validar_pago de la BD
     *
     * @param float $monto
     * @param string $fecha_pago (formato YYYY-MM-DD)
     * @return bool
     */
    public function validarPago(float $monto, string $fecha_pago): bool
    {
        try {
            $result = DB::select('SELECT fn_validar_pago(?, ?::DATE) as valido', [
                $monto, $fecha_pago
            ]);

            if (empty($result)) {
                return false;
            }

            return (bool) $result[0]->valido;
        } catch (Exception $e) {
            \Log::error('Error al validar pago: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Detectar conflicto de grupo por horario
     * Usa la función fn_detectar_conflicto_grupo de la BD
     *
     * @param int $codigo_horario
     * @param int|null $codigo_aula_id
     * @return bool True si hay conflicto
     */
    public function detectarConflictoGrupo(int $codigo_horario, ?int $codigo_aula_id = null): bool
    {
        try {
            if ($codigo_aula_id) {
                $result = DB::select('SELECT fn_detectar_conflicto_grupo(?, ?) as existe_conflicto', [
                    $codigo_horario, $codigo_aula_id
                ]);
            } else {
                $result = DB::select('SELECT fn_detectar_conflicto_grupo(?) as existe_conflicto', [
                    $codigo_horario
                ]);
            }

            if (empty($result)) {
                return false;
            }

            return (bool) $result[0]->existe_conflicto;
        } catch (Exception $e) {
            \Log::error('Error al detectar conflicto de grupo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar y decrementar cupo
     * Usa la función fn_validar_decrementar_cupo de la BD
     *
     * @param int $carrera_id
     * @param int $gestion_academica_id
     * @return bool True si el cupo fue decrementado exitosamente
     */
    public function validarDecrementarCupo(int $carrera_id, int $gestion_academica_id): bool
    {
        try {
            $result = DB::select('SELECT fn_validar_decrementar_cupo(?, ?) as decrementado', [
                $carrera_id, $gestion_academica_id
            ]);

            if (empty($result)) {
                return false;
            }

            return (bool) $result[0]->decrementado;
        } catch (Exception $e) {
            \Log::error('Error al decrementar cupo: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // FASE 2 - MÉTODOS PARA USUARIO, PERSONA, ASISTENCIA, ROL_GRUPO_PRIVILEGIO
    // =========================================================================

    /**
     * Validar si un email es único
     * Usa la función fn_validar_email_unico de la BD
     *
     * @param string $email
     * @return bool True si el email es único
     */
    public function validarEmailUnico(string $email): bool
    {
        try {
            $result = DB::select('SELECT fn_validar_email_unico(CAST(? AS VARCHAR)) as es_unico', [$email]);

            if (empty($result)) {
                return false;
            }

            return (bool) $result[0]->es_unico;
        } catch (Exception $e) {
            \Log::error('Error al validar email único: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar si un CI no es duplicado
     * Usa la función fn_validar_ci_no_duplicado de la BD
     *
     * @param string $ci
     * @return bool True si el CI es único
     */
    public function validarCiNoDuplicado(string $ci): bool
    {
        try {
            $result = DB::select('SELECT fn_validar_ci_no_duplicado(CAST(? AS VARCHAR)) as no_duplicado', [$ci]);

            if (empty($result)) {
                return false;
            }

            return (bool) $result[0]->no_duplicado;
        } catch (Exception $e) {
            \Log::error('Error al validar CI no duplicado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular porcentaje de asistencia para un postulante en un período
     * Usa la función fn_calcular_porcentaje_asistencia de la BD
     *
     * @param int $postulante_id
     * @param string $periodo_inicio (formato YYYY-MM-DD)
     * @param string $periodo_fin (formato YYYY-MM-DD)
     * @return float Porcentaje de asistencia (0-100)
     */
    public function calcularPorcentajeAsistencia(int $postulante_id, string $periodo_inicio, string $periodo_fin): float
    {
        try {
            $result = DB::select(
                'SELECT fn_calcular_porcentaje_asistencia(?, ?::DATE, ?::DATE) as porcentaje',
                [$postulante_id, $periodo_inicio, $periodo_fin]
            );

            if (empty($result)) {
                return 0.0;
            }

            return (float) $result[0]->porcentaje;
        } catch (Exception $e) {
            \Log::error('Error al calcular porcentaje de asistencia: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Validar si un código de permiso es válido (formato CU01-CU50)
     * Usa la función fn_validar_permiso_valido de la BD
     *
     * @param string $codigo_cu
     * @return bool
     */
    public function validarPermisoValido(string $codigo_cu): bool
    {
        try {
            $result = DB::select('SELECT fn_validar_permiso_valido(CAST(? AS VARCHAR)) as es_valido', [$codigo_cu]);

            if (empty($result)) {
                return false;
            }

            return (bool) $result[0]->es_valido;
        } catch (Exception $e) {
            \Log::error('Error al validar permiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear múltiples usuarios masivamente desde JSON
     * Usa el procedimiento sp_crear_usuarios_masivos de la BD
     *
     * @param string $json_usuarios JSON array con usuarios
     * @return array Resultado con total, exitosos y fallidos
     */
    public function crearUsuariosMasivos(string $json_usuarios): array
    {
        try {
            $result = DB::select('CALL sp_crear_usuarios_masivos(?, ?, ?, ?)', [
                $json_usuarios,
                0, // OUT total_insertados
                0, // OUT exitosos
                0  // OUT fallidos
            ]);

            if (empty($result)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo ejecutar el procedimiento',
                    'total' => 0,
                    'exitosos' => 0,
                    'fallidos' => 0
                ];
            }

            $datos = (array) $result[0];

            return [
                'success' => true,
                'message' => 'Usuarios creados correctamente',
                'total' => $datos['total_insertados'] ?? 0,
                'exitosos' => $datos['exitosos'] ?? 0,
                'fallidos' => $datos['fallidos'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear usuarios: ' . $e->getMessage(),
                'total' => 0,
                'exitosos' => 0,
                'fallidos' => 0
            ];
        }
    }

    /**
     * Importar múltiples personas masivamente desde JSON
     * Usa el procedimiento sp_importar_personas_masivas de la BD
     *
     * @param string $json_personas JSON array con personas
     * @return array Resultado con total, exitosos y fallidos
     */
    public function importarPersonasMasivas(string $json_personas): array
    {
        try {
            $result = DB::select('CALL sp_importar_personas_masivas(?, ?, ?, ?)', [
                $json_personas,
                0, // OUT total_importados
                0, // OUT exitosos
                0  // OUT fallidos
            ]);

            if (empty($result)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo ejecutar el procedimiento',
                    'total' => 0,
                    'exitosos' => 0,
                    'fallidos' => 0
                ];
            }

            $datos = (array) $result[0];

            return [
                'success' => true,
                'message' => 'Personas importadas correctamente',
                'total' => $datos['total_importados'] ?? 0,
                'exitosos' => $datos['exitosos'] ?? 0,
                'fallidos' => $datos['fallidos'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al importar personas: ' . $e->getMessage(),
                'total' => 0,
                'exitosos' => 0,
                'fallidos' => 0
            ];
        }
    }

    /**
     * Calcular asistencia para todos en un período
     * Usa el procedimiento sp_calcular_asistencia_periodo de la BD
     *
     * @param string $periodo_inicio (formato YYYY-MM-DD)
     * @param string $periodo_fin (formato YYYY-MM-DD)
     * @return array Tabla temporal con postulante_id y porcentaje_asistencia
     */
    public function calcularAsistenciaPeriodo(string $periodo_inicio, string $periodo_fin): array
    {
        try {
            DB::statement('CALL sp_calcular_asistencia_periodo(?::DATE, ?::DATE)', [
                $periodo_inicio,
                $periodo_fin
            ]);

            // Recuperar datos de la tabla temporal creada por el procedimiento
            $result = DB::select('SELECT * FROM temp_asistencia');

            return [
                'success' => true,
                'message' => 'Asistencia calculada correctamente',
                'datos' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al calcular asistencia: ' . $e->getMessage(),
                'datos' => []
            ];
        }
    }

    /**
     * Asignar permisos masivamente desde JSON
     * Usa el procedimiento sp_asignar_permisos_masivos de la BD
     *
     * @param string $json_asignaciones JSON array con asignaciones
     * @return array Resultado con total, exitosos y fallidos
     */
    public function asignarPermisosMasivos(string $json_asignaciones): array
    {
        try {
            $result = DB::select('CALL sp_asignar_permisos_masivos(?, ?, ?, ?)', [
                $json_asignaciones,
                0, // OUT total_asignados
                0, // OUT exitosos
                0  // OUT fallidos
            ]);

            if (empty($result)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo ejecutar el procedimiento',
                    'total' => 0,
                    'exitosos' => 0,
                    'fallidos' => 0
                ];
            }

            $datos = (array) $result[0];

            return [
                'success' => true,
                'message' => 'Permisos asignados correctamente',
                'total' => $datos['total_asignados'] ?? 0,
                'exitosos' => $datos['exitosos'] ?? 0,
                'fallidos' => $datos['fallidos'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al asignar permisos: ' . $e->getMessage(),
                'total' => 0,
                'exitosos' => 0,
                'fallidos' => 0
            ];
        }
    }
}
