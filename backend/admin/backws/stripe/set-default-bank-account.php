<?php
/**
 * Set Default Bank Account
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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';

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
    
    // Verify that the bank account belongs to the user
    // Note: compania_id field doesn't exist in usuario_bank_accounts table
    $arrBankAccount = $conexion->doSelect(
        "bank_account_id, is_default",
        "usuario_bank_accounts",
        "bank_account_id = '$bank_account_id' AND usuario_id = '$usuario_id' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    
    if (count($arrBankAccount) == 0) {
        $response['code'] = 101;
        $response['message'] = 'Cuenta bancaria no encontrada o no pertenece al usuario';
        echo json_encode($response);
        exit();
    }
    
    $account = $arrBankAccount[0];
    
    // Check if it's already the default
    if ($account['is_default'] == '1') {
        $response['code'] = 0;
        $response['message'] = 'Esta cuenta ya es la predeterminada';
        $response['data'] = array('already_default' => true);
        echo json_encode($response);
        exit();
    }
    
    // Update without transaction for simplicity
    $fechaactual = date('Y-m-d H:i:s');
    
    // First, remove default status from all other accounts of this user
    $updateOthers = $conexion->doUpdate(
        "usuario_bank_accounts",
        "is_default = '0', updated_at = '$fechaactual'",
        "usuario_id = '$usuario_id' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    
    // Then set the new default account
    $updateDefault = $conexion->doUpdate(
        "usuario_bank_accounts",
        "is_default = '1', updated_at = '$fechaactual'",
        "bank_account_id = '$bank_account_id'"
    );
    
    if ($updateDefault !== false) {
        $response['code'] = 0;
        $response['message'] = 'Cuenta bancaria establecida como predeterminada exitosamente';
        $response['data'] = array(
            'bank_account_id' => $bank_account_id,
            'is_default' => true,
            'updated_at' => $fechaactual
        );
    } else {
        $response['message'] = 'Error al actualizar la cuenta bancaria';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>