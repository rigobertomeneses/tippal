<?php
/**
 * Simplified Bank Account Verification for Test Mode
 * Auto-verifies bank accounts in test mode
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

// Include necessary files
include_once '../../lib/mysqlclass.php';

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
    if (!isset($input['usuario_id']) || !isset($input['bank_account_id']) || !isset($input['compania'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $bank_account_id = $input['bank_account_id'];
    $compania = $input['compania'];
    
    // Get bank account information
    $arrBankAccount = $conexion->doSelect(
        "bank_account_id, stripe_customer_id, stripe_bank_account_id, status, last4, account_holder_name",
        "usuario_bank_accounts",
        "bank_account_id = '$bank_account_id' AND usuario_id = '$usuario_id' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    
    if (count($arrBankAccount) == 0) {
        // Try to find any bank account for this user to help debug
        $anyBankAccount = $conexion->doSelect(
            "bank_account_id, usuario_id, status, deleted_at",
            "usuario_bank_accounts",
            "usuario_id = '$usuario_id'"
        );
        
        $response['message'] = 'Cuenta bancaria no encontrada';
        $response['debug'] = array(
            'searched_bank_account_id' => $bank_account_id,
            'searched_usuario_id' => $usuario_id,
            'total_accounts_for_user' => count($anyBankAccount),
            'accounts' => $anyBankAccount
        );
        echo json_encode($response);
        exit();
    }
    
    $bank_account = $arrBankAccount[0];
    
    // Check if already verified
    if ($bank_account['status'] == 'verified') {
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
    
    // For test/demo mode, automatically verify the account
    // In production, this would initiate actual micro-deposits
    $fechaactual = date('Y-m-d H:i:s');
    
    // Check if we have Stripe IDs (if not, it's a demo account)
    if (empty($bank_account['stripe_customer_id']) || empty($bank_account['stripe_bank_account_id'])) {
        // Demo mode - directly verify
        $updateResult = $conexion->doUpdate(
            "usuario_bank_accounts",
            "status = 'verified', verified_at = '$fechaactual', updated_at = '$fechaactual'",
            "bank_account_id = '$bank_account_id'"
        );
        
        if ($updateResult !== false) {
            $response['code'] = 0;
            $response['message'] = 'Cuenta bancaria verificada (modo demo)';
            $response['data'] = array(
                'status' => 'verified',
                'last4' => $bank_account['last4'],
                'account_holder_name' => $bank_account['account_holder_name'],
                'message' => 'La cuenta ha sido verificada y está lista para usar.',
                'demo_mode' => true
            );
        } else {
            $response['message'] = 'Error actualizando el estado de verificación';
        }
    } else {
        // Has Stripe IDs - mark as pending verification
        // In a real implementation, this would trigger Stripe micro-deposits
        $updateResult = $conexion->doUpdate(
            "usuario_bank_accounts",
            "status = 'validated', updated_at = '$fechaactual'",
            "bank_account_id = '$bank_account_id'"
        );
        
        if ($updateResult !== false) {
            // For test purposes, immediately verify
            $updateResult = $conexion->doUpdate(
                "usuario_bank_accounts",
                "status = 'verified', verified_at = '$fechaactual', updated_at = '$fechaactual'",
                "bank_account_id = '$bank_account_id'"
            );
            
            $response['code'] = 0;
            $response['message'] = 'Cuenta bancaria verificada automáticamente (modo de prueba)';
            $response['data'] = array(
                'status' => 'verified',
                'last4' => $bank_account['last4'],
                'account_holder_name' => $bank_account['account_holder_name'],
                'message' => 'La cuenta ha sido verificada y está lista para usar.',
                'test_mode' => true
            );
        } else {
            $response['message'] = 'Error actualizando el estado de verificación';
        }
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Bank Verification Error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>