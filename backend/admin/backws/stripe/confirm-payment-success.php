<?php
/**
 * Confirm Payment Success
 * Confirms that a payment was successful and updates the database accordingly
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

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../models/lista.php';
require_once '../../vendor/autoload.php'; // Stripe PHP SDK

// Initialize response array
$response = array(
    'code' => 100,
    'message' => 'Error confirmando el pago',
    'data' => null
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Validate required parameters
    if (!isset($input['payment_intent_id']) || !isset($input['compania']) || 
        !isset($input['usuario_id'])) {
        $response['message'] = 'Faltan parámetros requeridos';
        echo json_encode($response);
        exit();
    }
    
    $payment_intent_id = $input['payment_intent_id'];
    $compania = $input['compania'];
    $usuario_id = $input['usuario_id'];
    $amount = isset($input['amount']) ? intval($input['amount']) : 0;
    
    // Get Stripe configuration for the company
    $arrresultado = $conexion->doSelect(
        "stripe_secret_key",
        "compania",
        "compania_id = '$compania'"
    );
    
    if (count($arrresultado) == 0) {
        $response['message'] = 'Configuración de Stripe no encontrada';
        echo json_encode($response);
        exit();
    }
    
    $company_data = $arrresultado[0];
    $stripe_secret_key = $company_data['stripe_secret_key'];
    
    if (empty($stripe_secret_key)) {
        $response['message'] = 'Clave secreta de Stripe no configurada';
        echo json_encode($response);
        exit();
    }
    
    // Initialize Stripe
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    try {
        // Retrieve the payment intent from Stripe
        $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
        
        if ($intent->status === 'succeeded') {
            // Check if transaction already exists
            $arrTransaccion = $conexion->doSelect(
                "transaction_id",
                "stripe_transactions",
                "payment_intent_id = '$payment_intent_id'"
            );
            
            $fecha_actual = date('Y-m-d H:i:s');
            
            if (count($arrTransaccion) == 0) {
                // Log the transaction
                $conexion->doInsert(
                    "stripe_transactions (compania_id, usuario_id, payment_intent_id, amount, currency, status, created_at)",
                    "'$compania', '$usuario_id', '$payment_intent_id', '{$intent->amount}', '{$intent->currency}', 'succeeded', '$fecha_actual'"
                );
            } else {
                // Update existing transaction
                $conexion->doUpdate(
                    "stripe_transactions",
                    "status = 'succeeded', updated_at = '$fecha_actual'",
                    "payment_intent_id = '$payment_intent_id'"
                );
            }
            
            // Update user balance for deposits
            if ($amount > 0) {
                $amount_in_dollars = $amount / 100;
                
                // Get current balance from usuariobalance table
                $arrBalance = $conexion->doSelect(
                    "usuariobalance_total, usuariobalance_bloqueado, usuariobalance_disponible, usuariobalance_pendiente, usuariobalance_id",
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
                    
                    // Update usuariobalance
                    $resultado = $conexion->doUpdate(
                        "usuariobalance",
                        "usuariobalance_total = '$new_total', usuariobalance_disponible = '$new_disponible'",
                        "usuario_id = '$usuario_id' and usuariobalance_eliminado = '0'"
                    );
                } else {
                    // Create new balance record if it doesn't exist
                    $resultado = $conexion->doInsert(
                        "usuariobalance (usuario_id, usuariobalance_total, usuariobalance_disponible, usuariobalance_bloqueado, usuariobalance_pendiente, usuariobalance_activo, usuariobalance_eliminado, cuenta_id, compania_id)",
                        "'$usuario_id', '$amount_in_dollars', '$amount_in_dollars', '0', '0', '1', '0', '0', '$compania'"
                    );
                    $usuariobalance_id = $resultado;
                }
                
                // Get user info for movement record
                $arrUsuario = $conexion->doSelect(
                    "cuenta_id",
                    "usuario",
                    "usuario_id = '$usuario_id'"
                );
                $cuenta_id = count($arrUsuario) > 0 ? $arrUsuario[0]['cuenta_id'] : 0;
                
                // Get moneda from configuration or use default
                $moneda = ObtenerMonedaPrincipalId($cuenta_id, $compania);
                if ($moneda == "0" || $moneda == "") {
                    // Default moneda ID - you may need to adjust this based on your system
                    $moneda = "1"; // Usually USD
                }
                
                // Initialize Lista class for getting list IDs
                $instancialista = new Lista();
                
                // Get forma de pago ID for Stripe
                $lista_cod = 999; // Code for Stripe/Card payments
                $arrFormaPago = $conexion->doSelect(
                    "formapago.lista_id as formapago_id",
                    "lista formapago 
                        inner join lista formapagorel on formapagorel.lista_id = formapago.lista_idrel",
                    "formapagorel.lista_cod = '$lista_cod' and formapago.compania_id = '$compania'"
                );
                $formapago_id = count($arrFormaPago) > 0 ? $arrFormaPago[0]['formapago_id'] : 0;
                
                // Get tipo movimiento for deposit/payment received
                $obtenerCodigoLista = 2; // Pago realizado
                $obtenerTipoLista = 269; // Tipo de Movimiento
                $tipomovimiento = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
                
                // Get estatus for approved payment
                $obtenerCodigoLista = 2; // Aprobado
                $obtenerTipoLista = 55; // Estatus de Pagos
                $estatussidpago = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
                
                // Create movement record
                $resultado = $conexion->doInsert(
                    "movimiento (mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha, 
                    elemento_id, l_moneda_id, l_formapago_id, mov_fechareg, mov_activo, 
                    mov_eliminado, cuenta_id, compania_id, l_estatus_id)",
                    "'Depósito confirmado via Stripe', '$tipomovimiento', '$usuario_id', '$amount_in_dollars', '$fecha_actual', 
                    '$usuariobalance_id', '$moneda', '$formapago_id', '$fecha_actual', '1', 
                    '0', '$cuenta_id', '$compania', '$estatussidpago'"
                );
            }
            
            $response['code'] = 0;
            $response['message'] = 'Pago confirmado exitosamente';
            $response['data'] = array(
                'payment_intent_id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount,
                'currency' => $intent->currency
            );
            
        } else {
            $response['message'] = 'El pago no está en estado exitoso. Estado actual: ' . $intent->status;
        }
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
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