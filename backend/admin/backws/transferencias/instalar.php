<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include_once '../../lib/mysqlclass.php';

$db = new ConexionBd();

try {
    $mensajes = array();
    $errores = array();
    
    // 1. Verificar y agregar campo usuario_alias
    $campos = $db->doSelect("COLUMN_NAME", "INFORMATION_SCHEMA.COLUMNS", 
        "TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'usuario_alias'");
    
    if (!$campos || count($campos) == 0) {
        $db->doQuery("ALTER TABLE `usuario` ADD COLUMN `usuario_alias` VARCHAR(100) NULL DEFAULT NULL AFTER `usuario_email`");
        $mensajes[] = "Campo usuario_alias agregado";
    } else {
        $mensajes[] = "Campo usuario_alias ya existe";
    }
    
    // 2. Verificar y agregar campo usuario_cbu
    $campos = $db->doSelect("COLUMN_NAME", "INFORMATION_SCHEMA.COLUMNS", 
        "TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'usuario_cbu'");
    
    if (!$campos || count($campos) == 0) {
        $db->doQuery("ALTER TABLE `usuario` ADD COLUMN `usuario_cbu` VARCHAR(22) NULL DEFAULT NULL AFTER `usuario_alias`");
        $mensajes[] = "Campo usuario_cbu agregado";
    } else {
        $mensajes[] = "Campo usuario_cbu ya existe";
    }
    
    // 3. Verificar y agregar campo usuario_verificado
    $campos = $db->doSelect("COLUMN_NAME", "INFORMATION_SCHEMA.COLUMNS", 
        "TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'usuario_verificado'");
    
    if (!$campos || count($campos) == 0) {
        $db->doQuery("ALTER TABLE `usuario` ADD COLUMN `usuario_verificado` INT(1) NULL DEFAULT 0 AFTER `usuario_cbu`");
        $mensajes[] = "Campo usuario_verificado agregado";
    } else {
        $mensajes[] = "Campo usuario_verificado ya existe";
    }
    
    // 4. Agregar índices (ignorar si ya existen)
    try {
        $db->doQuery("ALTER TABLE `usuario` ADD INDEX `idx_usuario_alias` (`usuario_alias`)");
        $mensajes[] = "Índice idx_usuario_alias agregado";
    } catch (Exception $e) {
        $mensajes[] = "Índice idx_usuario_alias ya existe o error al crear";
    }
    
    try {
        $db->doQuery("ALTER TABLE `usuario` ADD INDEX `idx_usuario_cbu` (`usuario_cbu`)");
        $mensajes[] = "Índice idx_usuario_cbu agregado";
    } catch (Exception $e) {
        $mensajes[] = "Índice idx_usuario_cbu ya existe o error al crear";
    }
    
    try {
        $db->doQuery("ALTER TABLE `usuario` ADD INDEX `idx_usuario_email` (`usuario_email`)");
        $mensajes[] = "Índice idx_usuario_email agregado";
    } catch (Exception $e) {
        $mensajes[] = "Índice idx_usuario_email ya existe o error al crear";
    }
    
    // 5. Agregar índices a tabla movimiento
    try {
        $db->doQuery("ALTER TABLE `movimiento` ADD INDEX `idx_mov_usuario_origen` (`usuario_id`)");
        $mensajes[] = "Índice idx_mov_usuario_origen agregado";
    } catch (Exception $e) {
        $mensajes[] = "Índice idx_mov_usuario_origen ya existe o error al crear";
    }
    
    try {
        $db->doQuery("ALTER TABLE `movimiento` ADD INDEX `idx_mov_usuario_destino` (`usuario_iddestino`)");
        $mensajes[] = "Índice idx_mov_usuario_destino agregado";
    } catch (Exception $e) {
        $mensajes[] = "Índice idx_mov_usuario_destino ya existe o error al crear";
    }
    
    // 6. Crear tabla notificaciones si no existe
    $tablas = $db->doSelect("TABLE_NAME", "INFORMATION_SCHEMA.TABLES", 
        "TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacion'");
    
    if (!$tablas || count($tablas) == 0) {
        $sql = "CREATE TABLE `notificacion` (
            `notificacion_id` int(11) NOT NULL AUTO_INCREMENT,
            `notificacion_titulo` varchar(200) DEFAULT NULL,
            `notificacion_mensaje` text DEFAULT NULL,
            `notificacion_tipo` varchar(50) DEFAULT NULL,
            `usuario_id` int(11) DEFAULT NULL,
            `notificacion_fecha` datetime DEFAULT NULL,
            `notificacion_leida` int(1) DEFAULT 0,
            `notificacion_activo` int(1) DEFAULT 1,
            `notificacion_eliminado` int(1) DEFAULT 0,
            `compania_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`notificacion_id`),
            KEY `idx_notif_usuario` (`usuario_id`),
            KEY `idx_notif_fecha` (`notificacion_fecha`),
            KEY `idx_notif_leida` (`notificacion_leida`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci";
        
        $db->doQuery($sql);
        $mensajes[] = "Tabla notificacion creada";
    } else {
        $mensajes[] = "Tabla notificacion ya existe";
    }
    
    // 7. Verificar si existe tabla l_tipomov
    $tablas = $db->doSelect("TABLE_NAME", "INFORMATION_SCHEMA.TABLES", 
        "TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'l_tipomov'");
    
    if (!$tablas || count($tablas) == 0) {
        // Crear tabla l_tipomov si no existe
        $sql = "CREATE TABLE `l_tipomov` (
            `l_tipomov_id` int(11) NOT NULL AUTO_INCREMENT,
            `l_tipomov_nombre` varchar(100) DEFAULT NULL,
            `l_tipomov_tipo` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`l_tipomov_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci";
        
        $db->doQuery($sql);
        $mensajes[] = "Tabla l_tipomov creada";
    }
    
    // 8. Insertar tipos de movimiento
    $tipos = array(
        array(1, 'Transferencia recibida', 'entrada'),
        array(2, 'Transferencia enviada', 'salida'),
        array(3, 'Depósito', 'entrada'),
        array(4, 'Retiro', 'salida')
    );
    
    foreach ($tipos as $tipo) {
        $existe = $db->doSelect("l_tipomov_id", "l_tipomov", "l_tipomov_id = " . $tipo[0]);
        if (!$existe || count($existe) == 0) {
            $db->doInsert("l_tipomov", 
                "l_tipomov_id, l_tipomov_nombre, l_tipomov_tipo",
                $tipo[0] . ", '" . $tipo[1] . "', '" . $tipo[2] . "'");
            $mensajes[] = "Tipo de movimiento '" . $tipo[1] . "' insertado";
        } else {
            $mensajes[] = "Tipo de movimiento '" . $tipo[1] . "' ya existe";
        }
    }
    
    echo json_encode(array(
        'code' => 0,
        'message' => 'Instalación completada',
        'detalles' => $mensajes,
        'errores' => $errores
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'code' => 500,
        'message' => 'Error durante la instalación',
        'error' => $e->getMessage()
    ));
}
?>