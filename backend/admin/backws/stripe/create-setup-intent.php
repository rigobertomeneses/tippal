<?php
/**
 * Create Setup Intent for saving payment methods
 * This creates a Setup Intent to save a card without charging it
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
include_once '../../models/lista.php';
require_once '../../vendor/autoload.php'; // Stripe PHP SDK

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
    
    // Initialize Stripe with company's secret key
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    // Check if customer exists in Stripe
    $stripe_customer_id = null;
    
    // Check if user has a Stripe customer ID stored
    $arrUsuario = $conexion->doSelect(
        "stripe_customer_id",
        "usuario",
        "usuario_id = '$usuario_id' and usuario_eliminado = '0'"
    );
    
    if (count($arrUsuario) > 0 && !empty($arrUsuario[0]['stripe_customer_id'])) {
        $stripe_customer_id = $arrUsuario[0]['stripe_customer_id'];
        
        // Verify customer exists in Stripe
        try {
            $customer = \Stripe\Customer::retrieve($stripe_customer_id);
            if ($customer->deleted) {
                $stripe_customer_id = null; // Customer was deleted, create new one
            }
        } catch (Exception $e) {
            $stripe_customer_id = null; // Customer doesn't exist, create new one
        }
    }
    
    // Create new customer if needed
    if (!$stripe_customer_id) {
        $customer = \Stripe\Customer::create([
            'email' => $usuario_email,
            'metadata' => [
                'usuario_id' => $usuario_id,
                'compania_id' => $compania
            ]
        ]);
        
        $stripe_customer_id = $customer->id;
        
        // Save customer ID to database
        $conexion->doUpdate(
            "usuario",
            "stripe_customer_id = '$stripe_customer_id'",
            "usuario_id = '$usuario_id'"
        );
    }
    
    // Create Setup Intent
    $setupIntent = \Stripe\SetupIntent::create([
        'customer' => $stripe_customer_id,
        'payment_method_types' => ['card'],
        'metadata' => [
            'usuario_id' => $usuario_id,
            'compania_id' => $compania
        ]
    ]);
    
    $response['code'] = 0;
    $response['message'] = 'Setup Intent created successfully';
    $response['client_secret'] = $setupIntent->client_secret;
    $response['setup_intent_id'] = $setupIntent->id;
    $response['customer_id'] = $stripe_customer_id;
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    $response['message'] = 'Stripe API Error: ' . $e->getMessage();
    error_log('Stripe API Error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('General Error: ' . $e->getMessage());
}

echo json_encode($response);
?>