<?php
/**
 * Create Setup Intent using cURL (no Stripe PHP library needed)
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

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';

$response = [
    'code' => 400,
    'message' => 'Invalid request',
    'data' => null
];

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
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
    
    if (count($arrresultado) == 0) {
        $response['message'] = 'Configuración de Stripe no encontrada para la compañía';
        echo json_encode($response);
        exit();
    }
    
    $company_data = $arrresultado[0];
    $stripe_secret_key = $company_data['stripe_secret_key'];
    
    // Validate that we have a secret key
    if (empty($stripe_secret_key)) {
        $response['message'] = 'La compañía no tiene configurada la clave secreta de Stripe';
        echo json_encode($response);
        exit();
    }
    
    // First, try to create or retrieve a Stripe customer
    $customer_id = null;
    
    // Check if we need to create a customer
    if ($usuario_email) {
        // Create customer using cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/customers');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => $usuario_email,
            'metadata[usuario_id]' => $usuario_id,
            'metadata[compania_id]' => $compania
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        
        $customer_result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            $customer = json_decode($customer_result, true);
            $customer_id = $customer['id'];
        }
    }
    
    // Create Setup Intent using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/setup_intents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    
    $post_data = [
        'payment_method_types[]' => 'card',
        'metadata[usuario_id]' => $usuario_id,
        'metadata[compania_id]' => $compania
    ];
    
    if ($customer_id) {
        $post_data['customer'] = $customer_id;
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $setup_intent = json_decode($result, true);
        
        $response['code'] = 0;
        $response['message'] = 'Setup Intent created successfully';
        $response['client_secret'] = $setup_intent['client_secret'];
        $response['setup_intent_id'] = $setup_intent['id'];
        $response['customer_id'] = $customer_id;
    } else {
        $error = json_decode($result, true);
        $response['message'] = 'Stripe Error: ' . ($error['error']['message'] ?? 'Unknown error');
        error_log('Stripe API Error: ' . $result);
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('General Error: ' . $e->getMessage());
}

echo json_encode($response);
?>