<?php
/**
 * Get Stripe Publishable Key for Company
 * Returns the Stripe publishable key stored in the database for a specific company
 */

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// Initialize response array
$response = array(
    'code' => 100,
    'message' => 'Error obteniendo configuración',
    'data' => null
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();

    // Get parameters from JSON body or REQUEST
    $input = json_decode(file_get_contents("php://input"), true);
    $compania = null;

    // Debug: Log input data
    error_log("=== GET PUBLISHABLE KEY DEBUG ===");
    error_log("Input JSON: " . json_encode($input));
    error_log("REQUEST data: " . json_encode($_REQUEST));

    // Try to get compania from JSON body first, then from REQUEST
    if (isset($input['compania']) && !empty($input['compania'])) {
        $compania = $input['compania'];
        error_log("Compania found in JSON: " . $compania);
    } elseif (isset($_REQUEST['compania']) && !empty($_REQUEST['compania'])) {
        $compania = $_REQUEST['compania'];
        error_log("Compania found in REQUEST: " . $compania);
    }

    if (empty($compania)) {
        error_log("ERROR: Compania no especificada");
        $response['message'] = 'Compañía no especificada';
        echo json_encode($response);
        exit();
    }

    error_log("Using compania: " . $compania);
    
    // Get Stripe configuration for the company
    $arrresultado = $conexion->doSelect(
        "compania_id, stripe_publishable_key, stripe_secret_key, stripe_connect_enabled",
        "compania",
        "compania_id = '$compania'"
    );
    
    if (count($arrresultado) == 0) {
        $response['message'] = 'Compañía no encontrada';
        echo json_encode($response);
        exit();
    }
    
    $company_data = $arrresultado[0];
    
    // Check if the publishable key exists
    if (empty($company_data['stripe_publishable_key'])) {
        // No key configured for this company
        $response['code'] = 100;
        $response['message'] = 'Stripe no está configurado para esta compañía';
        $response['data'] = null;
    } else {
        $response['code'] = 0;
        $response['message'] = 'Configuración obtenida exitosamente';
        $response['data'] = array(
            'publishable_key' => $company_data['stripe_publishable_key'],
            'stripe_connect_enabled' => $company_data['stripe_connect_enabled'] == 1,
            'is_test_mode' => strpos($company_data['stripe_publishable_key'], 'pk_test') === 0
        );
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
    error_log('Server Error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>