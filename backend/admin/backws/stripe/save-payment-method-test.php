<?php
/**
 * Test version - Save Payment Method
 * Returns success without actually saving
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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$usuario_id = isset($input['usuario_id']) ? $input['usuario_id'] : null;
$payment_method_id = isset($input['payment_method_id']) ? $input['payment_method_id'] : null;
$cardholder_name = isset($input['cardholder_name']) ? $input['cardholder_name'] : null;
$compania = isset($input['compania']) ? $input['compania'] : null;

if (!$usuario_id || !$payment_method_id || !$compania) {
    echo json_encode([
        'code' => 400,
        'message' => 'Usuario ID, Payment Method ID y Compañía son requeridos',
        'data' => null
    ]);
    exit;
}

// Return mock successful response
$response = [
    'code' => 0,
    'message' => 'Payment method saved successfully (Test Mode)',
    'data' => [
        'id' => rand(1000, 9999),
        'payment_method_id' => $payment_method_id,
        'cardholder_name' => $cardholder_name,
        'test_mode' => true
    ]
];

echo json_encode($response);
?>