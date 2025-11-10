<?php
/**
 * Simple Bank Account Verification Confirmation for Test Mode
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
    if (!isset($input['usuario_id']) || !isset($input['bank_account_id']) || 
        !isset($input['amount1']) || !isset($input['amount2'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $bank_account_id = $input['bank_account_id'];
    $amount1 = intval($input['amount1']);
    $amount2 = intval($input['amount2']);
    
    // Check if test amounts are correct (32 and 45 cents)
    $isCorrectTestAmounts = ($amount1 == 32 && $amount2 == 45) || ($amount1 == 45 && $amount2 == 32);
    
    if (!$isCorrectTestAmounts) {
        $response['code'] = 104;
        $response['message'] = 'Los montos de prueba deben ser $0.32 y $0.45';
        $response['data'] = array(
            'error_type' => 'incorrect_amounts',
            'expected' => array(32, 45),
            'received' => array($amount1, $amount2)
        );
        echo json_encode($response);
        exit();
    }
    
    // Get bank account information
    $arrBankAccount = $conexion->doSelect(
        "bank_account_id, status, last4, account_holder_name",
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
    
    // Update database status to verified
    $fechaactual = date('Y-m-d H:i:s');
    $updateResult = $conexion->doUpdate(
        "usuario_bank_accounts",
        "status = 'verified', verified_at = '$fechaactual', updated_at = '$fechaactual'",
        "bank_account_id = '$bank_account_id'"
    );
    
    if ($updateResult !== false) {
        $response['code'] = 0;
        $response['message'] = '¡Cuenta bancaria verificada exitosamente!';
        $response['data'] = array(
            'status' => 'verified',
            'last4' => $bank_account['last4'],
            'account_holder_name' => $bank_account['account_holder_name'],
            'verified_at' => $fechaactual,
            'message' => 'Su cuenta bancaria ha sido verificada y ahora puede usarla para pagos.',
            'test_mode' => true
        );
    } else {
        $response['message'] = 'Error actualizando el estado de verificación';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>