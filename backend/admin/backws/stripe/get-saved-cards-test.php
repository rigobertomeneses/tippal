<?php
/**
 * Test version - Get Saved Payment Methods
 * Always returns empty array for testing
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

// Always return success with empty array
$response = [
    'code' => 0,
    'message' => 'Success (Test Mode - No cards saved)',
    'data' => []
];

echo json_encode($response);
?>