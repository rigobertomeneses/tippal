<?php
/**
 * Set Default Bank Account - Simplified Version
 * Updates the default bank account for a user
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
    
    // Log request for debugging
    error_log('Set Default Bank Account Request: ' . json_encode($input));
    
    // Validate required parameters
    if (!isset($input['usuario_id']) || !isset($input['bank_account_id'])) {
        $response['message'] = 'Faltan parámetros requeridos (usuario_id, bank_account_id)';
        echo json_encode($response);
        exit();
    }
    
    $usuario_id = $input['usuario_id'];
    $bank_account_id = $input['bank_account_id'];
    
    // Verify that the bank account exists and belongs to the user
    $arrBankAccount = $conexion->doSelect(
        "bank_account_id, is_default, last4",
        "usuario_bank_accounts",
        "bank_account_id = '$bank_account_id' AND usuario_id = '$usuario_id' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    
    if (count($arrBankAccount) == 0) {
        $response['code'] = 101;
        $response['message'] = 'Cuenta bancaria no encontrada';
        $response['debug'] = array(
            'bank_account_id' => $bank_account_id,
            'usuario_id' => $usuario_id
        );
        echo json_encode($response);
        exit();
    }
    
    $account = $arrBankAccount[0];
    
    // Check if it's already the default
    if ($account['is_default'] == '1') {
        $response['code'] = 0;
        $response['message'] = 'Esta cuenta ya es la predeterminada';
        $response['data'] = array(
            'already_default' => true,
            'last4' => $account['last4']
        );
        echo json_encode($response);
        exit();
    }
    
    $fechaactual = date('Y-m-d H:i:s');
    
    // Step 1: Remove default status from all accounts of this user
    $sql1 = "UPDATE usuario_bank_accounts 
             SET is_default = '0', updated_at = '$fechaactual' 
             WHERE usuario_id = '$usuario_id' 
             AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
    
    $result1 = $conexion->query($sql1);
    
    // Step 2: Set the new default account
    $sql2 = "UPDATE usuario_bank_accounts 
             SET is_default = '1', updated_at = '$fechaactual' 
             WHERE bank_account_id = '$bank_account_id'";
    
    $result2 = $conexion->query($sql2);
    
    if ($result2) {
        $response['code'] = 0;
        $response['message'] = 'Cuenta bancaria establecida como predeterminada';
        $response['data'] = array(
            'bank_account_id' => $bank_account_id,
            'is_default' => true,
            'last4' => $account['last4'],
            'updated_at' => $fechaactual
        );
    } else {
        $response['message'] = 'Error al actualizar la cuenta bancaria';
        $response['debug'] = array(
            'sql_error' => $conexion->error ?? 'Unknown error'
        );
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Set Default Bank Account Error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>