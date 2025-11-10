<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");

// Include necessary files
include_once '../../lib/mysqlclass.php';

// Include vendor autoload for Stripe SDK if it exists
if (file_exists('../../vendor/autoload.php')) {
    require_once('../../vendor/autoload.php');
}

// Default response
$valores = array(
    "code" => 101,
    "message" => "Sin permisos",
    "data" => null,
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo == "POST" || $metodo == "DELETE") {
    try {
        // Get POST data
        $valoresPost = json_decode(file_get_contents('php://input'), true);
        
        // Extract parameters
        $usuario_id = isset($valoresPost['usuario_id']) ? $valoresPost['usuario_id'] : '';
        $payment_method_id = isset($valoresPost['payment_method_id']) ? $valoresPost['payment_method_id'] : '';
        $compania_id = isset($valoresPost['compania']) ? $valoresPost['compania'] : '';
        
        // Validate required fields
        if ($usuario_id == "" || $payment_method_id == "" || $compania_id == "") {
            $valores = array(
                "code" => 102,
                "message" => "Missing required fields",
                "data" => null,
            );
            echo json_encode($valores);
            exit;
        }
        
        // Database connection
        $conexion = new ConexionBd();
        
        // Get the card details first
        $arrCard = $conexion->doSelect(
            "payment_method_id, cardholder_name, last4, is_default",
            "usuario_payment_methods",
            "payment_method_id = '$payment_method_id' AND usuario_id = '$usuario_id' AND compania_id = '$compania_id' AND eliminado = '0'"
        );
        
        if (count($arrCard) == 0) {
            $valores = array(
                "code" => 106,
                "message" => "Card not found",
                "data" => null,
            );
            echo json_encode($valores);
            exit;
        }
        
        $cardholder_name = $arrCard[0]['cardholder_name'];
        $last4 = $arrCard[0]['last4'];
        $is_default = $arrCard[0]['is_default'];
        
        // Get stripe_customer_id from usuario_stripe_customers table
        $arrCustomer = $conexion->doSelect(
            "stripe_customer_id",
            "usuario_stripe_customers",
            "usuario_id = '$usuario_id' AND compania_id = '$compania_id'"
        );
        
        $stripe_customer_id = count($arrCustomer) > 0 ? $arrCustomer[0]['stripe_customer_id'] : null;
        
        // Get Stripe keys from company settings
        $arrCompania = $conexion->doSelect(
            "stripe_secret_key, stripe_publishable_key, stripe_mode",
            "compania",
            "compania_id = '$compania_id'"
        );
        
        if (count($arrCompania) == 0) {
            $valores = array(
                "code" => 106,
                "message" => "Company configuration not found",
                "data" => null,
            );
            echo json_encode($valores);
            exit;
        }
        
        $stripe_secret_key = $arrCompania[0]['stripe_secret_key'];
        $stripe_mode = $arrCompania[0]['stripe_mode'];
        
        $deleted_from_stripe = false;
        $stripe_error = null;
        
        // Only try Stripe operations if we have the SDK and keys
        if (class_exists('\Stripe\Stripe') && !empty($stripe_secret_key)) {
            // Initialize Stripe with the secret key
            \Stripe\Stripe::setApiKey($stripe_secret_key);
            
            // Try to detach the payment method from the customer
            if (!empty($payment_method_id)) {
                try {
                    // Detach the payment method from the customer
                    $paymentMethod = \Stripe\PaymentMethod::retrieve($payment_method_id);
                    $paymentMethod->detach();
                    $deleted_from_stripe = true;
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    // If the payment method doesn't exist in Stripe, continue with DB deletion
                    $stripe_error = "Stripe: " . $e->getMessage();
                    error_log("Stripe deletion error (InvalidRequest): " . $stripe_error);
                    // Continue with DB deletion anyway
                    $deleted_from_stripe = false;
                } catch (Exception $e) {
                    // Log error but continue with DB deletion
                    $stripe_error = "Error: " . $e->getMessage();
                    error_log("Stripe deletion error (General): " . $stripe_error);
                    $deleted_from_stripe = false;
                }
            }
        }
        
        // Always try to delete from database regardless of Stripe result
        // Soft delete from database (mark as deleted but keep record)
        $fechaactual = date('Y-m-d H:i:s');
        $resultado = $conexion->doUpdate(
            "usuario_payment_methods",
            "eliminado = '1', fecha_eliminada = '".$fechaactual."'",
            "payment_method_id = '$payment_method_id' AND usuario_id = '$usuario_id'"
        );
        
        // Check if this was the default card
        if ($is_default == '1') {
            // Find another card to set as default
            $arrOtherCards = $conexion->doSelect(
                "payment_method_id",
                "usuario_payment_methods",
                "usuario_id = '$usuario_id' AND compania_id = '$compania_id' AND eliminado = '0' AND payment_method_id != '$payment_method_id' ORDER BY fecha_creada DESC LIMIT 1"
            );
            
            if (count($arrOtherCards) > 0) {
                $conexion->doUpdate(
                    "usuario_payment_methods",
                    "is_default = '1'",
                    "payment_method_id = '".$arrOtherCards[0]['payment_method_id']."'"
                );
            }
        }
        
        $valores = array(
            "code" => 0,
            "message" => "Card deleted successfully",
            "data" => array(
                "payment_method_id" => $payment_method_id,
                "cardholder_name" => utf8_encode($cardholder_name),
                "last4" => $last4,
                "deleted_from_stripe" => $deleted_from_stripe,
                "stripe_error" => $stripe_error
            ),
        );
        
    } catch (Exception $e) {
        error_log("Error in delete-card.php: " . $e->getMessage());
        $valores = array(
            "code" => 109,
            "message" => "Error: " . $e->getMessage(),
            "data" => null,
        );
    }
} else {
    $valores = array(
        "code" => 101,
        "message" => "Method not allowed",
        "data" => null,
    );
}

echo json_encode($valores);
?>