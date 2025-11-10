<?php
/**
 * Delete Saved Payment Method
 * Removes a saved payment method for a user
 */

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('content-type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set("display_errors", "0");

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../models/lista.php';
require_once '../../vendor/autoload.php'; // Stripe PHP SDK

$response = [
    'code' => 400,
    'message' => 'Invalid request',
    'data' => null
];

try {
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $card_id = isset($input['card_id']) ? $input['card_id'] : null;
    $usuario_id = isset($input['usuario_id']) ? $input['usuario_id'] : null;
    $compania = isset($input['compania']) ? $input['compania'] : null;
    
    if (!$card_id || !$usuario_id || !$compania) {
        $response['message'] = 'Card ID, Usuario ID y Compañía son requeridos';
        echo json_encode($response);
        exit;
    }
    
    // Get Stripe configuration for the company from database
    $arrresultado = $conexion->doSelect(
        "stripe_secret_key, stripe_publishable_key",
        "compania",
        "compania_id = '$compania'"
    );
    
    if (count($arrresultado) == 0) {
        $response['message'] = 'Configuración de Stripe no encontrada para la compañía';
        echo json_encode($response);
        exit();
    }
    
    $company_data = $arrresultado[0];
    $stripe_secret_key = $company_data['stripe_secret_key'];
    
    // Check if table exists first
    $tableCheck = $conexion->doQuery("SHOW TABLES LIKE 'usuario_payment_methods'");
    
    if (count($tableCheck) == 0) {
        $response['message'] = 'Table usuario_payment_methods does not exist';
        echo json_encode($response);
        exit();
    }
    
    // Verify the card belongs to this user
    $arrCard = $conexion->doSelect(
        "payment_method_id",
        "usuario_payment_methods",
        "id = '$card_id' AND usuario_id = '$usuario_id' 
         AND compania_id = '$compania' AND eliminado = '0'"
    );
    
    if (count($arrCard) == 0) {
        $response['message'] = 'Tarjeta no encontrada';
        echo json_encode($response);
        exit;
    }
    
    $payment_method_id = $arrCard[0]['payment_method_id'];
    
    // Initialize Stripe if we have a secret key
    if (!empty($stripe_secret_key)) {
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        
        // Try to detach the payment method from Stripe
        try {
            $paymentMethod = \Stripe\PaymentMethod::retrieve($payment_method_id);
            $paymentMethod->detach();
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log but don't fail - payment method might already be detached
            error_log('Could not detach payment method from Stripe: ' . $e->getMessage());
        }
    }
    
    // Soft delete from database
    $fecha_eliminada = date('Y-m-d H:i:s');
    $conexion->doUpdate(
        "usuario_payment_methods",
        "eliminado = '1', fecha_eliminada = '$fecha_eliminada'",
        "id = '$card_id'"
    );
    
    $response['code'] = 0;
    $response['message'] = 'Tarjeta eliminada exitosamente';
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Delete Saved Card Error: ' . $e->getMessage());
}

echo json_encode($response);
?>