<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../../lib/mysqlclass.php';
include_once '../comprobartoken.php';

$db = new ConexionBd();

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

$token = isset($obj['token']) ? $obj['token'] : '';
$compania = isset($obj['compania']) ? $obj['compania'] : '';
$tipo = isset($obj['tipo']) ? $obj['tipo'] : '';
$valor = isset($obj['valor']) ? addslashes(trim($obj['valor'])) : '';

// Verificar token
$datosUsuario = comprobartoken($token, $compania);
if (!$datosUsuario) {
    echo json_encode(array(
        'code' => 104,
        'message' => 'Token inválido o expirado'
    ));
    exit();
}

$usuario_id = $datosUsuario['usuario_id'];

// Validar que se proporcionen tipo y valor
if (empty($tipo) || empty($valor)) {
    echo json_encode(array(
        'code' => 400,
        'message' => 'Tipo y valor son requeridos'
    ));
    exit();
}

try {
    $where = "compania_id = '$compania' 
              AND usuario_eliminado = 0 
              AND usuario_activo = 1
              AND usuario_id != '$usuario_id'"; // No buscar el propio usuario
    
    // Construir la condición según el tipo de búsqueda
    switch ($tipo) {
        case 'email':
            $where .= " AND LOWER(usuario_email) = LOWER('$valor')";
            break;
        case 'alias':
            $where .= " AND LOWER(usuario_alias) = LOWER('$valor')";
            break;
        case 'cbu':
            $where .= " AND usuario_cbu = '$valor'";
            break;
        default:
            echo json_encode(array(
                'code' => 400,
                'message' => 'Tipo de búsqueda no válido'
            ));
            exit();
    }
    
    // Buscar el usuario
    $usuarios = $db->doSelect(
        "usuario_id as id,
         usuario_nombre as nombre,
         usuario_email as email,
         usuario_alias as alias,
         usuario_cbu as cbu,
         usuario_imagen as imagen,
         usuario_verificado as verificado",
        "usuario",
        $where,
        null,
        null
    );
    
    if ($usuarios && count($usuarios) > 0) {
        $usuario = $usuarios[0];
        
        // Si no tiene imagen, generar una con las iniciales
        if (empty($usuario['imagen'])) {
            $nombre = $usuario['nombre'] ? $usuario['nombre'] : 'Usuario';
            $usuario['imagen'] = "https://ui-avatars.com/api/?name=" . urlencode($nombre) . "&background=random";
        }
        
        // Ocultar parte del CBU por seguridad
        if (!empty($usuario['cbu'])) {
            $usuario['cbu'] = substr($usuario['cbu'], 0, 4) . str_repeat('*', strlen($usuario['cbu']) - 8) . substr($usuario['cbu'], -4);
        }
        
        // Convertir verificado a booleano
        $usuario['verificado'] = ($usuario['verificado'] == 1);
        
        echo json_encode(array(
            'code' => 0,
            'message' => 'Cuenta encontrada',
            'data' => $usuario
        ));
    } else {
        echo json_encode(array(
            'code' => 404,
            'message' => 'No se encontró ninguna cuenta con esos datos'
        ));
    }
    
} catch (Exception $e) {
    echo json_encode(array(
        'code' => 500,
        'message' => 'Error al buscar cuenta: ' . $e->getMessage()
    ));
}
?>