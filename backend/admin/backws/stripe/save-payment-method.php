<?php
/**
 * Save Payment Method
 * Stores the payment method ID in the database for future use
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
    $payment_method_id = isset($input['payment_method_id']) ? $input['payment_method_id'] : null;
    $cardholder_name = isset($input['cardholder_name']) ? $input['cardholder_name'] : '';
    $compania = isset($input['compania']) ? $input['compania'] : null;
    
    // Profile data
    $usuario_nombre = isset($input['usuario_nombre']) ? $input['usuario_nombre'] : null;
    $usuario_apellido = isset($input['usuario_apellido']) ? $input['usuario_apellido'] : null;
    $usuario_email = isset($input['usuario_email']) ? $input['usuario_email'] : null;
    $usuario_telefono = isset($input['usuario_telefono']) ? $input['usuario_telefono'] : null;
    $genero = isset($input['genero']) ? $input['genero'] : null;
    
    if (!$usuario_id || !$payment_method_id || !$compania) {
        $response['message'] = 'Usuario ID, Payment Method ID y Compañía son requeridos';
        echo json_encode($response);
        exit;
    }
    
    // Connect to database
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
    
    // Retrieve payment method details from Stripe
    $paymentMethod = \Stripe\PaymentMethod::retrieve($payment_method_id);
    
    if (!$paymentMethod) {
        $response['message'] = 'Payment method not found in Stripe';
        echo json_encode($response);
        exit;
    }
    
    // Extract card details
    $card = $paymentMethod->card;
    $brand = $card->brand;
    $last4 = $card->last4;
    $exp_month = $card->exp_month;
    $exp_year = $card->exp_year;
    $fingerprint = $card->fingerprint;
    
    // Check if this card already exists for this user (by fingerprint)
    $arrExistingCard = $conexion->doSelect(
        "id",
        "usuario_payment_methods",
        "usuario_id = '$usuario_id' AND fingerprint = '$fingerprint' AND eliminado = '0'"
    );
    
    if (count($arrExistingCard) > 0) {
        // Update existing card
        $card_id = $arrExistingCard[0]['id'];
        $fecha_actualizada = date('Y-m-d H:i:s');
        
        $conexion->doUpdate(
            "usuario_payment_methods",
            "payment_method_id = '$payment_method_id', 
             cardholder_name = '$cardholder_name',
             fecha_actualizada = '$fecha_actualizada'",
            "id = '$card_id'"
        );
        
        $response['message'] = 'Card updated successfully';
    } else {
        // Save new payment method
        $fecha_creada = date('Y-m-d H:i:s');
        
        // First, mark any existing default cards as non-default
        $conexion->doUpdate(
            "usuario_payment_methods",
            "is_default = '0'",
            "usuario_id = '$usuario_id' AND eliminado = '0'"
        );
        
        // Insert new payment method as default
        $conexion->doInsert(
            "usuario_payment_methods 
            (usuario_id, payment_method_id, brand, last4, exp_month, exp_year, 
             fingerprint, cardholder_name, is_default, activo, eliminado, 
             compania_id, fecha_creada)",
            "'$usuario_id', '$payment_method_id', '$brand', '$last4', '$exp_month', 
             '$exp_year', '$fingerprint', '$cardholder_name', '1', '1', '0', 
             '$compania', '$fecha_creada'"
        );
        
        $response['message'] = 'Card saved successfully';
    }
    
    // Update user profile data if provided
    if ($usuario_nombre || $usuario_apellido || $usuario_email || $usuario_telefono || $genero) {
        $updateFields = [];
        
        if ($usuario_nombre) {
            $updateFields[] = "usuario_nombre = '$usuario_nombre'";
        }
        if ($usuario_apellido) {
            $updateFields[] = "usuario_apellido = '$usuario_apellido'";
        }
        if ($usuario_email) {
            $updateFields[] = "usuario_email = '$usuario_email'";
        }
        if ($usuario_telefono) {
            $updateFields[] = "usuario_telf = '$usuario_telefono'";
        }
        if ($genero) {
            // Convert gender to sexo_id (1=Male, 2=Female)
            $sexo_id = ($genero === 'Male' || $genero === 'Masculino') ? '1' : '2';
            $updateFields[] = "sexo_id = '$sexo_id'";
        }
        
        if (count($updateFields) > 0) {
            $updateString = implode(', ', $updateFields);
            $conexion->doUpdate(
                "usuario",
                $updateString,
                "usuario_id = '$usuario_id'"
            );
            
            $response['message'] = 'Card and profile saved successfully';
        }
    }
    
    $response['code'] = 0;
    $response['data'] = [
        'payment_method_id' => $payment_method_id,
        'brand' => $brand,
        'last4' => $last4,
        'exp_month' => $exp_month,
        'exp_year' => $exp_year,
        'profile_updated' => ($usuario_nombre || $usuario_apellido || $usuario_email || $usuario_telefono || $genero) ? true : false
    ];
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    $response['message'] = 'Stripe API Error: ' . $e->getMessage();
    error_log('Stripe API Error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('General Error: ' . $e->getMessage());
}

echo json_encode($response);
?>