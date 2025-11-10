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
$contacto_usuario_id = isset($obj['contacto_usuario_id']) ? intval($obj['contacto_usuario_id']) : 0;
$alias_personalizado = isset($obj['alias_personalizado']) ? addslashes(trim($obj['alias_personalizado'])) : '';
$accion = isset($obj['accion']) ? $obj['accion'] : 'agregar'; // agregar, eliminar, actualizar

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

if ($contacto_usuario_id <= 0) {
    echo json_encode(array(
        'code' => 400,
        'message' => 'ID de contacto inválido'
    ));
    exit();
}

try {
    // Verificar que la tabla de contactos_transferencias existe
    $db->doQuery("CREATE TABLE IF NOT EXISTS `contactos_transferencias` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `usuario_id` int(11) NOT NULL,
        `contacto_usuario_id` int(11) NOT NULL,
        `alias_personalizado` varchar(100) DEFAULT NULL,
        `fecha_agregado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ultima_transferencia` datetime DEFAULT NULL,
        `total_transferencias` int(11) DEFAULT 0,
        `favorito` tinyint(1) DEFAULT 0,
        `activo` tinyint(1) DEFAULT 1,
        `compania_id` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_contacto_usuario` (`usuario_id`, `contacto_usuario_id`, `compania_id`),
        KEY `idx_usuario_compania` (`usuario_id`, `compania_id`),
        KEY `idx_ultima_transferencia` (`ultima_transferencia`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $fecha_actual = date('Y-m-d H:i:s');

    switch ($accion) {
        case 'agregar':
            // Verificar que el contacto existe y es diferente al usuario actual
            $contactos = $db->doSelect(
                "usuario_id",
                "usuario",
                "usuario_id = '$contacto_usuario_id' 
                 AND compania_id = '$compania' 
                 AND usuario_activo = 1 
                 AND usuario_eliminado = 0
                 AND usuario_id != '$usuario_id'"
            );
            
            if (!$contactos || count($contactos) == 0) {
                echo json_encode(array(
                    'code' => 404,
                    'message' => 'Contacto no encontrado'
                ));
                exit();
            }

            // Insertar o actualizar contacto
            $db->doQuery("INSERT INTO contactos_transferencias 
                (usuario_id, contacto_usuario_id, alias_personalizado, fecha_agregado, compania_id)
                VALUES ('$usuario_id', '$contacto_usuario_id', '$alias_personalizado', '$fecha_actual', '$compania')
                ON DUPLICATE KEY UPDATE
                    alias_personalizado = '$alias_personalizado',
                    activo = 1"
            );

            $message = 'Contacto agregado exitosamente';
            break;

        case 'actualizar_transferencia':
            // Esta función se llamará automáticamente después de una transferencia exitosa
            $db->doQuery("INSERT INTO contactos_transferencias 
                (usuario_id, contacto_usuario_id, fecha_agregado, ultima_transferencia, total_transferencias, compania_id)
                VALUES ('$usuario_id', '$contacto_usuario_id', '$fecha_actual', '$fecha_actual', 1, '$compania')
                ON DUPLICATE KEY UPDATE
                    ultima_transferencia = '$fecha_actual',
                    total_transferencias = total_transferencias + 1,
                    activo = 1"
            );

            $message = 'Contacto actualizado automáticamente';
            break;

        case 'eliminar':
            $db->doUpdate(
                "contactos_transferencias",
                "activo = 0",
                "usuario_id = '$usuario_id' 
                 AND contacto_usuario_id = '$contacto_usuario_id' 
                 AND compania_id = '$compania'"
            );

            $message = 'Contacto eliminado exitosamente';
            break;

        case 'marcar_favorito':
            $es_favorito = isset($obj['favorito']) ? intval($obj['favorito']) : 1;
            $db->doUpdate(
                "contactos_transferencias",
                "favorito = '$es_favorito'",
                "usuario_id = '$usuario_id' 
                 AND contacto_usuario_id = '$contacto_usuario_id' 
                 AND compania_id = '$compania'"
            );

            $message = $es_favorito ? 'Contacto marcado como favorito' : 'Contacto desmarcado como favorito';
            break;

        default:
            echo json_encode(array(
                'code' => 400,
                'message' => 'Acción no válida'
            ));
            exit();
    }

    echo json_encode(array(
        'code' => 0,
        'message' => $message,
        'data' => array(
            'usuario_id' => $usuario_id,
            'contacto_usuario_id' => $contacto_usuario_id,
            'accion' => $accion,
            'fecha' => $fecha_actual
        )
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'code' => 500,
        'message' => 'Error al procesar contacto: ' . $e->getMessage()
    ));
}
?>