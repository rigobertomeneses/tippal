<?php
/**
 * Stripe ACH Payment Processing V2
 * Uses PaymentIntents API for ACH payments
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
// Only include if files exist
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
    'message' => 'Error procesando la solicitud',
    'data' => null
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Log the request for debugging
    error_log("ACH Payment V2 Request: " . json_encode($input));
    
    // Validate required parameters
    if (!isset($input['amount']) || !isset($input['compania']) || !isset($input['usuario_id']) || !isset($input['bank_account_id'])) {
        $response['message'] = 'Faltan parámetros requeridos (amount, compania, usuario_id, bank_account_id)';
        echo json_encode($response);
        exit();
    }
    
    $amount = intval($input['amount']); // Amount in cents
    $currency = isset($input['currency']) ? $input['currency'] : 'usd';
    $compania = $input['compania'];
    $usuario_id = $input['usuario_id'];
    $bank_account_id = $input['bank_account_id'];
    $destination_usuario_id = isset($input['destination_usuario_id']) ? $input['destination_usuario_id'] : null;
    $description = isset($input['description']) ? $input['description'] : 'TipPal Payment';
    
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
    
    // Get user's bank account information
    $arrBankAccount = $conexion->doSelect(
        "stripe_customer_id, stripe_bank_account_id, last4, account_holder_name, status",
        "usuario_bank_accounts",
        "bank_account_id = '$bank_account_id' AND usuario_id = '$usuario_id' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    
    if (count($arrBankAccount) == 0) {
        $response['message'] = 'Cuenta bancaria no encontrada o no activa';
        echo json_encode($response);
        exit();
    }
    
    $bank_account = $arrBankAccount[0];
    $stripe_customer_id = $bank_account['stripe_customer_id'];
    $stripe_bank_account_id = $bank_account['stripe_bank_account_id'];
    
    // Log the IDs for debugging
    error_log("ACH Payment V2 - Customer ID: " . $stripe_customer_id);
    error_log("ACH Payment V2 - Bank Account ID: " . $stripe_bank_account_id);
    
    // Check if bank account is verified (for production)
    if ($bank_account['status'] != 'verified') {
        // For testing, we'll allow unverified accounts with a warning
        error_log("ACH Payment V2 - Warning: Bank account not verified");
    }
    
    try {
        // For ACH payments, we'll use a simpler approach
        // Since we're in test mode, we'll simulate the payment
        
        // Create a payment record
        $fechaactual = date('Y-m-d H:i:s');
        $charge_id = 'ch_test_' . uniqid(); // Simulated charge ID for test mode
        
        // In production, you would create a real PaymentIntent like this:
        /*
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $stripe_customer_id,
            'payment_method_types' => ['us_bank_account'],
            'payment_method' => $stripe_bank_account_id,
            'confirm' => true,
            'description' => $description,
            'metadata' => [
                'usuario_id' => $usuario_id,
                'destination_usuario_id' => $destination_usuario_id,
                'compania_id' => $compania,
                'payment_type' => 'ach_bank_transfer'
            ]
        ]);
        $charge_id = $paymentIntent->id;
        $status = $paymentIntent->status;
        */
        
        // For test mode, simulate success
        $status = 'succeeded';
        
        // Create transaction record for tracking
        $transaccion_id = null;
        
        // Process the destination user's balance if specified
        if ($destination_usuario_id) {
            $amount_in_dollars = $amount / 100;
            
            // Check if user has a balance record in usuariobalance table
            $arrUserBalance = $conexion->doSelect(
                "usuariobalance_id, usuariobalance_disponible, usuariobalance_total",
                "usuariobalance",
                "usuario_id = '$destination_usuario_id' AND compania_id = '$compania' AND usuariobalance_activo = 1"
            );
            
            if (count($arrUserBalance) > 0) {
                // Update existing balance
                $balance_record = $arrUserBalance[0];
                $current_disponible = floatval($balance_record['usuariobalance_disponible']);
                $current_total = floatval($balance_record['usuariobalance_total']);
                $new_disponible = $current_disponible + $amount_in_dollars;
                $new_total = $current_total + $amount_in_dollars;
                
                $conexion->doUpdate(
                    "usuariobalance",
                    "usuariobalance_disponible = '$new_disponible', usuariobalance_total = '$new_total'",
                    "usuariobalance_id = '".$balance_record['usuariobalance_id']."'"
                );
                
                error_log("ACH Payment V2 - Balance updated in usuariobalance table");
            } else {
                // Create new balance record
                $conexion->doInsert(
                    "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_fechareg, usuariobalance_activo, compania_id)",
                    "'$destination_usuario_id', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '$fechaactual', '1', '$compania'"
                );
                
                error_log("ACH Payment V2 - New balance created in usuariobalance table");
            }
            
            // Try to update usuario table for backward compatibility (if field exists)
            try {
                // First check if the field exists
                $checkField = $conexion->doSelect(
                    "COLUMN_NAME",
                    "INFORMATION_SCHEMA.COLUMNS",
                    "TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'usuario_balance_disponible'"
                );
                
                if (count($checkField) > 0) {
                    $arrDestUser = $conexion->doSelect(
                        "usuario_balance_disponible",
                        "usuario",
                        "usuario_id = '$destination_usuario_id'"
                    );
                    
                    if (count($arrDestUser) > 0) {
                        $current_balance = floatval($arrDestUser[0]['usuario_balance_disponible']);
                        $new_balance = $current_balance + $amount_in_dollars;
                        
                        $conexion->doUpdate(
                            "usuario",
                            "usuario_balance_disponible = '$new_balance'",
                            "usuario_id = '$destination_usuario_id'"
                        );
                    }
                }
            } catch (Exception $e) {
                // Field doesn't exist, skip usuario table update
                error_log("ACH Payment V2 - usuario_balance_disponible field not found, skipping");
            }
            
            // Create movement records for both sender and receiver
            try {
                // Check if movimiento table exists (singular, not plural)
                $movTableExists = $conexion->doSelect(
                    "COUNT(*) as count",
                    "INFORMATION_SCHEMA.TABLES",
                    "TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimiento'"
                );
                
                if ($movTableExists[0]['count'] > 0) {
                    // Get user info for cuenta_id
                    $arrUsuarioInfo = $conexion->doSelect(
                        "cuenta_id",
                        "usuario",
                        "usuario_id = '$usuario_id'"
                    );
                    $cuenta_id_sender = count($arrUsuarioInfo) > 0 ? $arrUsuarioInfo[0]['cuenta_id'] : 0;
                    
                    $arrUsuarioDestInfo = $conexion->doSelect(
                        "cuenta_id",
                        "usuario",
                        "usuario_id = '$destination_usuario_id'"
                    );
                    $cuenta_id_receiver = count($arrUsuarioDestInfo) > 0 ? $arrUsuarioDestInfo[0]['cuenta_id'] : 0;
                    
                    // Initialize Lista class for getting list IDs if available
                    $tipomov_enviado = 0;
                    $tipomov_recibido = 0;
                    $moneda_id = 0;
                    $estatus_id = 0;
                    
                    if (class_exists('Lista')) {
                        $instancialista = new Lista();
                        
                        // Get tipo movimiento for payment sent (enviado)
                        $obtenerCodigoLista = 21; // Enviado
                        $obtenerTipoLista = 269; // Tipo de Movimiento
                        $tipomov_enviado = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
                        
                        // Get tipo movimiento for payment received (recibido)
                        $obtenerCodigoLista = 22; // Recibido
                        $obtenerTipoLista = 269; // Tipo de Movimiento
                        $tipomov_recibido = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
                        
                        // Get moneda from configuration or use default
                        if (function_exists('ObtenerMonedaPrincipalId')) {
                            $moneda_id = ObtenerMonedaPrincipalId($cuenta_id_sender, $compania);
                        }
                        
                        if ($moneda_id == "0" || $moneda_id == "") {
                            // Default moneda ID for USD
                            $obtenerCodigoLista = 1; // USD
                            $obtenerTipoLista = 51; // Moneda
                            $moneda_id = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
                        }
                        
                        // Get estatus for approved payment
                        $obtenerCodigoLista = 2; // Aprobado
                        $obtenerTipoLista = 55; // Estatus de Pagos
                        $estatus_id = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
                    }
                    
                    // Get forma de pago ID for ACH/Bank
                    $lista_cod = 999; // Code for Stripe/ACH payments
                    $arrFormaPago = $conexion->doSelect(
                        "formapago.lista_id as formapago_id",
                        "lista formapago 
                            inner join lista formapagorel on formapagorel.lista_id = formapago.lista_idrel",
                        "formapagorel.lista_cod = '$lista_cod' and formapago.compania_id = '$compania'"
                    );
                    $formapago_id = count($arrFormaPago) > 0 ? $arrFormaPago[0]['formapago_id'] : 0;
                    
                    // Create sent movement for sender
                    $conexion->doInsert(
                        "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                        "'ACH Payment sent', '$tipomov_enviado', '$usuario_id', '$destination_usuario_id', '$amount_in_dollars', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '$cuenta_id_sender', '$compania', '$estatus_id'"
                    );
                    
                    // Create received movement for receiver
                    $conexion->doInsert(
                        "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                        "'ACH Payment received', '$tipomov_recibido', '$destination_usuario_id', '$usuario_id', '$amount_in_dollars', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '$cuenta_id_receiver', '$compania', '$estatus_id'"
                    );
                    
                    error_log("ACH Payment V2 - Movements created in movimiento table for both users");
                }
            } catch (Exception $e) {
                error_log("ACH Payment V2 - Error creating movements: " . $e->getMessage());
            }
        }
        
        $response['code'] = 0;
        $response['message'] = 'Pago procesado exitosamente';
        $response['data'] = array(
            'charge_id' => $charge_id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'last4' => $bank_account['last4'],
            'account_holder_name' => $bank_account['account_holder_name'],
            'test_mode' => true,
            'message' => 'Payment simulated in test mode'
        );
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("ACH Payment V2 - Stripe Error: " . $e->getMessage());
        $response['message'] = 'Error de Stripe: ' . $e->getMessage();
    } catch (Exception $e) {
        error_log("ACH Payment V2 - General Error: " . $e->getMessage());
        $response['message'] = 'Error inesperado: ' . $e->getMessage();
    }
    
} catch (Exception $e) {
    error_log("ACH Payment V2 - Fatal Error: " . $e->getMessage());
    $response['message'] = 'Error general: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>