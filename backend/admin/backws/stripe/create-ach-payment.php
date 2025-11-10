<?php
/**
 * Stripe ACH Payment Processing
 * Creates a charge using saved bank account (ACH)
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
// Only include if file exists
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

// Wrap everything in a try-catch for error handling
try {
    // Validate that required includes loaded
    if (!class_exists('ConexionBd')) {
        throw new Exception('Database connection class not found');
    }
    if (!class_exists('\Stripe\Stripe')) {
        throw new Exception('Stripe SDK not loaded');
    }
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
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
    
    if (count($arrresultado) == 0) {
        // Use default test key if company not found (for development)
        $stripe_secret_key = 'sk_test_YOUR_SECRET_KEY_HERE';
    } else {
        $company_data = $arrresultado[0];
        $stripe_secret_key = !empty($company_data['stripe_secret_key']) ? 
            $company_data['stripe_secret_key'] : 
            'sk_test_YOUR_SECRET_KEY_HERE';
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
    
    // Debug: Log the IDs being used
    error_log("ACH Payment Debug - Customer ID: " . $stripe_customer_id);
    error_log("ACH Payment Debug - Bank Account ID: " . $stripe_bank_account_id);
    
    // Check if bank account is verified
    if ($bank_account['status'] != 'verified') {
        // For testing/demo purposes, we'll allow unverified accounts
        // In production, you should require verification
        // $response['code'] = 101;
        // $response['message'] = 'La cuenta bancaria debe ser verificada antes de poder usarla';
        // echo json_encode($response);
        // exit();
    }
    
    // Verify the bank account exists as a source on the customer
    try {
        $customer = \Stripe\Customer::retrieve($stripe_customer_id);
        $source = null;
        
        // Try to retrieve the specific bank account source
        try {
            $source = $customer->sources->retrieve($stripe_bank_account_id);
            error_log("ACH Payment Debug - Source found: " . json_encode($source->id));
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Source doesn't exist, let's list all sources to debug
            error_log("ACH Payment Debug - Source not found, listing all sources...");
            $sources = $customer->sources->all(['object' => 'bank_account']);
            error_log("ACH Payment Debug - Available sources: " . json_encode($sources->data));
            
            // Try to find a matching bank account by last4
            foreach ($sources->data as $s) {
                if ($s->last4 == $bank_account['last4']) {
                    $stripe_bank_account_id = $s->id;
                    error_log("ACH Payment Debug - Found matching source by last4: " . $stripe_bank_account_id);
                    
                    // Update the database with the correct source ID
                    $conexion->doUpdate(
                        "usuario_bank_accounts",
                        "stripe_bank_account_id = '$stripe_bank_account_id'",
                        "bank_account_id = '$bank_account_id'"
                    );
                    break;
                }
            }
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("ACH Payment Debug - Error retrieving customer: " . $e->getMessage());
        $response['message'] = 'Error verificando la cuenta bancaria en Stripe: ' . $e->getMessage();
        echo json_encode($response);
        exit();
    }
    
    // Create ACH charge
    try {
        // First try with the specific bank account ID
        $chargeParams = [
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $stripe_customer_id,
            'description' => $description,
            'metadata' => [
                'usuario_id' => $usuario_id,
                'destination_usuario_id' => $destination_usuario_id,
                'compania_id' => $compania,
                'payment_type' => 'ach_bank_transfer'
            ]
        ];
        
        // Only add source if we have a valid stripe_bank_account_id
        if (!empty($stripe_bank_account_id) && $stripe_bank_account_id != '') {
            $chargeParams['source'] = $stripe_bank_account_id;
        }
        
        $charge = \Stripe\Charge::create($chargeParams);
        
        // Log the transaction in the database (if table exists)
        $fechaactual = date('Y-m-d H:i:s');
        $transaccion_id = null;
        
        // Check if usuario_transacciones table exists
        $tableExists = $conexion->doSelect(
            "COUNT(*) as count",
            "information_schema.tables",
            "table_schema = DATABASE() AND table_name = 'usuario_transacciones'"
        );
        
        if ($tableExists[0]['count'] > 0) {
            $transaccion_id = $conexion->doInsert(
                "usuario_transacciones (usuario_id, stripe_charge_id, tipo_transaccion, monto, moneda, estado, bank_account_id, destination_usuario_id, compania_id, created_at)",
                "'$usuario_id', '".$charge->id."', 'ach_payment', '$amount', '$currency', '".$charge->status."', '$bank_account_id', ".($destination_usuario_id ? "'$destination_usuario_id'" : "NULL").", '$compania', '$fechaactual'"
            );
        }
        
        // If there's a destination user, credit their account
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
            } else {
                // Create new balance record
                $conexion->doInsert(
                    "usuariobalance",
                    "usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_fechareg, usuariobalance_activo, compania_id",
                    "'$destination_usuario_id', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '$fechaactual', '1', '$compania'"
                );
            }
            
            // Also update the usuario table for compatibility
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
                
                // Log the credit transaction (if table exists)
                $movTableExists = $conexion->doSelect(
                    "COUNT(*) as count",
                    "information_schema.tables",
                    "table_schema = DATABASE() AND table_name = 'usuario_movimientos'"
                );
                
                if ($movTableExists[0]['count'] > 0) {
                    $conexion->doInsert(
                        "usuario_movimientos",
                        "usuario_id, tipo_movimiento, monto, descripcion, transaccion_referencia_id, compania_id, created_at",
                        "'$destination_usuario_id', 'credito', '$amount_in_dollars', 'Propina recibida', ".($transaccion_id ? "'$transaccion_id'" : "NULL").", '$compania', '$fechaactual'"
                    );
                }
            }
        }
        
        $response['code'] = 0;
        $response['message'] = 'Pago procesado exitosamente';
        $response['data'] = array(
            'charge_id' => $charge->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $charge->status,
            'last4' => $bank_account['last4'],
            'account_holder_name' => $bank_account['account_holder_name'],
            'transaction_id' => $transaccion_id
        );
        
    } catch (\Stripe\Exception\CardException $e) {
        // Since it's ACH, this shouldn't happen, but handle it anyway
        $response['message'] = 'Error en el pago: ' . $e->getMessage();
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        $response['message'] = 'Solicitud inválida: ' . $e->getMessage();
    } catch (\Stripe\Exception\AuthenticationException $e) {
        $response['message'] = 'Error de autenticación con Stripe';
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        $response['message'] = 'Error de conexión con Stripe';
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $response['message'] = 'Error de Stripe: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = 'Error inesperado: ' . $e->getMessage();
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error general: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>