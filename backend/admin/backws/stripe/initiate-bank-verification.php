<?php
/**
 * Initiate Bank Account Verification
 * Starts the micro-deposit verification process for ACH bank accounts
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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

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
    
    // Log incoming request for debugging
    error_log('Initiate Bank Verification Request: ' . json_encode($input));
    
    // Validate required parameters
    if (!isset($input['usuario_id']) || !isset($input['bank_account_id']) || !isset($input['compania'])) {
        $response['message'] = 'Faltan parámetros requeridos (usuario_id, bank_account_id, compania)';
        $response['debug'] = array(
            'received' => array_keys($input ?? array()),
            'required' => array('usuario_id', 'bank_account_id', 'compania')
        );
        echo json_encode($response);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $bank_account_id = $input['bank_account_id'];
    $compania = $input['compania'];
    
    // Get bank account information
    $arrBankAccount = $conexion->doSelect(
        "stripe_customer_id, stripe_bank_account_id, status, last4",
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
    
    // Check if verification is already in progress
    if ($bank_account['status'] == 'validated') {
        $response['code'] = 0;
        $response['message'] = 'La verificación ya está en progreso. Por favor revise su cuenta bancaria para los micro-depósitos.';
        $response['data'] = array('verification_in_progress' => true);
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
        // In test mode, we can immediately verify with test amounts (32 and 45 cents)
        // In production, this would initiate actual micro-deposits
        
        // Check if we're in test mode
        $isTestMode = strpos($stripe_secret_key, 'sk_test_') === 0;
        
        if ($isTestMode) {
            // In test mode, automatically verify with test amounts
            try {
                $bankAccount = \Stripe\Customer::verifySource(
                    $bank_account['stripe_customer_id'],
                    $bank_account['stripe_bank_account_id'],
                    ['amounts' => [32, 45]] // Test amounts that work in Stripe test mode
                );
                
                // Update database status to verified
                $fechaactual = date('Y-m-d H:i:s');
                $conexion->doUpdate(
                    "usuario_bank_accounts",
                    "status = 'verified', verified_at = '$fechaactual', updated_at = '$fechaactual'",
                    "bank_account_id = '$bank_account_id'"
                );
                
                $response['code'] = 0;
                $response['message'] = 'Cuenta bancaria verificada automáticamente (modo de prueba)';
                $response['data'] = array(
                    'status' => 'verified',
                    'last4' => $bank_account['last4'],
                    'message' => 'La cuenta ha sido verificada y está lista para usar.',
                    'test_mode' => true
                );
                
                echo json_encode($response);
                exit();
                
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // If verification fails, it might be because the account is already verified
                // or needs manual verification
                error_log('Test verification failed: ' . $e->getMessage());
            }
        }
        
        // For production mode or if test verification failed
        // Get the bank account source to check its current status
        $customer = \Stripe\Customer::retrieve($bank_account['stripe_customer_id']);
        $sources = $customer->sources->all(['object' => 'bank_account']);
        
        $bankAccountSource = null;
        foreach ($sources->data as $source) {
            if ($source->id == $bank_account['stripe_bank_account_id']) {
                $bankAccountSource = $source;
                break;
            }
        }
        
        if (!$bankAccountSource) {
            throw new Exception('Bank account source not found in Stripe');
        }
        
        // Check current status
        if ($bankAccountSource->status == 'verified') {
            // Already verified
            $fechaactual = date('Y-m-d H:i:s');
            $conexion->doUpdate(
                "usuario_bank_accounts",
                "status = 'verified', verified_at = '$fechaactual', updated_at = '$fechaactual'",
                "bank_account_id = '$bank_account_id'"
            );
            
            $response['code'] = 0;
            $response['message'] = 'Esta cuenta ya está verificada';
            $response['data'] = array(
                'status' => 'verified',
                'last4' => $bank_account['last4'],
                'already_verified' => true
            );
            
            echo json_encode($response);
            exit();
        }
        
        // Status is 'new' or 'validated' - mark as validated to indicate verification pending
        $bankAccount = $bankAccountSource;
        
        // Update database status
        $fechaactual = date('Y-m-d H:i:s');
        $conexion->doUpdate(
            "usuario_bank_accounts",
            "status = 'validated', updated_at = '$fechaactual'",
            "bank_account_id = '$bank_account_id'"
        );
        
        $response['code'] = 0;
        $response['message'] = 'Verificación iniciada. Stripe enviará dos pequeños depósitos a su cuenta en 1-2 días hábiles.';
        $response['data'] = array(
            'status' => 'validated',
            'last4' => $bank_account['last4'],
            'message' => 'Por favor revise su cuenta bancaria en 1-2 días hábiles para ver los montos de los micro-depósitos.'
        );
        
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // Check if it's because micro-deposits were already sent
        if (strpos($e->getMessage(), 'already been verified') !== false) {
            $response['code'] = 0;
            $response['message'] = 'Los micro-depósitos ya fueron enviados. Por favor ingrese los montos para verificar.';
            $response['data'] = array('verification_in_progress' => true);
            
            // Update status in database
            $fechaactual = date('Y-m-d H:i:s');
            $conexion->doUpdate(
                "usuario_bank_accounts",
                "status = 'validated', updated_at = '$fechaactual'",
                "bank_account_id = '$bank_account_id'"
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