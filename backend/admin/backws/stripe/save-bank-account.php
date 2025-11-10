<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");

// Include necessary files
include_once '../../lib/mysqlclass.php';

// Define formatoFechaHoraBd function if not exists
if (!function_exists('formatoFechaHoraBd')) {
    function formatoFechaHoraBd($fecha = null, $hora = null, $minuto = null, $segundo = null, $compania_id = null) {
        return date('Y-m-d H:i:s');
    }
}

// Include vendor autoload for Stripe SDK
require_once('../../vendor/autoload.php');

// Default response
$valores = array(
    "code" => 101,
    "message" => "Sin permisos",
    "data" => null,
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo == "POST") {
    // Get POST data
    $valoresPost = json_decode(file_get_contents('php://input'), true);
    
    // Extract parameters
    (isset($valoresPost['usuario_id'])) ? $usuario_id = $valoresPost['usuario_id'] : $usuario_id = '';
    (isset($valoresPost['account_holder_name'])) ? $account_holder_name = $valoresPost['account_holder_name'] : $account_holder_name = '';
    (isset($valoresPost['account_holder_type'])) ? $account_holder_type = $valoresPost['account_holder_type'] : $account_holder_type = 'individual';
    (isset($valoresPost['account_type'])) ? $account_type = $valoresPost['account_type'] : $account_type = 'checking';
    (isset($valoresPost['account_number'])) ? $account_number = $valoresPost['account_number'] : $account_number = '';
    (isset($valoresPost['routing_number'])) ? $routing_number = $valoresPost['routing_number'] : $routing_number = '';
    (isset($valoresPost['is_default'])) ? $is_default = $valoresPost['is_default'] : $is_default = 0;
    (isset($valoresPost['compania'])) ? $compania_id = $valoresPost['compania'] : $compania_id = '';
    (isset($valoresPost['email'])) ? $email = $valoresPost['email'] : $email = '';
    
    // Validate required fields
    if ($usuario_id == "" || $account_holder_name == "" || $account_number == "" || $routing_number == "" || $compania_id == "") {
        $valores = array(
            "code" => 102,
            "message" => "Missing required fields",
            "data" => null,
        );
        echo json_encode($valores);
        exit;
    }
    
    // Extract last 4 digits
    $last4 = substr($account_number, -4);
    
    // Database connection
    $conexion = new ConexionBd();
    
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
    
    // Initialize Stripe with the secret key
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    
    // Debug log - temporary
    error_log("Email received: " . $email);
    error_log("Account holder name: " . $account_holder_name);
    
    // Get user email if not provided
    if ($email == "") {
        $arrUsuario = $conexion->doSelect(
            "usuario_email",
            "usuario",
            "usuario_id = '$usuario_id'"
        );
        
        if (count($arrUsuario) > 0) {
            $email = $arrUsuario[0]['usuario_email'];
        } else {
            // If still no email, we need to fail
            $valores = array(
                "code" => 102,
                "message" => "Email is required for Stripe customer creation",
                "data" => null,
            );
            echo json_encode($valores);
            exit;
        }
    }
    
    try {
        // Get current timestamp for use throughout the function
        $fechaactual = date('Y-m-d H:i:s');
        error_log("Timestamp generated: " . $fechaactual);
        
        // Check if customer exists for this user
        $arrCustomer = $conexion->doSelect(
            "stripe_customer_id",
            "usuario_stripe_customers",
            "usuario_id = '$usuario_id' AND compania_id = '$compania_id'"
        );
        
        if (count($arrCustomer) == 0) {
            // Create new customer
            $customer = \Stripe\Customer::create([
                'email' => $email,
                'name' => $account_holder_name,
                'description' => 'Customer for user ID: ' . $usuario_id,
                'metadata' => [
                    'usuario_id' => $usuario_id,
                    'compania_id' => $compania_id
                ]
            ]);
            
            $stripe_customer_id = $customer->id;
            
            // Save customer to database
            $resultado = $conexion->doInsert(
                "usuario_stripe_customers (usuario_id, stripe_customer_id, compania_id, created_at)",
                "'$usuario_id', '$stripe_customer_id', '$compania_id', '$fechaactual'"
            );
        } else {
            $stripe_customer_id = $arrCustomer[0]['stripe_customer_id'];
        }
        
        // First check if this bank account already exists in our database
        $arrExistingLocal = $conexion->doSelect(
            "bank_account_id, deleted_at",
            "usuario_bank_accounts",
            "usuario_id = '$usuario_id' AND last4 = '$last4' AND routing_number = '$routing_number' AND compania_id = '$compania_id'"
        );
        
        if (count($arrExistingLocal) > 0) {
            // Check if it's deleted
            if ($arrExistingLocal[0]['deleted_at'] != null && $arrExistingLocal[0]['deleted_at'] != '0000-00-00 00:00:00') {
                // Reactivate the deleted account
                error_log("About to update with fechaactual: " . $fechaactual);
                error_log("Bank account ID: " . $arrExistingLocal[0]['bank_account_id']);
                $bank_id = $arrExistingLocal[0]['bank_account_id'];
                $updateResult = $conexion->doUpdate(
                    "usuario_bank_accounts",
                    "deleted_at = NULL, updated_at = '".$fechaactual."'",
                    "bank_account_id = '".$bank_id."'"
                );
                error_log("Update result: " . print_r($updateResult, true));
                
                $valores = array(
                    "code" => 0,
                    "message" => "Bank account reactivated successfully",
                    "data" => array(
                        "bank_account_id" => $arrExistingLocal[0]['bank_account_id'],
                        "reactivated" => true
                    ),
                );
                echo json_encode($valores);
                exit;
            } else {
                // Account already exists and is active
                $valores = array(
                    "code" => 107,
                    "message" => "This bank account is already saved",
                    "data" => null,
                );
                echo json_encode($valores);
                exit;
            }
        }
        
        // Try to create token and attach to customer
        try {
            // Create a bank account token server-side
            $token = \Stripe\Token::create([
                'bank_account' => [
                    'country' => 'US',
                    'currency' => 'usd',
                    'account_holder_name' => $account_holder_name,
                    'account_holder_type' => $account_holder_type,
                    'routing_number' => $routing_number,
                    'account_number' => $account_number,
                ],
            ]);
            
            // Attach the bank account token to the customer
            $bankAccount = \Stripe\Customer::createSource(
                $stripe_customer_id,
                ['source' => $token->id]
            );
            
            // Log the bank account object for debugging
            error_log("Bank Account Created - Full object: " . json_encode($bankAccount));
            error_log("Bank Account Created - ID: " . $bankAccount->id);
            error_log("Bank Account Created - Object type: " . $bankAccount->object);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Check if it's a duplicate error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                // The account exists in Stripe but not in our DB, let's retrieve it
                $customer = \Stripe\Customer::retrieve($stripe_customer_id);
                $sources = $customer->sources->all(['object' => 'bank_account']);
                
                // Find the matching bank account
                $bankAccount = null;
                foreach ($sources->data as $source) {
                    if ($source->last4 == $last4 && $source->routing_number == $routing_number) {
                        $bankAccount = $source;
                        break;
                    }
                }
                
                if (!$bankAccount) {
                    throw new Exception("Bank account exists in Stripe but couldn't retrieve it");
                }
            } else {
                throw $e; // Re-throw if it's a different error
            }
        }
        
        // Get bank account details from Stripe response
        $bank_name = $bankAccount->bank_name ?? '';
        $fingerprint = $bankAccount->fingerprint ?? '';
        $stripe_bank_account_id = $bankAccount->id;
        
        // Check if this bank account already exists (using fingerprint)
        if ($fingerprint != '') {
            $arrExisting = $conexion->doSelect(
                "bank_account_id",
                "usuario_bank_accounts",
                "usuario_id = '$usuario_id' AND fingerprint = '$fingerprint' AND compania_id = '$compania_id'"
            );
            
            if (count($arrExisting) > 0) {
                $valores = array(
                    "code" => 107,
                    "message" => "This bank account is already saved",
                    "data" => null,
                );
                echo json_encode($valores);
                exit;
            }
        }
        
        // If this is set as default, remove default from other accounts
        if ($is_default == 1) {
            $conexion->doUpdate(
                "usuario_bank_accounts",
                "is_default = '0'",
                "usuario_id = '$usuario_id' AND compania_id = '$compania_id'"
            );
        }
        
        // Save bank account to database
        $account_holder_name_decoded = utf8_decode($account_holder_name);
        $bank_name_decoded = utf8_decode($bank_name);
        
        $bank_account_id = $conexion->doInsert(
            "usuario_bank_accounts (usuario_id, stripe_customer_id, stripe_bank_account_id, account_holder_name, account_holder_type, account_type, bank_name, last4, routing_number, fingerprint, currency, country, is_default, status, compania_id, created_at)",
            "'$usuario_id', '$stripe_customer_id', '$stripe_bank_account_id', '$account_holder_name_decoded', 'individual', '$account_type', '$bank_name_decoded', '$last4', '$routing_number', '$fingerprint', 'usd', 'US', '$is_default', 'new', '$compania_id', '$fechaactual'"
        );
        
        if ($bank_account_id > 0) {
            // Initiate micro-deposits for verification (optional)
            // This will send two small deposits that the user needs to verify
            try {
                $verification = $bankAccount->verify(['amounts' => [32, 45]]); // Example amounts
            } catch (Exception $e) {
                // If automatic verification fails, manual verification will be needed
                // This is normal for new accounts
            }
            
            $valores = array(
                "code" => 0,
                "message" => "Bank account saved successfully",
                "data" => array(
                    "bank_account_id" => $bank_account_id,
                    "stripe_bank_account_id" => $stripe_bank_account_id,
                    "status" => "new",
                    "verification_required" => true,
                    "message" => "Your bank account has been added. Verification deposits will be sent within 1-2 business days."
                ),
            );
        } else {
            $valores = array(
                "code" => 105,
                "message" => "Error saving bank account",
                "data" => null,
            );
        }
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe API Error in save-bank-account.php: " . $e->getMessage());
        error_log("Error Code: " . ($e->getError() ? $e->getError()->code : 'N/A'));
        $valores = array(
            "code" => 108,
            "message" => "Stripe error: " . $e->getMessage(),
            "data" => array(
                "type" => "stripe_api_error",
                "error_code" => $e->getError() ? $e->getError()->code : null,
                "error_type" => $e->getError() ? $e->getError()->type : null,
                "decline_code" => $e->getError() ? $e->getError()->decline_code : null
            ),
        );
    } catch (Exception $e) {
        error_log("General Error in save-bank-account.php: " . $e->getMessage());
        error_log("Error Trace: " . $e->getTraceAsString());
        $valores = array(
            "code" => 109,
            "message" => "General error: " . $e->getMessage(),
            "data" => array(
                "type" => "general_error",
                "trace" => $e->getTraceAsString()
            ),
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