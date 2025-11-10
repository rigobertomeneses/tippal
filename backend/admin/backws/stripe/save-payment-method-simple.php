<?php
/**
 * Save Payment Method - Simple version
 * Saves to a log file for now (no database required)
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

$response = [
    'code' => 400,
    'message' => 'Invalid request',
    'data' => null
];

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $usuario_id = isset($input['usuario_id']) ? $input['usuario_id'] : null;
    $payment_method_id = isset($input['payment_method_id']) ? $input['payment_method_id'] : null;
    $cardholder_name = isset($input['cardholder_name']) ? $input['cardholder_name'] : null;
    $compania = isset($input['compania']) ? $input['compania'] : null;
    
    if (!$usuario_id || !$payment_method_id || !$compania) {
        $response['message'] = 'Usuario ID, Payment Method ID y Compañía son requeridos';
        echo json_encode($response);
        exit;
    }
    
    // For now, save to a log file
    $log_file = dirname(__FILE__) . '/saved_cards.log';
    $log_entry = date('Y-m-d H:i:s') . " | Usuario: $usuario_id | PM: $payment_method_id | Name: $cardholder_name | Company: $compania\n";
    
    // Append to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    // Return success
    $response['code'] = 0;
    $response['message'] = 'Payment method saved successfully';
    $response['data'] = [
        'id' => uniqid(),
        'usuario_id' => $usuario_id,
        'payment_method_id' => $payment_method_id,
        'cardholder_name' => $cardholder_name,
        'saved_at' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Error in save-payment-method: ' . $e->getMessage());
}

echo json_encode($response);
?>