<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Services\BitacoraService;

class PaymentController extends Controller
{
    private $paypalMode;
    private $paypalClientId;
    private $paypalSecret;
    private $paypalUrl;

    public function __construct()
    {
        $this->paypalMode = env('PAYPAL_MODE', 'sandbox');
        $this->paypalClientId = env('PAYPAL_CLIENT_ID');
        $this->paypalSecret = env('PAYPAL_SECRET');
        $this->paypalUrl = $this->paypalMode === 'sandbox' 
            ? 'https://api.sandbox.paypal.com'
            : 'https://api.paypal.com';
    }

    /**
     * Validar unicidad y opciones de carrera; devuelve array de observaciones.
     * Si $esRepostulacion = true, omite las validaciones de CI y título bachiller.
     */
    private function verificarDatos(array $data, ?int $excludePostulanteId = null, bool $esRepostulacion = false): array
    {
        $observaciones = [];

        if (!$esRepostulacion) {
            if (DB::table('persona')->where('ci', $data['ci'])->exists()) {
                $observaciones[] = 'El CI "' . $data['ci'] . '" ya se encuentra registrado en el sistema.';
            }

            $q = DB::table('postulante')->where('titulo_bachiller', $data['titulo_bachiller']);
            if ($excludePostulanteId) $q->where('id', '<>', $excludePostulanteId);
            if ($q->exists()) {
                $observaciones[] = 'El Nro. de Título Bachiller "' . $data['titulo_bachiller'] . '" ya se encuentra registrado en el sistema.';
            }
        }

        if (!empty($data['carrera_segunda_opcion_id']) &&
            $data['carrera_primera_opcion_id'] == $data['carrera_segunda_opcion_id']) {
            $observaciones[] = 'La primera y segunda opción de carrera no pueden ser la misma carrera.';
        }

        return $observaciones;
    }

    /**
     * Enviar correo de rechazo al postulante.
     */
    private function enviarCorreoRechazo(string $email, string $nombre, array $observaciones): void
    {
        try {
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
                function ($message) use ($email, $nombre) {
                    $message->to($email, $nombre)
                            ->subject('Observaciones en su postulación — Gestión Académica CUP');
                }
            );
        } catch (\Exception $e) {
            \Log::error('Error enviando correo de rechazo a ' . $email . ': ' . $e->getMessage());
        }
    }

    /**
     * Crear transacción en PayPal
     */
    public function createPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre'                    => 'required|string',
                'apellido'                  => 'required|string',
                'ci'                        => 'required|string',
                'correo_electronico'        => 'required|email',
                'fecha_nacimiento'          => 'required|date',
                'sexo'                      => 'required|in:M,F',
                'direccion'                 => 'required|string',
                'telefono'                  => 'nullable|string',
                'ciudad'                    => 'required|string',
                'colegio_procedencia'       => 'required|string',
                'titulo_bachiller'          => 'required|string',
                'carrera_primera_opcion_id' => 'required|string',
                'carrera_segunda_opcion_id' => 'required|string',
                'es_repostulacion'          => 'nullable|boolean',
                'id_persona_anterior'       => 'nullable|integer',
            ]);

            $esRepostulacion   = !empty($validated['es_repostulacion']);
            $idPersonaAnterior = $validated['id_persona_anterior'] ?? null;

            // Verificar datos únicos ANTES de ir a PayPal
            $observaciones = $this->verificarDatos($validated, null, $esRepostulacion);

            if (!empty($observaciones)) {
                // Registrar como Rechazado sin ir a PayPal
                $registro = null;
                $nombre   = $validated['nombre'] . ' ' . $validated['apellido'];

                DB::transaction(function () use ($validated, $observaciones, &$registro, $esRepostulacion, $idPersonaAnterior) {
                    if ($esRepostulacion && $idPersonaAnterior) {
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

                    $gestion = DB::table('gestion_academica')->orderBy('codigo', 'desc')->value('codigo') ?: 1;
                    $inscripcionId = DB::table('inscripcion')->insertGetId([
                        'fecha_inscripcion'        => now()->toDateString(),
                        'estado_pago'              => 'Pendiente',
                        'codigo_gestion_academica' => $gestion,
                        'codigo_pago'              => null,
                        'codigo_pasarelaPago'       => null,
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
                        'carrera_segunda_opcion_id' => $validated['carrera_segunda_opcion_id'] ?: null,
                        'carrera_asignada_id'       => null,
                        'estado_asignacion'         => 'Rechazado',
                        'observaciones_rechazo'     => implode(' | ', $observaciones),
                    ]);

                    $registro = 'P' . str_pad($postulanteId, 3, '0', STR_PAD_LEFT);
                    DB::table('postulante')->where('id', $postulanteId)->update(['registro' => $registro]);

                    BitacoraService::registrar(
                        "Postulante {$registro} registrado como RECHAZADO (PayPal)",
                        request()->ip(),
                        $personaId
                    );
                });

                $this->enviarCorreoRechazo($validated['correo_electronico'], $nombre, $observaciones);

                return response()->json([
                    'success'       => false,
                    'rechazado'     => true,
                    'registro'      => $registro,
                    'observaciones' => $observaciones,
                ], 200);
            }

            // Sin problemas — guardar datos temporales en sesión
            session([
                'postulacion_data' => $validated,
                'postulacion_timestamp' => now(),
            ]);

            // Obtener token de PayPal
            $token = $this->getPayPalToken();

            // Crear orden en PayPal
            $order = $this->createPayPalOrder($token);

            if ($order && isset($order['id'])) {
                // Guardar ID de orden temporal en sesión
                session(['paypal_order_id' => $order['id']]);

                // Encontrar la URL de aprobación
                $approvalUrl = null;
                if (isset($order['links'])) {
                    foreach ($order['links'] as $link) {
                        if ($link['rel'] === 'approve') {
                            $approvalUrl = $link['href'];
                            break;
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'approval_url' => $approvalUrl,
                    'order_id' => $order['id'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'No se pudo crear la orden en PayPal: ' . json_encode($order),
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validación: ' . json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('PayPal createPayment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ejecutar pago después de aprobación
     */
    public function executePayment(Request $request)
    {
        try {
            $orderId = $request->query('token');

            if (!$orderId) {
                return redirect('/postularse')->with('error', 'No se encontró el ID de la orden');
            }

            // Obtener token de PayPal
            $token = $this->getPayPalToken();

            // Capturar la orden
            $result = $this->capturePayPalOrder($token, $orderId);

            if (!($result && $result['status'] === 'COMPLETED')) {
                return redirect('/postularse')->with('error', 'El pago no fue completado');
            }

            // Obtener datos de postulación de la sesión
            $postulationData = session('postulacion_data');

            if (!$postulationData) {
                return redirect('/postularse')->with('error', 'Datos de postulación no encontrados');
            }

            $registro          = null;
            $password          = $postulationData['ci'];
            $esRepostulacion   = !empty($postulationData['es_repostulacion']);
            $idPersonaAnterior = $postulationData['id_persona_anterior'] ?? null;

            DB::transaction(function () use ($postulationData, $result, &$registro, $password, $esRepostulacion, $idPersonaAnterior) {
                if ($esRepostulacion && $idPersonaAnterior) {
                    DB::table('persona')->where('id', $idPersonaAnterior)->update([
                        'direccion'          => $postulationData['direccion'],
                        'telefono'           => $postulationData['telefono'] ?? null,
                        'correo_electronico' => $postulationData['correo_electronico'],
                        'ciudad'             => $postulationData['ciudad'],
                        'updated_at'         => now(),
                    ]);
                    $personaId = $idPersonaAnterior;
                } else {
                    $personaId = DB::table('persona')->insertGetId([
                        'ci'                 => $postulationData['ci'],
                        'nombre'             => $postulationData['nombre'],
                        'apellido'           => $postulationData['apellido'],
                        'fecha_nacimiento'   => $postulationData['fecha_nacimiento'],
                        'sexo'               => $postulationData['sexo'],
                        'direccion'          => $postulationData['direccion'],
                        'telefono'           => $postulationData['telefono'] ?? null,
                        'correo_electronico' => $postulationData['correo_electronico'],
                        'ciudad'             => $postulationData['ciudad'],
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }

                // Crear registro de pago (sin id_postulante aún)
                $paymentId = DB::table('pago')->insertGetId([
                    'monto' => 150.00,
                    'fecha_pago' => now()->toDateString(),
                    'hora_pago' => now()->format('H:i:s'),
                    'comprobante' => 'PAYPAL-' . $result['id'],
                    'estado' => 'Completado',
                    'metodo_pago' => 'PayPal',
                    'referencia_transaccion' => $result['id'],
                    'descripcion' => 'Pago de inscripción CUP via PayPal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Crear pasarela de pago
                $pasarelaId = DB::table('pasarela_pago')->insertGetId([
                    'monto' => 150.00,
                    'fecha_pago' => now()->toDateString(),
                    'comprobante' => 'PAYPAL-' . $result['id'],
                    'codigo_pago' => $paymentId,
                ]);

                // Obtener gestión académica actual
                $gestionAcademica = DB::table('gestion_academica')
                    ->orderBy('codigo', 'desc')
                    ->value('codigo') ?: 1;

                // Crear inscripción
                $inscripcionId = DB::table('inscripcion')->insertGetId([
                    'fecha_inscripcion' => now()->toDateString(),
                    'estado_pago' => 'Pagado',
                    'codigo_gestion_academica' => $gestionAcademica,
                    'codigo_pago' => $paymentId,
                    'codigo_pasarelaPago' => $pasarelaId,
                ]);

                // Crear postulante
                $postulanteId = DB::table('postulante')->insertGetId([
                    'id_persona' => $personaId,
                    'registro' => 'P000',
                    'colegio_procedencia' => $postulationData['colegio_procedencia'],
                    'ciudad' => $postulationData['ciudad'],
                    'titulo_bachiller' => $postulationData['titulo_bachiller'],
                    'otros_requisitos' => 'Ninguno',
                    'codigo_inscripcion' => $inscripcionId,
                    'codigo_grupo' => null,
                    'carrera_primera_opcion_id' => $postulationData['carrera_primera_opcion_id'],
                    'carrera_segunda_opcion_id' => $postulationData['carrera_segunda_opcion_id'] ?: null,
                    'carrera_asignada_id' => null,
                    'estado_asignacion' => 'Pendiente',
                    // 'password' eliminado: la tabla postulante no tiene esa columna
                    // (la contraseña del postulante es su CI / persona.temporary_password).
                ]);

                // ✅ ACTUALIZAR PAGO CON ID_POSTULANTE
                DB::table('pago')
                    ->where('id', $paymentId)
                    ->update(['id_postulante' => $postulanteId]);

                // Generar código de registro
                $registro = 'P' . str_pad($postulanteId, 3, '0', STR_PAD_LEFT);
                DB::table('postulante')
                    ->where('id', $postulanteId)
                    ->update(['registro' => $registro]);

                // Registrar en bitácora
                BitacoraService::registrar(
                    "Creación de postulante {$registro} - {$postulationData['nombre']} {$postulationData['apellido']} (PayPal)",
                    request()->ip(),
                    $personaId
                );

                // Enviar email con credenciales
                $this->sendCredentialsEmail(
                    $postulationData['correo_electronico'],
                    $postulationData['nombre'],
                    $registro,
                    $password
                );
            });

            // Limpiar sesión
            session()->forget(['postulacion_data', 'paypal_order_id', 'postulacion_timestamp']);

            return redirect('/postularse/exito')->with([
                'success' => 'FELICIDADES YA ESTAS INSCRITO EN LOS CURSOS PREUNIVERSITARIOS PARA LA FICCT. TU NUMERO DE REGISTRO ES ' . $registro . ', YA PUEDES INGRESAR USANDO TU NUMERO DE REGISTRO Y TU CONTRASEÑA QUE FUE ENVIADA A TU CORREO ELECTRÓNICO.',
                'registro' => $registro,
                'ci' => $postulationData['ci'],
            ]);
        } catch (\Exception $e) {
            return redirect('/postularse')->with('error', 'Error al procesar el pago: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar pago
     */
    public function cancelPayment(Request $request)
    {
        // Limpiar datos de sesión
        session()->forget(['postulacion_data', 'paypal_order_id', 'postulacion_timestamp']);

        return redirect('/postularse')->with('error', 'El pago fue cancelado');
    }

    /**
     * Obtener token de acceso de PayPal
     */
    private function getPayPalToken()
    {
        $client = new Client();

        $response = $client->request('POST', $this->paypalUrl . '/v1/oauth2/token', [
            'auth' => [$this->paypalClientId, $this->paypalSecret],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['access_token'];
    }

    /**
     * Crear orden en PayPal
     */
    private function createPayPalOrder($token)
    {
        $client = new Client();

        $response = $client->request('POST', $this->paypalUrl . '/v2/checkout/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => '20.00', // 150 BS aproximadamente a USD
                        ],
                    ],
                ],
                'application_context' => [
                    'return_url' => route('payment.success'),
                    'cancel_url' => route('payment.cancel'),
                ],
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Capturar orden en PayPal
     */
    private function capturePayPalOrder($token, $orderId)
    {
        $client = new Client();

        $response = $client->request('POST', $this->paypalUrl . '/v2/checkout/orders/' . $orderId . '/capture', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Enviar email con credenciales de acceso
     */
    private function sendCredentialsEmail($email, $nombre, $codigo, $password)
    {
        try {
            $subject = 'Credenciales de Acceso - COPA FICCT';
            $loginUrl = url(route('postulacion.login'));
            
            Mail::send('emails.credentials', [
                'nombre' => $nombre,
                'codigo' => $codigo,
                'password' => $password,
                'loginUrl' => $loginUrl,
            ], function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            \Log::error('Error enviando email: ' . $e->getMessage());
        }
    }

    /**
     * Crear inscripción con pago físico (pendiente)
     */
    public function createPhysicalPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre'                    => 'required|string',
                'apellido'                  => 'required|string',
                'ci'                        => 'required|string',
                'correo_electronico'        => 'required|email',
                'fecha_nacimiento'          => 'required|date',
                'sexo'                      => 'required|in:M,F',
                'direccion'                 => 'required|string',
                'telefono'                  => 'nullable|string',
                'ciudad'                    => 'required|string',
                'colegio_procedencia'       => 'required|string',
                'titulo_bachiller'          => 'required|string',
                'carrera_primera_opcion_id' => 'required|string',
                'carrera_segunda_opcion_id' => 'required|string',
                'es_repostulacion'          => 'nullable|boolean',
                'id_persona_anterior'       => 'nullable|integer',
            ]);

            $esRepostulacion   = !empty($validated['es_repostulacion']);
            $idPersonaAnterior = $validated['id_persona_anterior'] ?? null;

            $observaciones  = $this->verificarDatos($validated, null, $esRepostulacion);
            $rechazado      = count($observaciones) > 0;
            $estadoAsig     = $rechazado ? 'Rechazado' : 'Pendiente';
            $registro       = null;
            $nombre         = $validated['nombre'] . ' ' . $validated['apellido'];

            DB::transaction(function () use ($validated, $estadoAsig, $rechazado, $observaciones, &$registro, $esRepostulacion, $idPersonaAnterior) {
                if ($esRepostulacion && $idPersonaAnterior) {
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

                $gestion = DB::table('gestion_academica')->orderBy('codigo', 'desc')->value('codigo') ?: 1;
                $inscripcionId = DB::table('inscripcion')->insertGetId([
                    'fecha_inscripcion'        => now()->toDateString(),
                    'estado_pago'              => 'Pendiente',
                    'codigo_gestion_academica' => $gestion,
                    'codigo_pago'              => null,
                    'codigo_pasarelaPago'       => null,
                ]);

                $postulanteId = DB::table('postulante')->insertGetId([
                    'id_persona'                => $personaId,
                    'registro'                  => 'P000',
                    'colegio_procedencia'       => $validated['colegio_procedencia'],
                    'ciudad'                    => $validated['ciudad'],
                    'titulo_bachiller'          => $validated['titulo_bachiller'],
                    'otros_requisitos'          => 'Pago pendiente',
                    'codigo_inscripcion'        => $inscripcionId,
                    'codigo_grupo'              => null,
                    'carrera_primera_opcion_id' => $validated['carrera_primera_opcion_id'],
                    'carrera_segunda_opcion_id' => $validated['carrera_segunda_opcion_id'] ?: null,
                    'carrera_asignada_id'       => null,
                    'estado_asignacion'         => $estadoAsig,
                    'observaciones_rechazo'     => $rechazado ? implode(' | ', $observaciones) : null,
                ]);

                $registro = 'P' . str_pad($postulanteId, 3, '0', STR_PAD_LEFT);
                DB::table('postulante')->where('id', $postulanteId)->update(['registro' => $registro]);

                BitacoraService::registrar(
                    "Postulante {$registro} pago físico" . ($rechazado ? ' (RECHAZADO)' : ''),
                    request()->ip(),
                    $personaId
                );
            });

            if ($rechazado) {
                $this->enviarCorreoRechazo($validated['correo_electronico'], $nombre, $observaciones);

                return response()->json([
                    'success'       => false,
                    'rechazado'     => true,
                    'registro'      => $registro,
                    'observaciones' => $observaciones,
                ]);
            }

            return response()->json([
                'success'  => true,
                'rechazado' => false,
                'registro' => $registro,
                'ci'       => $validated['ci'],
                'message'  => 'Inscripción creada. Tu pago está pendiente de verificación.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Validación: ' . json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Physical payment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Error: ' . $e->getMessage(),
            ], 400);
        }
    }
}

