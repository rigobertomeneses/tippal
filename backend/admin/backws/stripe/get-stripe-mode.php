<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';

// Default response
$valores = array(
    "code" => 101,
    "message" => "Sin permisos",
    "data" => null,
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo == "POST" || $metodo == "GET") {
    // Get parameters
    if ($metodo == "POST") {
        $valoresPost = json_decode(file_get_contents('php://input'), true);
        (isset($valoresPost['compania'])) ? $compania_id = $valoresPost['compania'] : $compania_id = '';
    } else {
        (isset($_GET['compania'])) ? $compania_id = $_GET['compania'] : $compania_id = '';
    }
    
    if ($compania_id == "") {
        $compania_id = 470; // Default company
    }
    
    // Database connection
    $conexion = new ConexionBd();
    
    // Get Stripe mode from company settings
    $arrCompania = $conexion->doSelect(
        "stripe_mode, 
         CASE WHEN stripe_secret_key LIKE 'sk_test_%' THEN 'test' 
              WHEN stripe_secret_key LIKE 'sk_live_%' THEN 'live' 
              ELSE 'unknown' END as detected_mode",
        "compania",
        "compania_id = '$compania_id'"
    );
    
    if (count($arrCompania) > 0) {
        $stripe_mode = $arrCompania[0]['stripe_mode'] ?: $arrCompania[0]['detected_mode'];
        $is_test_mode = ($stripe_mode == 'test' || $arrCompania[0]['detected_mode'] == 'test');
        
        $valores = array(
            "code" => 0,
            "message" => "Stripe mode retrieved successfully",
            "data" => array(
                "mode" => $stripe_mode,
                "is_test_mode" => $is_test_mode,
                "test_numbers" => $is_test_mode ? array(
                    "routing_numbers" => array(
                        "110000000" => "Valid test routing number",
                        "021000021" => "JP Morgan Chase (test)",
                        "011401533" => "Bank of America (test)",
                        "091000019" => "Wells Fargo (test)"
                    ),
                    "account_numbers" => array(
                        "000123456789" => "Valid test account",
                        "000111111113" => "Will fail routing validation",
                        "000111111116" => "Will fail account validation"
                    )
                ) : null
            )
        );
    } else {
        $valores = array(
            "code" => 106,
            "message" => "Company configuration not found",
            "data" => null
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