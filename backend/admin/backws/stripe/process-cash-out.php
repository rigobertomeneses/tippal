<?php
/**
 * Process Cash Out with Split Payments
 * Handles splitting balance among multiple users and sending remainder to bank account
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
    'message' => 'Error procesando la solicitud',
    'data' => null
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Log the request for debugging
    error_log("Process Cash Out Request: " . json_encode($input));
    
    // Validate required parameters
    if (!isset($input['usuario_id']) || !isset($input['compania']) || !isset($input['total_amount'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $compania = $input['compania'];
    $total_amount = floatval($input['total_amount']);
    $split_payments = isset($input['split_payments']) ? $input['split_payments'] : [];
    $remaining_amount = floatval($input['remaining_amount']);
    $bank_account_id = isset($input['bank_account_id']) ? $input['bank_account_id'] : null;
    $stripe_customer_id = isset($input['stripe_customer_id']) ? $input['stripe_customer_id'] : null;
    $stripe_bank_account_id = isset($input['stripe_bank_account_id']) ? $input['stripe_bank_account_id'] : null;
    $express_fee = floatval(isset($input['express_fee']) ? $input['express_fee'] : 0);
    $processing_fee = floatval(isset($input['processing_fee']) ? $input['processing_fee'] : 0);
    
    $fechaactual = date('Y-m-d H:i:s');
    
    // Begin transaction processing
    $successful_transfers = [];
    $failed_transfers = [];
    
    // Get user's current balance
    $arrUserBalance = $conexion->doSelect(
        "usuariobalance_id, usuariobalance_disponible",
        "usuariobalance",
        "usuario_id = '$usuario_id' AND compania_id = '$compania' AND usuariobalance_activo = 1"
    );
    
    if (count($arrUserBalance) == 0) {
        $response['message'] = 'No se encontró balance para el usuario';
        echo json_encode($response);
        exit();
    }
    
    $current_balance = floatval($arrUserBalance[0]['usuariobalance_disponible']);
    $balance_id = $arrUserBalance[0]['usuariobalance_id'];
    
    // Verify sufficient balance
    if ($current_balance < $total_amount) {
        $response['message'] = 'Balance insuficiente';
        echo json_encode($response);
        exit();
    }
    
    // Initialize Lista class for getting list IDs if available
    $tipomov_enviado = 0;
    $tipomov_recibido = 0;
    $tipomov_cashout = 0;
    $moneda_id = 0;
    $estatus_id = 0;
    $formapago_id = 0;
    
    if (class_exists('Lista')) {
        $instancialista = new Lista();
        
        // Get tipo movimiento IDs
        $tipomov_enviado = $instancialista->ObtenerIdLista(21, 269); // Enviado
        $tipomov_recibido = $instancialista->ObtenerIdLista(22, 269); // Recibido
        $tipomov_cashout = $instancialista->ObtenerIdLista(23, 269); // Cash Out
        
        // Get moneda ID (USD)
        $moneda_id = $instancialista->ObtenerIdLista(1, 51); // USD
        
        // Get estatus for approved payment
        $estatus_id = $instancialista->ObtenerIdLista(2, 55); // Aprobado
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
    
    // Process split payments first
    foreach ($split_payments as $split) {
        $recipient_tippal_id = $split['tippal_id'];
        $split_amount = floatval($split['amount']);
        
        // Find recipient user by TipPal ID (using alias field like usuarioinfo endpoint)
        $arrRecipient = $conexion->doSelect(
            "usuario_id, cuenta_id",
            "usuario",
            "(usuario_alias = '$recipient_tippal_id' OR usuario_email = '$recipient_tippal_id') AND compania_id = '$compania'"
        );
        
        if (count($arrRecipient) > 0) {
            $recipient_id = $arrRecipient[0]['usuario_id'];
            $recipient_cuenta_id = $arrRecipient[0]['cuenta_id'];
            
            // Update recipient's balance
            $arrRecipientBalance = $conexion->doSelect(
                "usuariobalance_id, usuariobalance_disponible, usuariobalance_total",
                "usuariobalance",
                "usuario_id = '$recipient_id' AND compania_id = '$compania' AND usuariobalance_activo = 1"
            );
            
            if (count($arrRecipientBalance) > 0) {
                // Update existing balance
                $recipient_balance_record = $arrRecipientBalance[0];
                $new_disponible = floatval($recipient_balance_record['usuariobalance_disponible']) + $split_amount;
                $new_total = floatval($recipient_balance_record['usuariobalance_total']) + $split_amount;
                
                $conexion->doUpdate(
                    "usuariobalance",
                    "usuariobalance_disponible = '$new_disponible', usuariobalance_total = '$new_total'",
                    "usuariobalance_id = '".$recipient_balance_record['usuariobalance_id']."'"
                );
            } else {
                // Create new balance record
                $conexion->doInsert(
                    "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_fechareg, usuariobalance_activo, compania_id)",
                    "'$recipient_id', '$split_amount', '$split_amount', '0', '0', '$fechaactual', '1', '$compania'"
                );
            }
            
            // Get recipient name for better descriptions
            $arrRecipientInfo = $conexion->doSelect(
                "usuario_nombre, usuario_apellido, usuario_alias",
                "usuario",
                "usuario_id = '$recipient_id'"
            );
            $recipient_name = '';
            if (count($arrRecipientInfo) > 0) {
                $recipient_name = trim($arrRecipientInfo[0]['usuario_nombre'] . ' ' . $arrRecipientInfo[0]['usuario_apellido']);
                if (empty($recipient_name)) {
                    $recipient_name = $arrRecipientInfo[0]['usuario_alias'];
                }
            }

            // Create movement records for split payment
            // Movement for sender (enviado) - Money sent from cash out split
            $mov_descrip_sent = "Retiro dividido enviado a $recipient_name";
            $conexion->doInsert(
                "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                "'$mov_descrip_sent', '$tipomov_enviado', '$usuario_id', '$recipient_id', '$split_amount', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '0', '$compania', '$estatus_id'"
            );

            // Movement for recipient (recibido) - Money received from split
            $mov_descrip_received = "Pago recibido de retiro dividido";
            $conexion->doInsert(
                "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                "'$mov_descrip_received', '$tipomov_recibido', '$recipient_id', '$usuario_id', '$split_amount', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '$recipient_cuenta_id', '$compania', '$estatus_id'"
            );
            
            $successful_transfers[] = array(
                'tippal_id' => $recipient_tippal_id,
                'amount' => $split_amount,
                'status' => 'success'
            );
        } else {
            $failed_transfers[] = array(
                'tippal_id' => $recipient_tippal_id,
                'amount' => $split_amount,
                'status' => 'failed',
                'error' => 'TipPal ID not found'
            );
        }
    }
    
    // Process cash out to bank account (remaining amount after splits and fees)
    $cash_out_success = false;
    $stripe_transfer_id = null;
    
    if ($remaining_amount > 0 && $bank_account_id) {
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
        
        try {
            // In test mode, simulate the transfer
            // In production, you would create an actual ACH transfer here
            /*
            $transfer = \Stripe\Transfer::create([
                'amount' => intval($remaining_amount * 100), // Amount in cents
                'currency' => 'usd',
                'destination' => $stripe_bank_account_id,
                'description' => 'Cash out from TipPal balance',
                'metadata' => [
                    'usuario_id' => $usuario_id,
                    'bank_account_id' => $bank_account_id,
                    'compania_id' => $compania
                ]
            ]);
            $stripe_transfer_id = $transfer->id;
            */
            
            // For test mode, generate a simulated transfer ID
            $stripe_transfer_id = 'tr_test_' . uniqid();
            $cash_out_success = true;
            
            // Create cash out movement with better description
            $bank_info = '';
            if ($bank_account_id) {
                $arrBankInfo = $conexion->doSelect(
                    "account_holder_name, last4",
                    "usuario_bank_accounts",
                    "bank_account_id = '$bank_account_id'"
                );
                if (count($arrBankInfo) > 0) {
                    $bank_info = ' - ' . $arrBankInfo[0]['account_holder_name'] . ' ****' . $arrBankInfo[0]['last4'];
                }
            }

            $mov_descrip_cashout = "Retiro a cuenta bancaria" . $bank_info;
            $conexion->doInsert(
                "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                "'$mov_descrip_cashout', '$tipomov_cashout', '$usuario_id', '0', '$remaining_amount', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '0', '$compania', '$estatus_id'"
            );
            
            error_log("Cash out processed successfully. Transfer ID: " . $stripe_transfer_id);
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Error processing cash out: " . $e->getMessage());
            $cash_out_success = false;
        }
    }
    
    // Update user's balance (deduct total amount)
    $new_balance = $current_balance - $total_amount;
    $conexion->doUpdate(
        "usuariobalance",
        "usuariobalance_disponible = '$new_balance'",
        "usuariobalance_id = '$balance_id'"
    );
    
    // Create fee movements if applicable
    if ($express_fee > 0) {
        $conexion->doInsert(
            "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
            "'Comisión de retiro express', '$tipomov_enviado', '$usuario_id', '0', '$express_fee', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '0', '$compania', '$estatus_id'"
        );
    }

    if ($processing_fee > 0) {
        $conexion->doInsert(
            "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
            "'Comisión de procesamiento', '$tipomov_enviado', '$usuario_id', '0', '$processing_fee', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '0', '$compania', '$estatus_id'"
        );
    }

    // Create a summary movement for the total cash out operation
    if ($total_amount > 0) {
        $total_splits = count($successful_transfers);
        $summary_descrip = "Retiro total procesado";
        if ($total_splits > 0) {
            $summary_descrip .= " - $total_splits usuarios";
        }
        if ($remaining_amount > 0) {
            $summary_descrip .= " + cuenta bancaria";
        }

        // This is the main cash out record showing the total amount debited from account
        $conexion->doInsert(
            "movimiento (mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
            "'$summary_descrip', '$tipomov_cashout', '$usuario_id', '0', '$total_amount', '$fechaactual', '$moneda_id', '$formapago_id', '$fechaactual', '1', '0', '0', '$compania', '$estatus_id'"
        );
    }
    
    $response['code'] = 0;
    $response['message'] = 'Cash out procesado exitosamente';
    $response['data'] = array(
        'successful_transfers' => $successful_transfers,
        'failed_transfers' => $failed_transfers,
        'cash_out_amount' => $remaining_amount,
        'cash_out_success' => $cash_out_success,
        'stripe_transfer_id' => $stripe_transfer_id,
        'total_processed' => $total_amount,
        'new_balance' => $new_balance,
        'fees' => array(
            'express_fee' => $express_fee,
            'processing_fee' => $processing_fee
        ),
        'test_mode' => true,
        'message' => 'Cash out simulated in test mode'
    );
    
} catch (Exception $e) {
    error_log("Process Cash Out - Fatal Error: " . $e->getMessage());
    $response['message'] = 'Error general: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>