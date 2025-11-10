<?php
/**
 * Process Native Payments (Apple Pay / Google Pay)
 * Simulates native payment processing for Tap to Pay functionality
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
if (file_exists('../../lib/funciones.php')) {
    include_once '../../lib/funciones.php';
}
if (file_exists('../../models/lista.php')) {
    include_once '../../models/lista.php';
}
require_once '../../vendor/autoload.php'; // Stripe PHP SDK

// Initialize response array
$response = array(
    'code' => 100,
    'message' => 'Error procesando el pago',
    'data' => null
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    file_put_contents("../files/".basename(__FILE__, '.php')."-".uniqid().".txt", $input);
    
    // Log the request for debugging
    error_log("Native Payment Request: " . json_encode($input));
    
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
    $destination_usuario_id = isset($input['destination_usuario_id']) ? $input['destination_usuario_id'] : null;
    $payment_method_type = isset($input['payment_method_type']) ? $input['payment_method_type'] : 'apple_pay';
    $description = isset($input['description']) ? $input['description'] : 'Native Payment';
    $test_mode = isset($input['test_mode']) ? $input['test_mode'] : true;
    
    // Validate amount
    if ($amount <= 0) {
        $response['message'] = 'El monto debe ser mayor a 0';
        echo json_encode($response);
        exit();
    }
    
    // Get Stripe configuration for the company
    $arrresultado = $conexion->doSelect(
        "stripe_secret_key",
        "compania",
        "compania_id = '$compania'"
    );
    
    if (count($arrresultado) > 0 && !empty($arrresultado[0]['stripe_secret_key'])) {
        $stripe_secret_key = $arrresultado[0]['stripe_secret_key'];
    } else {
        // Use test key as fallback
        $stripe_secret_key = 'sk_test_YOUR_SECRET_KEY_HERE';
    }
    
    // Initialize Stripe
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    // Get user information
    $arrUsuario = $conexion->doSelect(
        "usuario_id, usuario_nombre, usuario_email, stripe_customer_id, cuenta_id, compania_id",
        "usuario",
        "usuario_id = '$usuario_id'"
    );
    
    if (count($arrUsuario) == 0) {
        $response['message'] = 'Usuario no encontrado';
        echo json_encode($response);
        exit();
    }

    foreach($arrUsuario as $n=>$valor2){	      
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);
		$compania_id = utf8_encode($valor2["compania_id"]);					
	}
    
    $user_data = $arrUsuario[0];
    $fechaactual = date('Y-m-d H:i:s');
    
    // In test mode, simulate the payment
    if ($test_mode) {
        // Generate a simulated payment ID
        $payment_intent_id = 'pi_native_' . uniqid();
        $charge_id = 'ch_native_' . uniqid();
        
        // Simulate successful payment
        $payment_status = 'succeeded';
        
        // Always process the balance update and movements
        $amount_in_dollars = $amount / 100;

        // Determine which user should receive the payment
        // If destination_usuario_id is provided, they receive the payment
        // Otherwise, it's a self-deposit
        $recipient_user_id = !empty($destination_usuario_id) ? $destination_usuario_id : $usuario_id;

        // Always update balance and create movements
        if (true) { // Changed from if ($destination_usuario_id)
            
            // Get current balance from usuariobalance table for the RECIPIENT
            $arrBalance = $conexion->doSelect(
                "usuariobalance_total, usuariobalance_disponible, usuariobalance_id",
                "usuariobalance",
                "usuario_id = '$recipient_user_id' AND compania_id = '$compania' AND usuariobalance_activo = 1"
            );
            
            if (count($arrBalance) > 0) {
                // Update existing balance
                $balance_record = $arrBalance[0];
                $current_disponible = floatval($balance_record['usuariobalance_disponible']);
                $current_total = floatval($balance_record['usuariobalance_total']);
                $new_disponible = $current_disponible + $amount_in_dollars;
                $new_total = $current_total + $amount_in_dollars;
                
                $conexion->doUpdate(
                    "usuariobalance",
                    "usuariobalance_disponible = '$new_disponible', usuariobalance_total = '$new_total'",
                    "usuariobalance_id = '".$balance_record['usuariobalance_id']."'"
                );
            } else {
                // Create new balance record for the RECIPIENT
                $conexion->doInsert(
                    "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_fechareg, usuariobalance_activo, compania_id)",
                    "'$recipient_user_id', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '$fechaactual', '1', '$compania'"
                );
            }
            
            // Create movement records
            try {
                // Get user info for movement records
                $arrUsuarioSender = $conexion->doSelect(
                    "cuenta_id",
                    "usuario",
                    "usuario_id = '$usuario_id'"
                );
                $cuenta_id_sender = count($arrUsuarioSender) > 0 ? $arrUsuarioSender[0]['cuenta_id'] : 0;
                
                $arrUsuarioReceiver = $conexion->doSelect(
                    "cuenta_id",
                    "usuario",
                    "usuario_id = '$recipient_user_id'"
                );
                $cuenta_id_receiver = count($arrUsuarioReceiver) > 0 ? $arrUsuarioReceiver[0]['cuenta_id'] : 0;
                
                // Initialize Lista class for getting list IDs if available
                $tipomov_enviado = 0;
                $tipomov_recibido = 0;
                $moneda_id = 0;
                $estatus_id = 0;
                $formapago_id = 0;
                
                if (class_exists('Lista')) {
                    $instancialista = new Lista();
                    
                    // Get tipo movimiento IDs
                    $tipomov_enviado = $instancialista->ObtenerIdLista(21, 269); // Enviado
                    $tipomov_recibido = $instancialista->ObtenerIdLista(22, 269); // Recibido
                    
                    // Get moneda ID (USD)
                    $moneda_id = ObtenerMonedaPrincipalId($cuenta_id, $compania_id);
                    //$moneda_id = $instancialista->ObtenerIdLista(1, 51); // USD
                    
                    // Get estatus for approved payment
                    $estatus_id = $instancialista->ObtenerIdLista(2, 55); // Aprobado
                }
                
                // Get forma de pago ID for native payments
                $payment_method_name = ($payment_method_type == 'apple_pay') ? 'Apple Pay' : 'Google Pay';
                
                // Get forma de pago ID for Stripe/Card payments
                $lista_cod = 999; // Use same code as other Stripe payments
                $arrFormaPago = $conexion->doSelect(
                    "formapago.lista_id as formapago_id",
                    "lista formapago 
                     inner join lista formapagorel on formapagorel.lista_id = formapago.lista_idrel",
                    "formapagorel.lista_cod = '$lista_cod' and formapago.compania_id = '$compania'"
                );
                $formapago_id = count($arrFormaPago) > 0 ? $arrFormaPago[0]['formapago_id'] : 0;
                
                // If not found, try to use default Stripe payment method
                if ($formapago_id == 0) {
                    $formapago_id = 2959; // Default Stripe/Card payment method
                }
                
                // Create movement records
                // IMPORTANT: When receiving payments via Tap to Pay, usuario_id and recipient_user_id
                // are the same (the receiver). We need to create the movement properly for this case.

                if ($recipient_user_id != $usuario_id) {
                    // Two different users - create both movements

                    // Movement for sender (type 21 = sent)
                    $conexion->doInsert(
                        "movimiento (mov_descrip, l_tipomov_id, 
                        usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                        "'Payment sent via $payment_method_name', '$tipomov_enviado',
                         '$recipient_user_id', '$recipient_user_id', '$amount_in_dollars', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '$cuenta_id_sender', '$compania', '$estatus_id'"
                    );

                    // Movement for recipient (type 22 = received)
                    $conexion->doInsert(
                        "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                        "'Payment received via $payment_method_name', '$tipomov_recibido', '$recipient_user_id', '$recipient_user_id', '$amount_in_dollars', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '$cuenta_id_receiver', '$compania', '$estatus_id'"
                    );
                } else {
                    // Self-deposit or receiving payment via Tap to Pay
                    // When receiving via Tap to Pay, we don't know the sender's ID
                    // So we create a movement where usuario_iddestino is the recipient
                    // and usuario_id is NULL or 0 (anonymous sender)
                    $conexion->doInsert(
                        "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                        "'Payment received via $payment_method_name', '$tipomov_recibido', '$recipient_user_id', '$recipient_user_id', '$amount_in_dollars', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '$cuenta_id_receiver', '$compania', '$estatus_id'"
                    );
                }
                
                error_log("Native payment movements created successfully");
                
            } catch (Exception $e) {
                error_log("Error creating movements: " . $e->getMessage());
                // Continue even if movement creation fails
            }
        }
        
        $response['code'] = 0;
        $response['message'] = 'Pago procesado exitosamente';
        $response['data'] = array(
            'payment_intent_id' => $payment_intent_id,
            'charge_id' => $charge_id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $payment_status,
            'payment_method' => $payment_method_type,
            'test_mode' => true,
            'timestamp' => $fechaactual
        );
        
        error_log("Native payment processed successfully: " . json_encode($response['data']));
        
    } else {
        // Production mode - would integrate with actual Apple Pay / Google Pay APIs
        $response['code'] = 100;
        $response['message'] = 'Production mode not yet implemented';
    }
    
} catch (Exception $e) {
    error_log("Process Native Payment - Fatal Error: " . $e->getMessage());
    $response['message'] = 'Error general: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>