<?php
/**
 * Register Card Payment Movement with Subscription Support
 * Creates movement records for card payments WITHOUT deducting from sender's balance
 * ALSO handles subscription plan activations when applicable
 */

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('content-type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set("display_errors", "0");

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../models/lista.php';
include_once '../socialmedia/config.php'; // Para usar enviarNotificacionFunciones

// Initialize response array
$response = array(
    'code' => 100,
    'message' => 'Error registrando el movimiento',
    'data' => null
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();

    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    // Validate required parameters
    if (!isset($input['usuario_id']) || !isset($input['compania']) || !isset($input['amount'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }

    $usuario_id = $input['usuario_id']; // Sender
    $destination_usuario_id = isset($input['destination_usuario_id']) ? $input['destination_usuario_id'] : null; // Recipient
    $compania = $input['compania'];
    $amount = intval($input['amount']); // Amount in cents
    $payment_intent_id = isset($input['payment_intent_id']) ? $input['payment_intent_id'] : '';
    $destination_usuario_email = isset($input['destination_usuario_email']) ? $input['destination_usuario_email'] : '';
    $destination_usuario_nombre = isset($input['destination_usuario_nombre']) ? $input['destination_usuario_nombre'] : '';

    // ==================================================================================
    // NUEVO: Detectar si es un pago de suscripción
    // ==================================================================================
    $subscription_plan_id = isset($input['subscription_plan_id']) ? $input['subscription_plan_id'] : null;
    $subscription_metadata = isset($input['metadata']) ? $input['metadata'] : [];
    $is_subscription_payment = !empty($subscription_plan_id) ||
                              (isset($subscription_metadata['type']) && $subscription_metadata['type'] == 'subscription');

    // Convert amount to dollars
    $amount_in_dollars = $amount / 100;

    // Get current date
    $fecha_actual = date('Y-m-d H:i:s');

    // Initialize Lista class for getting list IDs
    $instancialista = new Lista();

    // Determine movement codes based on company and context
    $obtenerTipoLista = 269; // Tipo de Movimiento (siempre 269)

    // ==================================================================================
    // SI ES PAGO DE SUSCRIPCIÓN - PROCESAR SUSCRIPCIÓN
    // ==================================================================================
    if ($is_subscription_payment && $subscription_plan_id) {
        error_log("STRIPE: Procesando pago de suscripción - Plan ID: $subscription_plan_id, Usuario: $usuario_id");

        // Obtener información del plan
        $arrPlan = $conexion->doSelect(
            "p.*, u.usuario_alias, u.usuario_id as creador_id",
            "plan_creador p INNER JOIN usuario u ON p.usuario_id = u.usuario_id",
            "p.plan_id = '$subscription_plan_id' AND p.plan_activo = 1"
        );

        if (count($arrPlan) === 0) {
            error_log("STRIPE: ERROR - Plan no encontrado: $subscription_plan_id");
            $response['code'] = 404;
            $response['message'] = 'Plan no encontrado';
            echo json_encode($response);
            exit();
        }

        $plan = $arrPlan[0];
        $creador_id = $plan['creador_id'];

        // Verificar si ya tiene una suscripción activa
        $arrSuscripcionExiste = $conexion->doSelect(
            "*",
            "plan_suscripcion",
            "plan_id = '$subscription_plan_id'
             AND usuario_id = '$usuario_id'
             AND suscripcion_estado = 'activa'
             AND suscripcion_fecha_fin > NOW()"
        );

        if (count($arrSuscripcionExiste) > 0) {
            error_log("STRIPE: Usuario ya tiene suscripción activa al plan $subscription_plan_id");
            // No es un error, pero no crear duplicado
            // Continuar con el registro del pago
        } else {
            // Calcular fecha de fin según la duración del plan
            $fecha_inicio = date('Y-m-d H:i:s');
            $fecha_fin = '';

            switch ($plan['plan_tipo_duracion']) {
                case 'dias':
                    $fecha_fin = date('Y-m-d H:i:s', strtotime("+{$plan['plan_duracion']} days"));
                    break;
                case 'semanas':
                    $fecha_fin = date('Y-m-d H:i:s', strtotime("+{$plan['plan_duracion']} weeks"));
                    break;
                case 'meses':
                    $fecha_fin = date('Y-m-d H:i:s', strtotime("+{$plan['plan_duracion']} months"));
                    break;
                case 'anual':
                    $fecha_fin = date('Y-m-d H:i:s', strtotime("+1 year"));
                    break;
                default:
                    $fecha_fin = date('Y-m-d H:i:s', strtotime("+{$plan['plan_duracion']} months"));
            }

            // Crear la suscripción
            $resultado_suscripcion = $conexion->doInsert(
                "plan_suscripcion (
                    plan_id,
                    usuario_id,
                    creador_id,
                    suscripcion_fecha_inicio,
                    suscripcion_fecha_fin,
                    suscripcion_precio_pagado,
                    suscripcion_estado,
                    suscripcion_metodo_pago,
                    suscripcion_referencia_pago,
                    suscripcion_renovacion_auto,
                    compania_id,
                    suscripcion_fechareg
                )",
                "'$subscription_plan_id',
                 '$usuario_id',
                 '$creador_id',
                 '$fecha_inicio',
                 '$fecha_fin',
                 '$amount_in_dollars',
                 'activa',
                 'stripe',
                 '$payment_intent_id',
                 1,
                 '$compania',
                 NOW()"
            );

            if ($resultado_suscripcion) {
                error_log("STRIPE: Suscripción creada exitosamente - ID: $resultado_suscripcion");

                // Obtener el ID de la suscripción
                $ultimo = $conexion->doSelect(
                    "MAX(suscripcion_id) as ultimo_id",
                    "plan_suscripcion",
                    "usuario_id = '$usuario_id'"
                );
                $suscripcion_id = $ultimo[0]['ultimo_id'];

                // Obtener info del usuario para notificaciones
                $arrUsuarioInfo = $conexion->doSelect(
                    "usuario_alias, usuario_nombre",
                    "usuario",
                    "usuario_id = '$usuario_id'"
                );
                $usuario_alias = $arrUsuarioInfo[0]['usuario_alias'] ?? 'Usuario';

                // Enviar notificación al creador
                $mensaje = "$usuario_alias se ha suscrito a tu plan {$plan['plan_nombre']}";
                enviarNotificacionFunciones(
                    $creador_id,
                    'NUEVA_SUSCRIPCION',
                    $mensaje,
                    $usuario_id,
                    'plan_suscripcion',
                    $suscripcion_id
                );

                $response['data']['subscription_created'] = true;
                $response['data']['subscription_id'] = $suscripcion_id;
                $response['data']['subscription_expires'] = $fecha_fin;
            }
        }

        // Actualizar el balance del CREADOR (recibe el dinero de la suscripción)
        $arrBalanceCreador = $conexion->doSelect(
            "usuariobalance_total, usuariobalance_disponible",
            "usuariobalance",
            "usuariobalance_eliminado = '0' and usuario_id = '$creador_id'"
        );

        if (count($arrBalanceCreador) > 0) {
            // Actualizar balance existente
            $balance_total = floatval($arrBalanceCreador[0]["usuariobalance_total"]);
            $balance_disponible = floatval($arrBalanceCreador[0]["usuariobalance_disponible"]);

            // Aplicar comisión de la plataforma (10% por ejemplo)
            $comision_plataforma = $amount_in_dollars * 0.10;
            $monto_neto_creador = $amount_in_dollars - $comision_plataforma;

            $new_total = $balance_total + $monto_neto_creador;
            $new_disponible = $balance_disponible + $monto_neto_creador;

            $conexion->doUpdate(
                "usuariobalance",
                "usuariobalance_total = '$new_total', usuariobalance_disponible = '$new_disponible'",
                "usuario_id = '$creador_id' and usuariobalance_eliminado = '0'"
            );

            error_log("STRIPE: Balance del creador $creador_id actualizado - Recibió: $monto_neto_creador (comisión: $comision_plataforma)");
        } else {
            // Crear balance si no existe
            $comision_plataforma = $amount_in_dollars * 0.10;
            $monto_neto_creador = $amount_in_dollars - $comision_plataforma;

            $cuenta_id_creador = $conexion->doSelect("cuenta_id", "usuario", "usuario_id = '$creador_id'")[0]['cuenta_id'] ?? 0;

            $conexion->doInsert(
                "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_activo, usuariobalance_eliminado, cuenta_id, compania_id)",
                "'$creador_id', '$monto_neto_creador', '$monto_neto_creador', '0', '0', '1', '0', '$cuenta_id_creador', '$compania'"
            );

            error_log("STRIPE: Balance creado para creador $creador_id - Inicial: $monto_neto_creador");
        }

        // Crear movimientos para la suscripción
        $concepto_suscripcion = "Suscripción al plan: {$plan['plan_nombre']}";
        $concepto_ingreso = "Ingreso por suscripción de $usuario_alias";

        // Tipo de movimiento para suscripción (usar código 21 = Envío para el usuario, código 2 = Recepción para el creador)
        $tipomov_suscripcion = $instancialista->ObtenerIdLista(21, $obtenerTipoLista);
        $tipomov_ingreso = $instancialista->ObtenerIdLista(2, $obtenerTipoLista);

        // Movimiento para el usuario (pago de suscripción)
        $cuenta_id_usuario = $conexion->doSelect("cuenta_id", "usuario", "usuario_id = '$usuario_id'")[0]['cuenta_id'] ?? 0;

        $conexion->doInsert(
            "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha,
            mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_formapago_id, l_moneda_id, l_estatus_id, payment_intent_id)",
            "'$concepto_suscripcion', '$tipomov_suscripcion', '$usuario_id', '$creador_id', '$amount_in_dollars', '$fecha_actual',
            '$fecha_actual', '1', '0', '$cuenta_id_usuario', '$compania', '2959', '29', '204', '$payment_intent_id'"
        );

        // Movimiento para el creador (ingreso por suscripción)
        $cuenta_id_creador = $conexion->doSelect("cuenta_id", "usuario", "usuario_id = '$creador_id'")[0]['cuenta_id'] ?? 0;
        $monto_neto_creador = $amount_in_dollars * 0.90; // Después de comisión

        $conexion->doInsert(
            "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha,
            mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_formapago_id, l_moneda_id, l_estatus_id, payment_intent_id)",
            "'$concepto_ingreso', '$tipomov_ingreso', '$creador_id', '$usuario_id', '$monto_neto_creador', '$fecha_actual',
            '$fecha_actual', '1', '0', '$cuenta_id_creador', '$compania', '2959', '29', '204', '$payment_intent_id'"
        );

        error_log("STRIPE: Movimientos de suscripción creados");

        $response['code'] = 0;
        $response['message'] = 'Suscripción y pago procesados exitosamente';
        $response['data']['subscription_payment'] = true;
        $response['data']['plan_id'] = $subscription_plan_id;
        $response['data']['creator_id'] = $creador_id;

    } else if ($compania == 467) {
        // TipPal (compania_id = 467) - Lógica original
        // ... [resto del código original para TipPal y depósitos normales]

    } else {
        // Lógica original para depósitos y transferencias normales
        // ... [resto del código original]
    }

} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
    error_log('Error in register-card-payment-movement-subscription.php: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>