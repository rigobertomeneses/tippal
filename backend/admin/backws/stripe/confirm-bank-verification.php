<?php
/**
 * Confirm Bank Account Verification
 * Confirms the micro-deposit amounts to verify the bank account
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
    
    // Validate required parameters
    if (!isset($input['usuario_id']) || !isset($input['bank_account_id']) || 
        !isset($input['amount1']) || !isset($input['amount2']) || !isset($input['compania'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $bank_account_id = $input['bank_account_id'];
    $amount1 = intval($input['amount1']); // Convert to cents
    $amount2 = intval($input['amount2']); // Convert to cents
    $compania = $input['compania'];
    
    // Validate amounts (must be between 1 and 99 cents)
    if ($amount1 < 1 || $amount1 > 99 || $amount2 < 1 || $amount2 > 99) {
        $response['code'] = 102;
        $response['message'] = 'Los montos deben estar entre $0.01 y $0.99';
        echo json_encode($response);
        exit();
    }
    
    // Get bank account information
    $arrBankAccount = $conexion->doSelect(
        "stripe_customer_id, stripe_bank_account_id, status, last4, account_holder_name",
        "usuario_bank_accounts",
        "bank_account_id = '$bank_account_id' AND usuario_id = '$usuario_id' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    
    if (count($arrBankAccount) == 0) {
        $response['message'] = 'Cuenta bancaria no encontrada';
        echo json_encode($response);
        exit();
    }
    
    $bank_account = $arrBankAccount[0];
    
    // Check if already verified
    if ($bank_account['status'] == 'verified') {
        $response['code'] = 0;
        $response['message'] = 'Esta cuenta ya está verificada';
        $response['data'] = array('already_verified' => true);
        echo json_encode($response);
        exit();
    }
    
    // Check if verification is pending
    if ($bank_account['status'] != 'validated' && $bank_account['status'] != 'new') {
        $response['code'] = 103;
        $response['message'] = 'Esta cuenta no está esperando verificación';
        echo json_encode($response);
        exit();
    }
    
    // Get Stripe configuration
    $arrresultado = $conexion->doSelect(
        "stripe_secret_key",
        "compania",
        "compania_id = '$compania'"
    );
    
    if (count($arrresultado) == 0) {
        $stripe_secret_key = 'sk_test_YOUR_SECRET_KEY_HERE';
    } else {
        $company_data = $arrresultado[0];
        $stripe_secret_key = !empty($company_data['stripe_secret_key']) ? 
            $company_data['stripe_secret_key'] : 
            'sk_test_YOUR_SECRET_KEY_HERE';
    }
    
    // Initialize Stripe
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    try {
        // Check if we're in test mode
        $isTestMode = strpos($stripe_secret_key, 'sk_test_') === 0;
        
        // In test mode, the correct amounts are 32 and 45 cents
        if ($isTestMode) {
            // Check if user entered the test amounts
            if (($amount1 == 32 && $amount2 == 45) || ($amount1 == 45 && $amount2 == 32)) {
                // Correct test amounts - verify the account
                $bankAccount = \Stripe\Customer::verifySource(
                    $bank_account['stripe_customer_id'],
                    $bank_account['stripe_bank_account_id'],
                    ['amounts' => [32, 45]] // Always use 32, 45 in this order for test mode
                );
            } else {
                // For demo purposes, accept any amounts in test mode
                // Try with the test amounts anyway
                try {
                    $bankAccount = \Stripe\Customer::verifySource(
                        $bank_account['stripe_customer_id'],
                        $bank_account['stripe_bank_account_id'],
                        ['amounts' => [32, 45]] // Test amounts
                    );
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    // If that fails, try with user's amounts
                    $bankAccount = \Stripe\Customer::verifySource(
                        $bank_account['stripe_customer_id'],
                        $bank_account['stripe_bank_account_id'],
                        ['amounts' => [$amount1, $amount2]]
                    );
                }
            }
        } else {
            // Production mode - use the actual amounts entered by user
            $bankAccount = \Stripe\Customer::verifySource(
                $bank_account['stripe_customer_id'],
                $bank_account['stripe_bank_account_id'],
                ['amounts' => [$amount1, $amount2]]
            );
        }
        
        // Update database status
        $fechaactual = date('Y-m-d H:i:s');
        $updateResult = $conexion->doUpdate(
            "usuario_bank_accounts",
            "status = 'verified', verified_at = '$fechaactual', updated_at = '$fechaactual'",
            "bank_account_id = '$bank_account_id'"
        );
        
        if ($updateResult) {
            $response['code'] = 0;
            $response['message'] = '¡Cuenta bancaria verificada exitosamente!';
            $response['data'] = array(
                'status' => 'verified',
                'last4' => $bank_account['last4'],
                'account_holder_name' => $bank_account['account_holder_name'],
                'verified_at' => $fechaactual,
                'message' => 'Su cuenta bancaria ha sido verificada y ahora puede usarla para pagos y retiros.'
            );
        } else {
            throw new Exception('Error actualizando el estado de verificación en la base de datos');
        }
        
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // Check if it's because of incorrect amounts
        if (strpos($e->getMessage(), 'amounts') !== false || strpos($e->getMessage(), 'incorrect') !== false) {
            $response['code'] = 104;
            $response['message'] = 'Los montos ingresados son incorrectos. Por favor verifique e intente nuevamente.';
            $response['data'] = array(
                'error_type' => 'incorrect_amounts',
                'attempts_remaining' => true // In production, you might want to track attempts
            );
        } else {
            throw $e;
        }
    }
    
} catch (\Stripe\Exception\CardException $e) {
    $response['message'] = 'Error de Stripe: ' . $e->getMessage();
} catch (\Stripe\Exception\InvalidRequestException $e) {
    $response['message'] = 'Solicitud inválida: ' . $e->getMessage();
} catch (\Stripe\Exception\AuthenticationException $e) {
    $response['message'] = 'Error de autenticación con Stripe';
} catch (\Stripe\Exception\ApiConnectionException $e) {
    $response['message'] = 'Error de conexión con Stripe';
} catch (\Stripe\Exception\ApiErrorException $e) {
    $response['message'] = 'Error de Stripe: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>