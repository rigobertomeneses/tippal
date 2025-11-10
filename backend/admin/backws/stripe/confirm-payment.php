<?php
/**
 * Stripe Connect Payment Confirmation
 * Confirms a payment intent and processes the payment
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include necessary files
include_once '../../lib/config.php';
include_once '../../lib/funciones.php';
include_once '../../vendor/autoload.php'; // Stripe PHP SDK

// Initialize response array
$response = array(
    'code' => 100,
    'message' => 'Error procesando el pago',
    'data' => null
);

try {
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Validate required parameters
    if (!isset($input['payment_intent_client_secret']) || !isset($input['payment_method']) || 
        !isset($input['compania']) || !isset($input['usuario_id'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }
    
    $payment_intent_client_secret = $input['payment_intent_client_secret'];
    $payment_method = $input['payment_method'];
    $compania = $input['compania'];
    $usuario_id = $input['usuario_id'];
    
    // Get Stripe configuration for the company
    $sql = "SELECT stripe_secret_key FROM compania WHERE id = '$compania'";
    $result = mysql_query($sql);
    
    if (!$result || mysql_num_rows($result) == 0) {
        // Use default test key if company not found (for development)
        $stripe_secret_key = 'sk_test_YOUR_SECRET_KEY_HERE';
    } else {
        $company_data = mysql_fetch_assoc($result);
        $stripe_secret_key = $company_data['stripe_secret_key'] ?? 'sk_test_YOUR_SECRET_KEY_HERE';
    }
    
    // Initialize Stripe
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    try {
        // Extract payment intent ID from client secret
        $parts = explode('_secret_', $payment_intent_client_secret);
        $payment_intent_id = $parts[0];
        
        // Create payment method
        $payment_method_obj = \Stripe\PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => $payment_method['card']['number'],
                'exp_month' => $payment_method['card']['exp_month'],
                'exp_year' => $payment_method['card']['exp_year'],
                'cvc' => $payment_method['card']['cvc']
            ],
            'billing_details' => $payment_method['billing_details'] ?? []
        ]);
        
        // Attach payment method to payment intent and confirm
        $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
        
        // Attach payment method
        $intent = \Stripe\PaymentIntent::update(
            $payment_intent_id,
            ['payment_method' => $payment_method_obj->id]
        );
        
        // Confirm the payment
        $intent = \Stripe\PaymentIntent::confirm(
            $payment_intent_id,
            ['payment_method' => $payment_method_obj->id]
        );
        
        // Check payment status
        if ($intent->status === 'succeeded') {
            // Payment successful
            // Update transaction in database
            $sql_update = "UPDATE stripe_transactions 
                          SET status = 'succeeded',
                              payment_method_id = '{$payment_method_obj->id}',
                              updated_at = NOW()
                          WHERE payment_intent_id = '$payment_intent_id'";
            mysql_query($sql_update);
            
            // Get transaction details
            $sql_trans = "SELECT * FROM stripe_transactions WHERE payment_intent_id = '$payment_intent_id'";
            $result_trans = mysql_query($sql_trans);
            $trans_data = mysql_fetch_assoc($result_trans);
            
            // If there's a destination user (Stripe Connect), update their balance
            if (!empty($trans_data['destination_usuario_id'])) {
                $amount_in_dollars = $trans_data['amount'] / 100;
                $fee_in_dollars = $trans_data['application_fee'] / 100;
                $net_amount = $amount_in_dollars - $fee_in_dollars;
                
                // Update destination user balance
                $sql_balance = "UPDATE usuario 
                               SET balance = balance + $net_amount 
                               WHERE id = '{$trans_data['destination_usuario_id']}'";
                mysql_query($sql_balance);
                
                // Create movement record for destination user
                $sql_movement = "INSERT INTO movimiento (
                    compania,
                    usuario,
                    tipo,
                    monto,
                    descripcion,
                    referencia,
                    fecha,
                    estatus
                ) VALUES (
                    '$compania',
                    '{$trans_data['destination_usuario_id']}',
                    'credito',
                    '$net_amount',
                    'Pago recibido via Stripe Connect',
                    '$payment_intent_id',
                    NOW(),
                    1
                )";
                mysql_query($sql_movement);
                
                // Create movement record for platform fee
                if ($fee_in_dollars > 0) {
                    $sql_fee = "INSERT INTO movimiento (
                        compania,
                        usuario,
                        tipo,
                        monto,
                        descripcion,
                        referencia,
                        fecha,
                        estatus
                    ) VALUES (
                        '$compania',
                        '0', -- Platform account
                        'credito',
                        '$fee_in_dollars',
                        'Comisión de plataforma',
                        '$payment_intent_id',
                        NOW(),
                        1
                    )";
                    mysql_query($sql_fee);
                }
            }
            
            // Create movement record for sender
            $amount_in_dollars = $trans_data['amount'] / 100;
            $sql_sender_movement = "INSERT INTO movimiento (
                compania,
                usuario,
                tipo,
                monto,
                descripcion,
                referencia,
                fecha,
                estatus
            ) VALUES (
                '$compania',
                '$usuario_id',
                'debito',
                '$amount_in_dollars',
                'Pago enviado via Stripe',
                '$payment_intent_id',
                NOW(),
                1
            )";
            mysql_query($sql_sender_movement);
            
            $response['code'] = 0;
            $response['message'] = 'Pago procesado exitosamente';
            $response['data'] = array(
                'payment_intent_id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount,
                'currency' => $intent->currency
            );
            
        } elseif ($intent->status === 'requires_action' || $intent->status === 'requires_source_action') {
            // 3D Secure authentication required
            $response['code'] = 1;
            $response['message'] = 'Se requiere autenticación adicional';
            $response['data'] = array(
                'payment_intent_id' => $intent->id,
                'status' => $intent->status,
                'client_secret' => $intent->client_secret,
                'requires_action' => true
            );
            
        } else {
            // Payment failed
            $response['code'] = 100;
            $response['message'] = 'El pago no pudo ser procesado. Estado: ' . $intent->status;
            
            // Update transaction status
            $sql_update = "UPDATE stripe_transactions 
                          SET status = 'failed',
                              updated_at = NOW()
                          WHERE payment_intent_id = '$payment_intent_id'";
            mysql_query($sql_update);
        }
        
    } catch (\Stripe\Exception\CardException $e) {
        // Card was declined
        $response['message'] = 'Tarjeta rechazada: ' . $e->getMessage();
        error_log('Card Error: ' . $e->getMessage());
        
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        // Invalid parameters
        $response['message'] = 'Solicitud inválida: ' . $e->getMessage();
        error_log('Invalid Request: ' . $e->getMessage());
        
    } catch (\Stripe\Exception\AuthenticationException $e) {
        // Authentication with Stripe failed
        $response['message'] = 'Error de autenticación con Stripe';
        error_log('Authentication Error: ' . $e->getMessage());
        
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        // Network communication with Stripe failed
        $response['message'] = 'Error de conexión con Stripe';
        error_log('Network Error: ' . $e->getMessage());
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Generic Stripe error
        $response['message'] = 'Error de Stripe: ' . $e->getMessage();
        error_log('Stripe API Error: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
    error_log('Server Error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>