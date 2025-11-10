<?php
/**
 * Stripe Token Payment Processing
 * Processes a payment using a tokenized card from Stripe Elements
 */

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('content-type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set("display_errors", "0");

// Log incoming request for debugging
error_log('Process Payment Token - Request received at ' . date('Y-m-d H:i:s'));
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request body: ' . file_get_contents("php://input"));

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../models/lista.php';
require_once '../../vendor/autoload.php'; // Stripe PHP SDK

// Initialize response array
$response = array(
    'code' => 100,
    'message' => 'Error procesando el pago',
    'data' => null
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Validate required parameters
    if (!isset($input['token']) || !isset($input['amount']) || 
        !isset($input['compania']) || !isset($input['usuario_id'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }
    
    $token = $input['token'];
    $amount = intval($input['amount']); // Amount in cents
    $currency = isset($input['currency']) ? $input['currency'] : 'usd';
    $description = isset($input['description']) ? $input['description'] : 'Payment via TipPal';
    $compania = $input['compania'];
    $usuario_id = $input['usuario_id'];
    $metadata = isset($input['metadata']) ? $input['metadata'] : array();
    
    // Validate amount
    if ($amount <= 0) {
        $response['message'] = 'El monto debe ser mayor a 0';
        echo json_encode($response);
        exit();
    }
    
    // Get Stripe configuration for the company from database
    $arrresultado = $conexion->doSelect(
        "stripe_secret_key, stripe_publishable_key, stripe_connect_enabled",
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
    
    // Initialize Stripe with the company's secret key
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    // Get user information
    $arrUsuario = $conexion->doSelect(
        "usuario_id, usuario_nombre, usuario_email, stripe_customer_id",
        "usuario",
        "usuario_id = '$usuario_id'"
    );
    
    if (count($arrUsuario) == 0) {
        $response['message'] = 'Usuario no encontrado';
        echo json_encode($response);
        exit();
    }
    
    $user_data = $arrUsuario[0];
    
    // Create or retrieve Stripe customer (optional - for record keeping)
    $stripe_customer_id = $user_data['stripe_customer_id'];
    if (empty($stripe_customer_id)) {
        try {
            // Create customer without attaching the token as source
            // Tokens are one-time use and will be used directly in the charge
            $customer = \Stripe\Customer::create([
                'email' => $user_data['usuario_email'],
                'name' => $user_data['usuario_nombre'],
                // Don't attach token here - use it directly in charge
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
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Customer creation is optional, log but don't fail
            error_log('Could not create Stripe customer: ' . $e->getMessage());
            // Continue without customer ID
            $stripe_customer_id = null;
        }
    }
    
    try {
        // Create the charge using the token
        // If we have a customer, don't use both customer and source
        // Use either source (token) OR customer (for saved cards)
        $chargeParams = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'metadata' => array_merge($metadata, [
                'usuario_id' => $usuario_id,
                'compania_id' => $compania,
                'type' => 'token_payment'
            ])
        ];
        
        // Use token directly as source (don't use customer parameter with new token)
        $chargeParams['source'] = $token;
        
        // Optionally associate with customer for record keeping
        if (!empty($stripe_customer_id)) {
            // Note: We're NOT using customer parameter here to avoid the error
            $chargeParams['metadata']['stripe_customer_id'] = $stripe_customer_id;
        }
        
        $charge = \Stripe\Charge::create($chargeParams);
        
        if ($charge->status === 'succeeded') {
            // Payment successful
            // Log the transaction - removed stripe_transactions table insert
            // The movimiento table insert is handled below
            $fecha_actual = date('Y-m-d H:i:s');
            
            // Update user balance if this is a deposit or tip payment
            $usuarioRecibeId = isset($metadata['usuarioRecibeId']) ? $metadata['usuarioRecibeId'] : null;
            $tipo_transaccion = isset($metadata['tipo_transaccion']) ? $metadata['tipo_transaccion'] : 'deposito';
            
            if ($tipo_transaccion === 'deposito' || $usuarioRecibeId) {
                $amount_in_dollars = $amount / 100;
                
                // Initialize Lista class
                $instancialista = new Lista();
                
                // Get user info
                $arrUsuarioInfo = $conexion->doSelect(
                    "cuenta_id",
                    "usuario",
                    "usuario_id = '$usuario_id' AND compania_id = '$compania'"
                );
                $cuenta_id = count($arrUsuarioInfo) > 0 ? $arrUsuarioInfo[0]['cuenta_id'] : 0;
                
                // Get moneda
                $moneda = ObtenerMonedaPrincipalId($cuenta_id, $compania);
                if ($moneda == "0" || $moneda == "") {
                    $moneda = "1"; // Default USD
                }
                
                // Get forma de pago ID for Stripe
                $lista_cod = 999; // Code for Stripe/Card payments
                $arrFormaPago = $conexion->doSelect(
                    "formapago.lista_id as formapago_id",
                    "lista formapago 
                        inner join lista formapagorel on formapagorel.lista_id = formapago.lista_idrel",
                    "formapagorel.lista_cod = '$lista_cod' and formapago.compania_id = '$compania'"
                );
                $formapago_id = count($arrFormaPago) > 0 ? $arrFormaPago[0]['formapago_id'] : 0;
                
                // Get tipo movimiento and estatus
                $obtenerCodigoLista = 2; // Pago realizado
                $obtenerTipoLista = 269; // Tipo de Movimiento
                $tipomovimiento = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
                
                $obtenerCodigoLista = 2; // Aprobado
                $obtenerTipoLista = 55; // Estatus de Pagos
                $estatussidpago = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
                
                if ($usuarioRecibeId) {
                    // This is a tip payment - deduct from sender, add to receiver
                    
                    // Deduct from sender's balance
                    $arrBalanceOrigen = $conexion->doSelect(
                        "usuariobalance_total, usuariobalance_disponible, usuariobalance_id",
                        "usuariobalance",
                        "usuariobalance_eliminado = '0' and usuario_id = '$usuario_id'"
                    );
                    
                    if (count($arrBalanceOrigen) > 0) {
                        $usuariobalance_id_origen = $arrBalanceOrigen[0]["usuariobalance_id"];
                        $usuariobalance_total = floatval($arrBalanceOrigen[0]["usuariobalance_total"]);
                        $usuariobalance_disponible = floatval($arrBalanceOrigen[0]["usuariobalance_disponible"]);
                        
                        $new_total = $usuariobalance_total - $amount_in_dollars;
                        $new_disponible = $usuariobalance_disponible - $amount_in_dollars;
                        
                        $conexion->doUpdate(
                            "usuariobalance",
                            "usuariobalance_total = '$new_total', usuariobalance_disponible = '$new_disponible'",
                            "usuario_id = '$usuario_id' and usuariobalance_eliminado = '0'"
                        );
                    }
                    
                    // Add to receiver's balance
                    $arrBalanceDestino = $conexion->doSelect(
                        "usuariobalance_total, usuariobalance_disponible, usuariobalance_id",
                        "usuariobalance",
                        "usuariobalance_eliminado = '0' and usuario_id = '$usuarioRecibeId'"
                    );
                    
                    if (count($arrBalanceDestino) > 0) {
                        $usuariobalance_id_destino = $arrBalanceDestino[0]["usuariobalance_id"];
                        $usuariobalancedestino_total = floatval($arrBalanceDestino[0]["usuariobalance_total"]);
                        $usuariobalancedestino_disponible = floatval($arrBalanceDestino[0]["usuariobalance_disponible"]);
                        
                        $new_total_destino = $usuariobalancedestino_total + $amount_in_dollars;
                        $new_disponible_destino = $usuariobalancedestino_disponible + $amount_in_dollars;
                        
                        $conexion->doUpdate(
                            "usuariobalance",
                            "usuariobalance_total = '$new_total_destino', usuariobalance_disponible = '$new_disponible_destino'",
                            "usuario_id = '$usuarioRecibeId' and usuariobalance_eliminado = '0'"
                        );
                    } else {
                        // Create balance record for receiver if doesn't exist
                        $resultado = $conexion->doInsert(
                            "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_activo, usuariobalance_eliminado, cuenta_id, compania_id)",
                            "'$usuarioRecibeId', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '1', '0', '0', '$compania'"
                        );
                        $usuariobalance_id_destino = $resultado;
                    }
                    
                    // Create movement records for both users
                    $conexion->doInsert(
                        "movimiento (mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha, 
                        elemento_id, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, 
                        mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                        "'Propina enviada via Stripe', '$tipomovimiento', '$usuario_id', '-$amount_in_dollars', '$fecha_actual', 
                        '$usuariobalance_id_origen', '$moneda', '$formapago_id', '$fecha_actual', '1', 
                        '0', '$cuenta_id', '$compania', '$estatussidpago'"
                    );
                    
                    $conexion->doInsert(
                        "movimiento (mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha, 
                        elemento_id, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, 
                        mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                        "'Propina recibida via Stripe', '$tipomovimiento', '$usuarioRecibeId', '$amount_in_dollars', '$fecha_actual', 
                        '$usuariobalance_id_destino', '$moneda', '$formapago_id', '$fecha_actual', '1', 
                        '0', '$cuenta_id', '$compania', '$estatussidpago'"
                    );
                    
                } else {
                    // This is a regular deposit
                    
                    // Get current balance from usuariobalance table
                    $arrBalance = $conexion->doSelect(
                        "usuariobalance_total, usuariobalance_disponible, usuariobalance_id",
                        "usuariobalance",
                        "usuariobalance_eliminado = '0' and usuario_id = '$usuario_id'"
                    );
                    
                    if (count($arrBalance) > 0) {
                        // Update existing balance
                        $usuariobalance_id = $arrBalance[0]["usuariobalance_id"];
                        $usuariobalance_total = floatval($arrBalance[0]["usuariobalance_total"]);
                        $usuariobalance_disponible = floatval($arrBalance[0]["usuariobalance_disponible"]);
                        
                        $new_total = $usuariobalance_total + $amount_in_dollars;
                        $new_disponible = $usuariobalance_disponible + $amount_in_dollars;
                        
                        $conexion->doUpdate(
                            "usuariobalance",
                            "usuariobalance_total = '$new_total', usuariobalance_disponible = '$new_disponible'",
                            "usuario_id = '$usuario_id' and usuariobalance_eliminado = '0'"
                        );
                    } else {
                        // Create new balance record if it doesn't exist
                        $resultado = $conexion->doInsert(
                            "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_activo, usuariobalance_eliminado, cuenta_id, compania_id)",
                            "'$usuario_id', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '1', '0', '$cuenta_id', '$compania'"
                        );
                        $usuariobalance_id = $resultado;
                    }
                    
                    // Create movement record
                    $conexion->doInsert(
                        "movimiento (mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha, 
                        elemento_id, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, 
                        mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                        "'Depósito via Stripe', '$tipomovimiento', '$usuario_id', '$amount_in_dollars', '$fecha_actual', 
                        '$usuariobalance_id', '$moneda', '$formapago_id', '$fecha_actual', '1', 
                        '0', '$cuenta_id', '$compania', '$estatussidpago'"
                    );
                }
            }
            
            $response['code'] = 0;
            $response['message'] = 'Pago procesado exitosamente';
            $response['data'] = array(
                'charge_id' => $charge->id,
                'status' => $charge->status,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'last4' => isset($charge->source->last4) ? $charge->source->last4 : null,
                'brand' => isset($charge->source->brand) ? $charge->source->brand : null
            );
            
        } else {
            // Payment failed
            $response['message'] = 'El pago no pudo ser procesado. Estado: ' . $charge->status;
            
            // Log failed transaction - removed stripe_transactions table insert
            // Failed transactions are not stored in movimiento
            $fecha_actual = date('Y-m-d H:i:s');
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