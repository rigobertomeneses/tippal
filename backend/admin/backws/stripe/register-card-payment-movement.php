<?php
/**
 * Register Card Payment Movement
 * Creates movement records for card payments WITHOUT deducting from sender's balance
 * The money comes from the card, not from the wallet
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

    // Convert amount to dollars
    $amount_in_dollars = $amount / 100;

    // Get current date
    $fecha_actual = date('Y-m-d H:i:s');

    // Initialize Lista class for getting list IDs
    $instancialista = new Lista();

    // Determine movement codes based on company and context
    $obtenerTipoLista = 269; // Tipo de Movimiento (siempre 269)

    if ($compania == 467) {
        // TipPal (compania_id = 467)
        // Envío de dinero o agradecimiento
        $obtenerCodigoLista = 21;
        $tipomov_enviado = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

        // Recepción de dinero o agradecimiento
        $obtenerCodigoLista = 2;
        $tipomov_recibido = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

        error_log("STRIPE: TipPal - Códigos: Envío=$obtenerCodigoLista, Recepción=2");
    } else {
        // Otras compañías (La Kress, etc.)
        if (!empty($destination_usuario_id)) {
            // Hay destinatario: Envío de dinero o agradecimiento
            $obtenerCodigoLista = 21;
            $tipomov_enviado = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

            // Recepción: Pago con Billetera
            $obtenerCodigoLista = 2;
            $tipomov_recibido = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

            error_log("STRIPE: Transferencia - Códigos: Envío=21, Recepción=2");
        } else {
            // Sin destinatario: Depósito a sí mismo
            $obtenerCodigoLista = 3;
            $tipomov_deposito = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

            error_log("STRIPE: Depósito - Código=3");
        }
    }

    // Set payment method, currency and status
    $formapago_id = 2959; // Stripe/Card payment method - adjust if needed
    $moneda_id = 29; // USD - adjust if needed
    $estatus_id = 204; // Approved/Completed status - adjust if needed

    // Obtener tipo de pago para la tabla pago (Depósito = código 1)
    $obtenerCodigoListaTipoPago = 1; // Depósito (código 1 en tipo lista 191)
    $obtenerTipoListaTipoPago = 191; // Tipo de Pago
    $tipopago_id = $instancialista->ObtenerIdLista($obtenerCodigoListaTipoPago, $obtenerTipoListaTipoPago);

    // Generar código interno único para el pago
    $arrresultado2 = $conexion->doSelect("max(CONVERT(pago_codint, UNSIGNED)) as pago_codint",
        "pago","pago.compania_id = '$compania'");
    $pago_codint = 0;
    if (count($arrresultado2)>0){
        foreach($arrresultado2 as $n=>$valor2){
            $pago_codint = utf8_encode($valor2["pago_codint"]);
        }
        if ($pago_codint==""){$pago_codint=0;}
        $pago_codint = $pago_codint + 1;
    }

    // Get sender info
    $arrUsuario = $conexion->doSelect(
        "cuenta_id, usuario_nombre, usuario_email",
        "usuario",
        "usuario_id = '$usuario_id'"
    );
    $cuenta_id_sender = count($arrUsuario) > 0 ? $arrUsuario[0]['cuenta_id'] : 0;
    $sender_nombre = count($arrUsuario) > 0 ? $arrUsuario[0]['usuario_nombre'] : '';
    $sender_email = count($arrUsuario) > 0 ? $arrUsuario[0]['usuario_email'] : '';

    // Check if destination user exists and get their info
    $cuenta_id_receiver = 0;
    if (!empty($destination_usuario_id)) {
        $arrUsuarioDestino = $conexion->doSelect(
            "cuenta_id, usuario_nombre, usuario_email",
            "usuario",
            "usuario_id = '$destination_usuario_id'"
        );
        $cuenta_id_receiver = count($arrUsuarioDestino) > 0 ? $arrUsuarioDestino[0]['cuenta_id'] : 0;

        // If destination user was found, update their balance (they receive the money)
        if (count($arrUsuarioDestino) > 0) {
            // Get current balance for the recipient
            $arrBalance = $conexion->doSelect(
                "usuariobalance_total, usuariobalance_disponible, usuariobalance_id",
                "usuariobalance",
                "usuariobalance_eliminado = '0' and usuario_id = '$destination_usuario_id'"
            );

            if (count($arrBalance) > 0) {
                // Update existing balance for recipient
                $usuariobalance_total = floatval($arrBalance[0]["usuariobalance_total"]);
                $usuariobalance_disponible = floatval($arrBalance[0]["usuariobalance_disponible"]);

                $new_total = $usuariobalance_total + $amount_in_dollars;
                $new_disponible = $usuariobalance_disponible + $amount_in_dollars;

                // Update recipient's balance
                $conexion->doUpdate(
                    "usuariobalance",
                    "usuariobalance_total = '$new_total', usuariobalance_disponible = '$new_disponible'",
                    "usuario_id = '$destination_usuario_id' and usuariobalance_eliminado = '0'"
                );
            } else {
                // Create new balance record for recipient if it doesn't exist
                $conexion->doInsert(
                    "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_activo, usuariobalance_eliminado, cuenta_id, compania_id)",
                    "'$destination_usuario_id', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '1', '0', '$cuenta_id_receiver', '$compania'"
                );
            }
        }
    }

    // Create movement records
    $concepto_enviado = "Payment sent via card to " . ($destination_usuario_nombre ? $destination_usuario_nombre : $destination_usuario_email);
    $concepto_recibido = "Payment received from " . $sender_nombre;

    // IMPORTANT: We do NOT deduct from sender's balance since payment is from card
    // Only create movement records for tracking

    if (!empty($destination_usuario_id)) {
        // Create SENT movement for sender (no balance deduction)
        $resultado = $conexion->doInsert(
            "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha,
            mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_formapago_id, l_moneda_id, l_estatus_id, payment_intent_id)",
            "'$concepto_enviado', '$tipomov_enviado', '$usuario_id', '$destination_usuario_id', '$amount_in_dollars', '$fecha_actual',
            '$fecha_actual', '1', '0', '$cuenta_id_sender', '$compania', '$formapago_id', '$moneda_id', '$estatus_id', '$payment_intent_id'"
        );

        error_log("STRIPE: Movimiento ENVIADO creado - De: $usuario_id, Para: $destination_usuario_id, Tipo: $tipomov_enviado, Monto: $amount_in_dollars");

        // Create RECEIVED movement for recipient (with balance addition)
        $resultado = $conexion->doInsert(
            "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha,
            mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_formapago_id, l_moneda_id, l_estatus_id, payment_intent_id)",
            "'$concepto_recibido', '$tipomov_recibido', '$destination_usuario_id', '$usuario_id', '$amount_in_dollars', '$fecha_actual',
            '$fecha_actual', '1', '0', '$cuenta_id_receiver', '$compania', '$formapago_id', '$moneda_id', '$estatus_id', '$payment_intent_id'"
        );

        error_log("STRIPE: Movimiento RECIBIDO creado - Para: $destination_usuario_id, De: $usuario_id, Tipo: $tipomov_recibido, Monto: $amount_in_dollars");

        // ==================================================================================
        // REGISTRAR EN TABLA PAGO para transferencias con destinatario
        // ==================================================================================
        $pago_referencia = 'Stripe Payment Intent: ' . $payment_intent_id;
        $pago_comentario = 'Pago con tarjeta vía Stripe Connect';

        $resultado_pago = $conexion->doInsert(
            "pago (pago_monto, pago_fechareg, pago_fecha, pago_referencia, pago_banco, pago_comentario,
            pago_img, l_formapago_id, usuario_id, usuario_idreg, pago_activo, pago_eliminado,
            cuenta_id, compania_id, l_tipoarchivo_id, l_moneda_id, l_estatus_id, pago_procesado, pago_archoriginal,
            pago_codglobal, pago_codint, pago_otro, l_tipopago_id, elemento_id, modulo_id, pago_codexterno,
            usuario_iddestino)",
            "'$amount_in_dollars', '$fecha_actual', '$fecha_actual','$pago_referencia','Stripe', '$pago_comentario',
            '0.jpg', '$formapago_id','$usuario_id', '$usuario_id','1', '0',
            '$cuenta_id_sender', '$compania','0','$moneda_id','$estatus_id', '1','',
            '$pago_codint','$pago_codint','','$tipopago_id','0','0', '$payment_intent_id',
            '$destination_usuario_id'"
        );

        error_log("STRIPE: Registro en tabla pago creado para transferencia - ID: $resultado_pago, De: $usuario_id, Para: $destination_usuario_id, Monto: $amount_in_dollars");

        $response['code'] = 0;
        $response['message'] = 'Movimientos registrados exitosamente';
        $response['data'] = array(
            'sender_movement_created' => true,
            'receiver_movement_created' => true,
            'receiver_balance_updated' => true,
            'pago_created' => true,
            'pago_id' => $resultado_pago,
            'amount' => $amount_in_dollars
        );
    } else {
        // If no destination user, it's a self-deposit
        $concepto_deposito = 'Depósito a cuenta vía tarjeta';

        // Get current balance for the user
        $arrBalance = $conexion->doSelect(
            "usuariobalance_total, usuariobalance_disponible",
            "usuariobalance",
            "usuariobalance_eliminado = '0' and usuario_id = '$usuario_id'"
        );

        if (count($arrBalance) > 0) {
            // Update existing balance
            $usuariobalance_total = floatval($arrBalance[0]["usuariobalance_total"]);
            $usuariobalance_disponible = floatval($arrBalance[0]["usuariobalance_disponible"]);

            $new_total = $usuariobalance_total + $amount_in_dollars;
            $new_disponible = $usuariobalance_disponible + $amount_in_dollars;

            // Update balance
            $conexion->doUpdate(
                "usuariobalance",
                "usuariobalance_total = '$new_total', usuariobalance_disponible = '$new_disponible'",
                "usuario_id = '$usuario_id' and usuariobalance_eliminado = '0'"
            );

            error_log("STRIPE: Balance actualizado para usuario $usuario_id - Nuevo total: $new_total");
        } else {
            // Create new balance record if it doesn't exist
            $conexion->doInsert(
                "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_activo, usuariobalance_eliminado, cuenta_id, compania_id)",
                "'$usuario_id', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '1', '0', '$cuenta_id_sender', '$compania'"
            );

            error_log("STRIPE: Balance creado para usuario $usuario_id - Total inicial: $amount_in_dollars");
        }

        // Create deposit movement (usando tipo de movimiento correcto según compañía)
        $resultado = $conexion->doInsert(
            "movimiento (mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha,
            mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_formapago_id, l_moneda_id, l_estatus_id, payment_intent_id)",
            "'$concepto_deposito', '$tipomov_deposito', '$usuario_id', '$amount_in_dollars', '$fecha_actual',
            '$fecha_actual', '1', '0', '$cuenta_id_sender', '$compania', '$formapago_id', '$moneda_id', '$estatus_id', '$payment_intent_id'"
        );

        error_log("STRIPE: Movimiento de depósito creado - Tipo: $tipomov_deposito, Monto: $amount_in_dollars");

        // ==================================================================================
        // REGISTRAR EN TABLA PAGO para que aparezca en /depositos
        // ==================================================================================
        $pago_referencia = 'Stripe Payment Intent: ' . $payment_intent_id;
        $pago_comentario = 'Depósito a cuenta vía Stripe';

        $resultado_pago = $conexion->doInsert(
            "pago (pago_monto, pago_fechareg, pago_fecha, pago_referencia, pago_banco, pago_comentario,
            pago_img, l_formapago_id, usuario_id, usuario_idreg, pago_activo, pago_eliminado,
            cuenta_id, compania_id, l_tipoarchivo_id, l_moneda_id, l_estatus_id, pago_procesado, pago_archoriginal,
            pago_codglobal, pago_codint, pago_otro, l_tipopago_id, elemento_id, modulo_id, pago_codexterno,
            usuario_iddestino)",
            "'$amount_in_dollars', '$fecha_actual', '$fecha_actual','$pago_referencia','Stripe', '$pago_comentario',
            '0.jpg', '$formapago_id','$usuario_id', '$usuario_id','1', '0',
            '$cuenta_id_sender', '$compania','0','$moneda_id','$estatus_id', '1','',
            '$pago_codint','$pago_codint','','$tipopago_id','0','0', '$payment_intent_id',
            NULL"
        );

        error_log("STRIPE: Registro en tabla pago creado - ID: $resultado_pago, Código: $pago_codint, Monto: $amount_in_dollars, Payment Intent: $payment_intent_id");

        $response['code'] = 0;
        $response['message'] = 'Depósito registrado exitosamente';
        $response['data'] = array(
            'deposit_movement_created' => true,
            'balance_updated' => true,
            'pago_created' => true,
            'pago_id' => $resultado_pago,
            'amount' => $amount_in_dollars
        );
    }

} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
    error_log('Error in register-card-payment-movement.php: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>