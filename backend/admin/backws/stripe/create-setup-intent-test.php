<?php
/**
 * Test version - Create Setup Intent
 * Returns mock setup intent for testing
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
$compania = isset($input['compania']) ? $input['compania'] : null;

if (!$usuario_id || !$compania) {
    echo json_encode([
        'code' => 400,
        'message' => 'Usuario ID y Compañía son requeridos',
        'data' => null
    ]);
    exit;
}

// Generate a mock client secret (format similar to Stripe's)
$mock_secret = 'seti_' . bin2hex(random_bytes(16)) . '_secret_' . bin2hex(random_bytes(16));
$mock_setup_intent_id = 'seti_' . bin2hex(random_bytes(16));
$mock_customer_id = 'cus_' . bin2hex(random_bytes(8));

// Return mock successful response
$response = [
    'code' => 0,
    'message' => 'Setup Intent created successfully (Test Mode)',
    'client_secret' => $mock_secret,
    'setup_intent_id' => $mock_setup_intent_id,
    'customer_id' => $mock_customer_id
];

echo json_encode($response);
?>