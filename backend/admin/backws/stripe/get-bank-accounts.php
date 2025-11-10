<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");

// Include necessary files
include_once '../../lib/mysqlclass.php';

// Default response
$valores = array(
    "code" => 101,
    "message" => "Sin permisos",
    "data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo == "POST") {
    // Get POST data
    $valoresPost = json_decode(file_get_contents('php://input'), true);
    
    // Extract parameters
    (isset($valoresPost['usuario_id'])) ? $usuario_id = $valoresPost['usuario_id'] : $usuario_id = '';
    (isset($valoresPost['compania'])) ? $compania_id = $valoresPost['compania'] : $compania_id = '';
    
    // Validate required fields
    if ($usuario_id == "" || $compania_id == "") {
        $valores = array(
            "code" => 102,
            "message" => "Missing required fields",
            "data" => [],
        );
        echo json_encode($valores);
        exit;
    }
    
    // Database connection
    $conexion = new ConexionBd();
    
    // Get saved bank accounts for this user (excluding deleted ones)
    $arrBankAccounts = $conexion->doSelect(
        "bank_account_id,
         stripe_customer_id,
         stripe_bank_account_id,
         account_holder_name,
         account_holder_type,
         account_type,
         bank_name,
         last4,
         routing_number,
         currency,
         country,
         is_default,
         status,
         created_at,
         verified_at",
        "usuario_bank_accounts",
        "usuario_id = '$usuario_id' AND compania_id = '$compania_id' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') ORDER BY is_default DESC, created_at DESC"
    );
    
    $bankAccounts = array();
    
    foreach($arrBankAccounts as $account) {
        $bankAccounts[] = array(
            "bank_account_id" => $account["bank_account_id"],
            "stripe_customer_id" => $account["stripe_customer_id"],
            "stripe_bank_account_id" => $account["stripe_bank_account_id"],
            "account_holder_name" => utf8_encode($account["account_holder_name"]),
            "account_holder_type" => $account["account_holder_type"],
            "account_type" => $account["account_type"],
            "bank_name" => utf8_encode($account["bank_name"]),
            "last4" => $account["last4"],
            "routing_number" => $account["routing_number"],
            "currency" => $account["currency"],
            "country" => $account["country"],
            "is_default" => $account["is_default"],
            "status" => $account["status"],
            "created_at" => $account["created_at"],
            "verified_at" => $account["verified_at"],
            "display_name" => utf8_encode($account["bank_name"]) . " ••••" . $account["last4"]
        );
    }
    
    $valores = array(
        "code" => 0,
        "message" => "Bank accounts retrieved successfully",
        "data" => $bankAccounts,
        "count" => count($bankAccounts)
    );
    
} else {
    $valores = array(
        "code" => 101,
        "message" => "Method not allowed",
        "data" => [],
    );
}

echo json_encode($valores);
?>