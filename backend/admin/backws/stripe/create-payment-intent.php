<?php
/**
 * Stripe Connect Payment Intent Creation
 * Creates a payment intent with destination charges for Stripe Connect
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
require_once '../../vendor/autoload.php'; // Stripe PHP SDK
require_once __DIR__ . '/stripe-helpers.php'; // Stripe helper functions

// Initialize response array
$response = array(
    'code' => 100,
    'message' => 'Error procesando la solicitud',
    'data' => null
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Validate required parameters
    if (!isset($input['amount']) || !isset($input['compania']) || !isset($input['usuario_id'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }
    
    $amount = intval($input['amount']); // Amount in cents
    $currency = isset($input['currency']) ? $input['currency'] : 'usd';
    $compania = $input['compania'];
    $usuario_id = $input['usuario_id'];
    $destination_account_id = isset($input['destination_account_id']) ? $input['destination_account_id'] : null;
    $destination_usuario_id = isset($input['destination_usuario_id']) ? $input['destination_usuario_id'] : null; // User receiving the tip
    $application_fee_amount = isset($input['application_fee_amount']) ? $input['application_fee_amount'] : 0;
    $payment_method_id = isset($input['payment_method_id']) ? $input['payment_method_id'] : null;
    $confirm = isset($input['confirm']) ? $input['confirm'] : false;
    $usuario_email = isset($input['usuario_email']) ? $input['usuario_email'] : null;
    
    // Validate amount
    if ($amount <= 0) {
        $response['message'] = 'El monto debe ser mayor a 0';
        echo json_encode($response);
        exit();
    }
    
    // Get Stripe configuration for the company
    $arrresultado = $conexion->doSelect(
        "stripe_secret_key, stripe_connect_enabled",
        "compania",
        "compania_id = '$compania'"
    );
    
    if (count($arrresultado) == 0) {
        // Use default test key if company not found (for development)
        $stripe_secret_key = 'sk_test_YOUR_SECRET_KEY_HERE';
        $stripe_connect_enabled = false;
    } else {
        $company_data = $arrresultado[0];
        $stripe_secret_key = !empty($company_data['stripe_secret_key']) ? 
            $company_data['stripe_secret_key'] : 
            'sk_test_YOUR_SECRET_KEY_HERE';
        $stripe_connect_enabled = $company_data['stripe_connect_enabled'] == 1;
    }
    
    // Initialize Stripe
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    // Get user information
    $arrUsuario = $conexion->doSelect(
        "usuario_id, usuario_nombre, usuario_email, stripe_customer_id, stripe_account_id",
        "usuario",
        "usuario_id = '$usuario_id'"
    );
    
    if (count($arrUsuario) == 0) {
        $response['message'] = 'Usuario no encontrado';
        echo json_encode($response);
        exit();
    }
    
    $user_data = $arrUsuario[0];

    // Fix email format for Stripe (uses helper function)
    $user_email = getUserEmailForStripe($user_data);
    error_log("STRIPE: Email original: " . $user_data['usuario_email'] . " | Email corregido: " . $user_email);

    // Create or retrieve Stripe customer
    $stripe_customer_id = $user_data['stripe_customer_id'];
    if (empty($stripe_customer_id)) {
        try {
            $customer = \Stripe\Customer::create([
                'email' => $user_email,
                'name' => $user_data['usuario_nombre'],
                'metadata' => [
                    'usuario_id' => $usuario_id,
                    'compania_id' => $compania
                ]
            ]);
            $stripe_customer_id = $customer->id;
            
            // Save customer ID to database
            $conexion->doUpdate(
                "usuario",
                "stripe_customer_id = '$stripe_customer_id'",
                "usuario_id = '$usuario_id'"
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $response['message'] = 'Error creando cliente en Stripe: ' . $e->getMessage();
            echo json_encode($response);
            exit();
        }
    }
    
    // Prepare payment intent parameters
    $payment_intent_params = [
        'amount' => $amount,
        'currency' => $currency,
        'customer' => $stripe_customer_id,
        'payment_method_types' => ['card'],
        'metadata' => [
            'usuario_id' => $usuario_id,
            'compania_id' => $compania,
            'type' => 'stripe_connect_payment'
        ]
    ];
    
    // If payment method is provided, attach it and optionally confirm
    if (!empty($payment_method_id)) {
        // First, verify that this payment method belongs to the user and is active
        $tableCheck = $conexion->doQuery("SHOW TABLES LIKE 'usuario_payment_methods'");

        if (count($tableCheck) > 0) {
            // Check if payment method exists and is active
            $arrPaymentMethod = $conexion->doSelect(
                "id, payment_method_id",
                "usuario_payment_methods",
                "payment_method_id = '$payment_method_id'
                 AND usuario_id = '$usuario_id'
                 AND compania_id = '$compania'
                 AND eliminado = '0'
                 AND activo = '1'"
            );

            if (count($arrPaymentMethod) == 0) {
                // Payment method not found or deleted
                $response['code'] = 404;
                $response['message'] = 'La tarjeta guardada no existe o fue eliminada. Por favor, use otra tarjeta o agregue una nueva.';
                echo json_encode($response);
                exit();
            }
        }

        $payment_intent_params['payment_method'] = $payment_method_id;

        // If confirm is true, confirm the payment immediately
        if ($confirm) {
            $payment_intent_params['confirm'] = true;
            $payment_intent_params['return_url'] = 'https://www.gestiongo.com/payment-complete';
        }
    }
    
    // If Stripe Connect is enabled and destination account is provided
    if ($stripe_connect_enabled && !empty($destination_account_id)) {
        // Get destination user's Stripe account
        $arrDestino = $conexion->doSelect(
            "stripe_account_id",
            "usuario",
            "usuario_id = '$destination_account_id' AND compania_id = '$compania'"
        );
        
        if (count($arrDestino) > 0) {
            $dest_data = $arrDestino[0];
            $destination_stripe_account = $dest_data['stripe_account_id'];
            
            if (!empty($destination_stripe_account)) {
                // Add transfer data for Stripe Connect
                $payment_intent_params['transfer_data'] = [
                    'destination' => $destination_stripe_account
                ];
                
                // Add application fee if specified
                if ($application_fee_amount > 0) {
                    $payment_intent_params['application_fee_amount'] = $application_fee_amount;
                }
                
                $payment_intent_params['metadata']['destination_usuario_id'] = $destination_account_id;
                $payment_intent_params['metadata']['application_fee'] = $application_fee_amount;
            }
        }
    }
    
    // Create payment intent
    try {
        $intent = \Stripe\PaymentIntent::create($payment_intent_params);
        
        // Log the transaction in database
        $fecha_actual = date('Y-m-d H:i:s');
        // Use destination_usuario_id for the actual recipient user, not destination_account_id
        $destination_id = !empty($destination_usuario_id) ? "'$destination_usuario_id'" : "NULL";
        $status = ($intent->status == 'succeeded') ? 'succeeded' : 'pending';
        
        $conexion->doInsert(
            "stripe_transactions (compania_id, usuario_id, destination_usuario_id, payment_intent_id, amount, currency, application_fee, status, created_at)",
            "'$compania', '$usuario_id', $destination_id, '{$intent->id}', '$amount', '$currency', '$application_fee_amount', '$status', '$fecha_actual'"
        );
        
        // If payment was confirmed and succeeded, update user balance
        error_log("STRIPE DEBUG: Payment Intent Status = " . $intent->status);
        if ($intent->status == 'succeeded') {
            error_log("STRIPE DEBUG: Entrando al bloque de succeeded");
            // Update user balance
            $amount_in_dollars = $amount / 100;
            
            // Determine which user should receive the payment
            $recipient_user_id = !empty($destination_usuario_id) ? $destination_usuario_id : $usuario_id;
            
            // Get current balance from usuariobalance table for the RECIPIENT
            $arrBalance = $conexion->doSelect(
                "usuariobalance_total, usuariobalance_bloqueado, usuariobalance_disponible, usuariobalance_pendiente, usuariobalance_id",
                "usuariobalance",
                "usuariobalance_eliminado = '0' and usuario_id = '$recipient_user_id'"
            );
            
            if (count($arrBalance) > 0) {
                // Update existing balance
                $usuariobalance_id = $arrBalance[0]["usuariobalance_id"];
                $usuariobalance_total = floatval($arrBalance[0]["usuariobalance_total"]);
                $usuariobalance_disponible = floatval($arrBalance[0]["usuariobalance_disponible"]);
                
                $new_total = $usuariobalance_total + $amount_in_dollars;
                $new_disponible = $usuariobalance_disponible + $amount_in_dollars;
                
                // Update usuariobalance for the RECIPIENT
                $conexion->doUpdate(
                    "usuariobalance",
                    "usuariobalance_total = '$new_total', usuariobalance_disponible = '$new_disponible'",
                    "usuario_id = '$recipient_user_id' and usuariobalance_eliminado = '0'"
                );
            } else {
                // Create new balance record if it doesn't exist for the RECIPIENT
                $resultado = $conexion->doInsert(
                    "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_activo, usuariobalance_eliminado, cuenta_id, compania_id)",
                    "'$recipient_user_id', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '1', '0', '0', '$compania'"
                );
                $usuariobalance_id = $resultado;
            }
            
            // Get user info for movement record
            $arrUsuario = $conexion->doSelect(
                "cuenta_id",
                "usuario",
                "usuario_id = '$usuario_id'"
            );
            $cuenta_id_sender = count($arrUsuario) > 0 ? $arrUsuario[0]['cuenta_id'] : 0;
            
            // Get recipient user info if exists
            $cuenta_id_receiver = 0;
            if (!empty($destination_usuario_id)) {
                $arrUsuarioDestino = $conexion->doSelect(
                    "cuenta_id",
                    "usuario",
                    "usuario_id = '$destination_usuario_id'"
                );
                $cuenta_id_receiver = count($arrUsuarioDestino) > 0 ? $arrUsuarioDestino[0]['cuenta_id'] : 0;
            }
            
            // Get tipo movimiento based on company and context
            $instancialista = new Lista();
            $obtenerTipoLista = 269; // Tipo de Movimiento (siempre 269)

            if ($compania == 467) {
                // TipPal (compania_id = 467)
                // Envío de dinero o agradecimiento
                $obtenerCodigoLista = 21;
                $tipomov_enviado = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

                // Recepción de dinero o agradecimiento
                $obtenerCodigoLista = 2;
                $tipomov_recibido = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

                error_log("STRIPE: TipPal - Códigos: Envío=21, Recepción=2");
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
            $formapago_id = 2959; // Stripe/Card payment method
            $moneda_id = 29; // USD
            $estatus_id = 204; // Approved/Completed status

            // Obtener tipo de pago para la tabla pago
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

            // Create movement records
            $concepto_enviado = 'Payment sent via Stripe';
            $concepto_recibido = 'Payment received via Stripe';

            // Check if destination user exists and create appropriate movements
            if (!empty($destination_usuario_id)) {
                // Create SENT movement for sender
                $conexion->doInsert(
                    "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, 
                    mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_formapago_id, l_moneda_id, l_estatus_id)",
                    "'$concepto_enviado', '$tipomov_enviado', '$usuario_id', '$destination_usuario_id', '$amount_in_dollars', '$fecha_actual', 
                    '$fecha_actual', '1', '0', '$cuenta_id_sender', '$compania', '$formapago_id', '$moneda_id', '$estatus_id'"
                );
                
                // Create RECEIVED movement for recipient
                $conexion->doInsert(
                    "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, 
                    mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_formapago_id, l_moneda_id, l_estatus_id)",
                    "'$concepto_recibido', '$tipomov_recibido', '$destination_usuario_id', '$usuario_id', '$amount_in_dollars', '$fecha_actual', 
                    '$fecha_actual', '1', '0', '$cuenta_id_receiver', '$compania', '$formapago_id', '$moneda_id', '$estatus_id'"
                );
            } else {
                // If no destination user, just create a single deposit/top-up movement for the user
                $concepto_deposito = 'Depósito a cuenta vía tarjeta';
                $conexion->doInsert(
                    "movimiento (mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha,
                    mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_formapago_id, l_moneda_id, l_estatus_id)",
                    "'$concepto_deposito', '$tipomov_deposito', '$usuario_id', '$amount_in_dollars', '$fecha_actual',
                    '$fecha_actual', '1', '0', '$cuenta_id_sender', '$compania', '$formapago_id', '$moneda_id', '$estatus_id'"
                );

                error_log("STRIPE: Movimiento de depósito creado - Tipo: $tipomov_deposito, Monto: $amount_in_dollars");
            }

            // ==================================================================================
            // REGISTRAR EN TABLA PAGO para que aparezca en /depositos
            // ==================================================================================
            error_log("STRIPE DEBUG: Antes de INSERT en tabla pago - tipopago_id: $tipopago_id, pago_codint: $pago_codint");
            $pago_referencia = 'Stripe Payment Intent: ' . $intent->id;
            $pago_comentario = !empty($destination_usuario_id) ?
                'Pago con tarjeta vía Stripe Connect' :
                'Depósito a cuenta vía Stripe';

            $resultado_pago = $conexion->doInsert(
                "pago (pago_monto, pago_fechareg, pago_fecha, pago_referencia, pago_banco, pago_comentario,
                pago_img, l_formapago_id, usuario_id, usuario_idreg, pago_activo, pago_eliminado,
                cuenta_id, compania_id, l_tipoarchivo_id, l_moneda_id, l_estatus_id, pago_procesado, pago_archoriginal,
                pago_codglobal, pago_codint, pago_otro, l_tipopago_id, elemento_id, modulo_id, pago_codexterno,
                usuario_iddestino)",
                "'$amount_in_dollars', '$fecha_actual', '$fecha_actual','$pago_referencia','Stripe', '$pago_comentario',
                '0.jpg', '$formapago_id','$usuario_id', '$usuario_id','1', '0',
                '$cuenta_id_sender', '$compania','0','$moneda_id','$estatus_id', '1','',
                '$pago_codint','$pago_codint','','$tipopago_id','0','0', '{$intent->id}',
                " . (!empty($destination_usuario_id) ? "'$destination_usuario_id'" : "NULL") . "
                )"
            );

            error_log("STRIPE DEBUG: Después de INSERT en tabla pago - Resultado: " . ($resultado_pago ? "SUCCESS (ID: $resultado_pago)" : "FAILED"));
            error_log("STRIPE: Registro en tabla pago creado - ID: $resultado_pago, Código: $pago_codint, Monto: $amount_in_dollars");
        } else {
            error_log("STRIPE DEBUG: NO entró al bloque de succeeded - Status actual: " . $intent->status);
        }
        
        $response['code'] = 0;
        $response['message'] = ($intent->status == 'succeeded') ? 'Pago procesado exitosamente' : 'Payment intent creado exitosamente';
        $response['data'] = array(
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $intent->status
        );
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $response['message'] = 'Error de Stripe: ' . $e->getMessage();
        error_log('Stripe API Error: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
    error_log('Server Error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>