<?php
/**
 * Debug version - Create Setup Intent
 * Shows detailed error information
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

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../models/lista.php';

$response = [
    'code' => 400,
    'message' => 'Invalid request',
    'data' => null,
    'debug' => []
];

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $response['debug']['input'] = $input;
    
    $usuario_id = isset($input['usuario_id']) ? $input['usuario_id'] : null;
    $usuario_email = isset($input['usuario_email']) ? $input['usuario_email'] : null;
    $compania = isset($input['compania']) ? $input['compania'] : null;
    
    if (!$usuario_id || !$compania) {
        $response['message'] = 'Usuario ID y Compañía son requeridos';
        echo json_encode($response);
        exit;
    }
    
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get Stripe configuration for the company from database
    $arrresultado = $conexion->doSelect(
        "stripe_secret_key, stripe_publishable_key",
        "compania",
        "compania_id = '$compania'"
    );
    
    $response['debug']['query'] = "SELECT stripe_secret_key, stripe_publishable_key FROM compania WHERE compania_id = '$compania'";
    
    if (count($arrresultado) == 0) {
        $response['message'] = 'Configuración de Stripe no encontrada para la compañía ' . $compania;
        $response['debug']['company_found'] = false;
        echo json_encode($response);
        exit();
    }
    
    $response['debug']['company_found'] = true;
    $company_data = $arrresultado[0];
    $stripe_secret_key = $company_data['stripe_secret_key'];
    
    // Check if we have a key
    $response['debug']['has_secret_key'] = !empty($stripe_secret_key);
    $response['debug']['key_length'] = strlen($stripe_secret_key);
    $response['debug']['key_starts_with'] = substr($stripe_secret_key, 0, 7);
    
    // Validate that we have a secret key
    if (empty($stripe_secret_key)) {
        $response['message'] = 'La compañía no tiene configurada la clave secreta de Stripe';
        echo json_encode($response);
        exit();
    }
    
    // Test Stripe API connection with cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/setup_intents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'payment_method_types[]' => 'card',
        'metadata[usuario_id]' => $usuario_id,
        'metadata[compania_id]' => $compania
    ]));
    curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // For testing only
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    $response['debug']['http_code'] = $http_code;
    $response['debug']['curl_error'] = $curl_error;
    
    if ($result === false) {
        $response['message'] = 'cURL Error: ' . $curl_error;
        echo json_encode($response);
        exit;
    }
    
    if ($http_code == 200) {
        $setup_intent = json_decode($result, true);
        
        $response['code'] = 0;
        $response['message'] = 'Setup Intent created successfully';
        $response['client_secret'] = $setup_intent['client_secret'];
        $response['setup_intent_id'] = $setup_intent['id'];
        $response['customer_id'] = null;
    } else {
        $error = json_decode($result, true);
        $response['message'] = 'Stripe Error: ' . ($error['error']['message'] ?? 'Unknown error');
        $response['debug']['stripe_error'] = $error;
    }
    
} catch (Exception $e) {
    $response['message'] = 'PHP Error: ' . $e->getMessage();
    $response['debug']['exception'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
}

echo json_encode($response);
?>