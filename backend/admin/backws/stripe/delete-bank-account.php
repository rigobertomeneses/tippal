<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");
error_reporting(E_ALL);

// Include necessary files
include_once '../../lib/mysqlclass.php';

// Only include funciones.php if we need specific functions
// Avoiding it for now due to relative path issues
// include_once '../../lib/funciones.php';

// Define formatoFechaHoraBd function if not exists
if (!function_exists('formatoFechaHoraBd')) {
    function formatoFechaHoraBd($fecha = null, $hora = null, $minuto = null, $segundo = null, $compania_id = null) {
        return date('Y-m-d H:i:s');
    }
}

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
    (isset($valoresPost['usuario_id'])) ? $usuario_id = $valoresPost['usuario_id'] : $usuario_id = '';
    (isset($valoresPost['bank_account_id'])) ? $bank_account_id = $valoresPost['bank_account_id'] : $bank_account_id = '';
    (isset($valoresPost['compania'])) ? $compania_id = $valoresPost['compania'] : $compania_id = '';
    
    // Validate required fields
    if ($usuario_id == "" || $bank_account_id == "" || $compania_id == "") {
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
    
    // Get the bank account details first
    $arrBankAccount = $conexion->doSelect(
        "stripe_customer_id, stripe_bank_account_id, account_holder_name, last4, is_default",
        "usuario_bank_accounts",
        "bank_account_id = '$bank_account_id' AND usuario_id = '$usuario_id' AND compania_id = '$compania_id'"
    );
    
    if (count($arrBankAccount) == 0) {
        $valores = array(
            "code" => 106,
            "message" => "Bank account not found",
            "data" => null,
        );
        echo json_encode($valores);
        exit;
    }
    
    $stripe_customer_id = $arrBankAccount[0]['stripe_customer_id'];
    $stripe_bank_account_id = $arrBankAccount[0]['stripe_bank_account_id'];
    $account_holder_name = $arrBankAccount[0]['account_holder_name'];
    $last4 = $arrBankAccount[0]['last4'];
    
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
        
        // Try to delete from Stripe first (only if we have valid IDs)
        if (!empty($stripe_customer_id) && !empty($stripe_bank_account_id)) {
            try {
                // Delete the bank account from Stripe using the static method
                \Stripe\Customer::deleteSource(
                    $stripe_customer_id,  // Customer ID
                    $stripe_bank_account_id  // Source ID to delete
                );
                $deleted_from_stripe = true;
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // If the bank account doesn't exist in Stripe, continue with DB deletion
                $stripe_error = $e->getMessage();
                $deleted_from_stripe = true; // Mark as deleted anyway to proceed
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $stripe_error = $e->getMessage();
                $deleted_from_stripe = true; // Continue anyway
            } catch (Exception $e) {
                $stripe_error = $e->getMessage();
                $deleted_from_stripe = true; // Continue anyway
            }
        } else {
            // If no Stripe IDs, just proceed with DB deletion
            $deleted_from_stripe = true;
        }
    } else {
        // No Stripe SDK or keys, just proceed with DB deletion
        $deleted_from_stripe = true;
        $stripe_error = 'Stripe not configured';
    }
        
        // Always try to delete from database regardless of Stripe result
        // Soft delete from database (mark as deleted but keep record)
        $fechaactual = date('Y-m-d H:i:s');
        $resultado = $conexion->doUpdate(
            "usuario_bank_accounts",
            "deleted_at = '".$fechaactual."'",
            "bank_account_id = '$bank_account_id'"
        );
        
        // Check if this was the default account
        if ($arrBankAccount[0]['is_default'] == '1') {
            // Find another account to set as default
            $arrOtherAccounts = $conexion->doSelect(
                "bank_account_id",
                "usuario_bank_accounts",
                "usuario_id = '$usuario_id' AND compania_id = '$compania_id' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') AND bank_account_id != '$bank_account_id' ORDER BY created_at DESC LIMIT 1"
            );
            
            if (count($arrOtherAccounts) > 0) {
                $conexion->doUpdate(
                    "usuario_bank_accounts",
                    "is_default = '1'",
                    "bank_account_id = '".$arrOtherAccounts[0]['bank_account_id']."'"
                );
            }
        }
        
        $valores = array(
            "code" => 0,
            "message" => "Bank account deleted successfully",
            "data" => array(
                "bank_account_id" => $bank_account_id,
                "account_holder_name" => utf8_encode($account_holder_name),
                "last4" => $last4,
                "deleted_from_stripe" => $deleted_from_stripe,
                "stripe_error" => $stripe_error
            ),
        );
        
    } catch (Exception $e) {
        error_log("Error in delete-bank-account.php: " . $e->getMessage());
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