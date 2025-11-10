<?php
/**
 * Get Saved Payment Methods
 * Retrieves all saved payment methods for a user
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

ini_set("display_errors", "0");

// Include necessary files
include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../models/lista.php';

// Initialize response array
$response = array(
    'code' => 100,
    'message' => 'Error obteniendo tarjetas guardadas',
    'data' => []
);

try {
    // Initialize database connection
    $conexion = new ConexionBd();
    
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Validate required parameters
    $usuario_id = isset($input['usuario_id']) ? $input['usuario_id'] : null;
    $compania = isset($input['compania']) ? $input['compania'] : null;
    
    if (!$usuario_id || !$compania) {
        $response['message'] = 'Usuario ID y Compañía son requeridos';
        echo json_encode($response);
        exit();
    }
    
    // Check if table exists first
    $tableCheck = $conexion->doQuery("SHOW TABLES LIKE 'usuario_payment_methods'");
    
    if (count($tableCheck) > 0) {
        // Table exists, get saved cards
        $arrresultado = $conexion->doSelect(
            "id, payment_method_id, brand, last4, exp_month, exp_year, 
             cardholder_name, is_default, fecha_creada",
            "usuario_payment_methods",
            "usuario_id = '$usuario_id' 
             AND compania_id = '$compania' 
             AND eliminado = '0' 
             AND activo = '1'",
            null,
            "is_default DESC, fecha_creada DESC"
        );
        
        if (count($arrresultado) > 0) {
            $cards = [];
            foreach ($arrresultado as $row) {
                $cards[] = [
                    'id' => $row['id'],
                    'payment_method_id' => $row['payment_method_id'],
                    'brand' => ucfirst($row['brand']),
                    'last4' => $row['last4'],
                    'exp_month' => str_pad($row['exp_month'], 2, '0', STR_PAD_LEFT),
                    'exp_year' => $row['exp_year'],
                    'cardholder_name' => $row['cardholder_name'],
                    'is_default' => $row['is_default'] == '1',
                    'created_at' => $row['fecha_creada']
                ];
            }
            $response['code'] = 0;
            $response['message'] = 'Success';
            $response['data'] = $cards;
        } else {
            // No cards found but that's ok
            $response['code'] = 0;
            $response['message'] = 'No cards found';
            $response['data'] = [];
        }
    } else {
        // Table doesn't exist yet, return empty array
        $response['code'] = 0;
        $response['message'] = 'No saved cards table';
        $response['data'] = [];
    }
    
} catch (Exception $e) {
    error_log('Error in get-saved-cards.php: ' . $e->getMessage());
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Always return a valid response
echo json_encode($response);
?>