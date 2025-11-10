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
$limite = isset($obj['limite']) ? intval($obj['limite']) : 20;

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

try {
    // Crear tabla si no existe
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

    // Primero intentar obtener contactos de la tabla especializada
    $contactos = $db->doSelect(
        "ct.contacto_usuario_id as id,
         u.usuario_nombre as nombre,
         u.usuario_email as email,
         u.usuario_alias as alias,
         u.usuario_imagen as imagen,
         ct.ultima_transferencia,
         ct.total_transferencias,
         ct.favorito,
         ct.alias_personalizado,
         CASE 
            WHEN ct.alias_personalizado IS NOT NULL AND ct.alias_personalizado != '' 
            THEN ct.alias_personalizado 
            ELSE u.usuario_nombre 
         END as nombre_mostrar",
        "contactos_transferencias ct
         INNER JOIN usuario u ON ct.contacto_usuario_id = u.usuario_id",
        "ct.usuario_id = '$usuario_id' 
         AND ct.compania_id = '$compania'
         AND ct.activo = 1
         AND u.usuario_eliminado = 0
         AND u.usuario_activo = 1",
        null,
        "ct.favorito DESC, ct.ultima_transferencia DESC LIMIT $limite"
    );

    // Si no hay contactos guardados, buscar en movimientos (fallback)
    if (!$contactos || count($contactos) == 0) {
        $contactos = $db->doSelect(
            "DISTINCT 
                CASE 
                    WHEN m.usuario_id = '$usuario_id' THEN m.usuario_iddestino
                    WHEN m.usuario_iddestino = '$usuario_id' THEN m.usuario_id
                END as id,
                u.usuario_nombre as nombre,
                u.usuario_email as email,
                u.usuario_alias as alias,
                u.usuario_imagen as imagen,
                MAX(m.mov_fecha) as ultima_transferencia,
                COUNT(m.mov_id) as total_transferencias,
                0 as favorito,
                NULL as alias_personalizado,
                u.usuario_nombre as nombre_mostrar",
            "movimiento m
            INNER JOIN usuario u ON (
                (m.usuario_id = '$usuario_id' AND u.usuario_id = m.usuario_iddestino) OR
                (m.usuario_iddestino = '$usuario_id' AND u.usuario_id = m.usuario_id)
            )",
            "m.compania_id = '$compania' 
            AND m.mov_eliminado = 0
            AND m.l_tipomov_id IN (1, 2, 3, 4)
            AND u.usuario_eliminado = 0
            AND u.usuario_activo = 1",
            "id, u.usuario_nombre, u.usuario_email, u.usuario_alias, u.usuario_imagen",
            "ultima_transferencia DESC LIMIT $limite"
        );
    }

    // Si no hay contactos, devolver array vacío
    if (!$contactos || count($contactos) == 0) {
        $contactos = array();
    }

    // Formatear los datos
    $contactosFormateados = array();
    foreach ($contactos as $contacto) {
        // Si no tiene imagen, generar una con las iniciales
        if (empty($contacto['imagen'])) {
            $nombre = isset($contacto['nombre_mostrar']) ? $contacto['nombre_mostrar'] : ($contacto['nombre'] ? $contacto['nombre'] : 'Usuario');
            $contacto['imagen'] = "https://ui-avatars.com/api/?name=" . urlencode($nombre) . "&background=random";
        }
        
        // Formatear fecha
        if ($contacto['ultima_transferencia']) {
            $contacto['ultimaTransferencia'] = date('Y-m-d', strtotime($contacto['ultima_transferencia']));
        }
        
        // Agregar campos adicionales para compatibilidad
        $contactoFormateado = array(
            'id' => intval($contacto['id']),
            'nombre' => isset($contacto['nombre_mostrar']) ? $contacto['nombre_mostrar'] : $contacto['nombre'],
            'email' => $contacto['email'],
            'alias' => $contacto['alias'],
            'imagen' => $contacto['imagen'],
            'ultimaTransferencia' => isset($contacto['ultimaTransferencia']) ? $contacto['ultimaTransferencia'] : null,
            'ultima_transferencia' => $contacto['ultima_transferencia'],
            'total_transferencias' => isset($contacto['total_transferencias']) ? intval($contacto['total_transferencias']) : 0,
            'favorito' => isset($contacto['favorito']) ? ($contacto['favorito'] == 1) : false,
            'alias_personalizado' => isset($contacto['alias_personalizado']) ? $contacto['alias_personalizado'] : null
        );
        
        $contactosFormateados[] = $contactoFormateado;
    }

    echo json_encode(array(
        'code' => 0,
        'message' => 'Contactos obtenidos correctamente',
        'data' => $contactosFormateados
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'code' => 500,
        'message' => 'Error al obtener contactos: ' . $e->getMessage()
    ));
}
?>