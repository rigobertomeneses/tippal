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
$usuarioRecibeId = isset($obj['usuarioRecibeId']) ? intval($obj['usuarioRecibeId']) : 0;
$monto = isset($obj['monto']) ? floatval($obj['monto']) : 0;
$concepto = isset($obj['concepto']) ? addslashes($obj['concepto']) : '';
$formapago_cod = isset($obj['formapago_cod']) ? $obj['formapago_cod'] : '999'; // 999 = billetera
$moneda = isset($obj['moneda']) ? intval($obj['moneda']) : 1;

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

// Validaciones básicas
if ($usuarioRecibeId <= 0) {
    echo json_encode(array(
        'code' => 400,
        'message' => 'Destinatario inválido'
    ));
    exit();
}

if ($monto <= 0) {
    echo json_encode(array(
        'code' => 400,
        'message' => 'El monto debe ser mayor a cero'
    ));
    exit();
}

if (empty($concepto)) {
    echo json_encode(array(
        'code' => 400,
        'message' => 'El concepto es requerido'
    ));
    exit();
}

// Verificar que no sea una transferencia a sí mismo
if ($usuario_id == $usuarioRecibeId) {
    echo json_encode(array(
        'code' => 400,
        'message' => 'No puedes transferir a tu propia cuenta'
    ));
    exit();
}

try {
    // Iniciar transacción
    $db->doQuery("START TRANSACTION");
    
    // 1. Verificar que el destinatario existe y está activo
    $destinatarios = $db->doSelect(
        "usuario_id, usuario_nombre, usuario_email",
        "usuario",
        "usuario_id = '$usuarioRecibeId' 
         AND compania_id = '$compania' 
         AND usuario_activo = 1 
         AND usuario_eliminado = 0"
    );
    
    if (!$destinatarios || count($destinatarios) == 0) {
        $db->doQuery("ROLLBACK");
        echo json_encode(array(
            'code' => 404,
            'message' => 'Destinatario no encontrado o inactivo'
        ));
        exit();
    }
    
    $destinatario = $destinatarios[0];
    
    // 2. Verificar saldo disponible del usuario origen
    $saldos = $db->doSelect(
        "COALESCE(SUM(CASE 
            WHEN l_tipomov_id IN (1, 3) THEN mov_monto 
            WHEN l_tipomov_id IN (2, 4) THEN -mov_monto 
            ELSE 0 
        END), 0) as saldo_disponible",
        "movimiento",
        "(usuario_id = '$usuario_id' OR usuario_iddestino = '$usuario_id')
         AND compania_id = '$compania' 
         AND mov_activo = 1 
         AND mov_eliminado = 0"
    );
    
    $saldo_disponible = $saldos[0]['saldo_disponible'];
    
    if ($saldo_disponible < $monto) {
        $db->doQuery("ROLLBACK");
        echo json_encode(array(
            'code' => 400,
            'message' => 'Saldo insuficiente. Disponible: $' . number_format($saldo_disponible, 2)
        ));
        exit();
    }
    
    // 3. Registrar movimiento de débito (salida) para el usuario origen
    $fecha_actual = date('Y-m-d H:i:s');
    
    $db->doInsert(
        "movimiento",
        "mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, 
         l_formapago_id, l_moneda_id, mov_fechareg, mov_activo, mov_eliminado, 
         cuenta_id, compania_id, l_estatus_id",
        "'Transferencia enviada: $concepto', 2, '$usuario_id', '$usuarioRecibeId', '$monto', '$fecha_actual',
         999, '$moneda', '$fecha_actual', 1, 0,
         1, '$compania', 1"
    );
    
    $movimiento_debito_id = $db->getLastInsertedId();
    
    // 4. Registrar movimiento de crédito (entrada) para el usuario destino
    $db->doInsert(
        "movimiento",
        "mov_descrip, l_tipomov_id, usuario_id, usuario_iddestino, mov_monto, mov_fecha, 
         l_formapago_id, l_moneda_id, mov_fechareg, mov_activo, mov_eliminado, 
         cuenta_id, compania_id, l_estatus_id",
        "'Transferencia recibida: $concepto', 1, '$usuarioRecibeId', '$usuario_id', '$monto', '$fecha_actual',
         999, '$moneda', '$fecha_actual', 1, 0,
         1, '$compania', 1"
    );
    
    $movimiento_credito_id = $db->getLastInsertedId();
    
    // 5. Registrar notificación para el destinatario
    $mensaje_notificacion = "Has recibido una transferencia de $" . number_format($monto, 2) . " - " . $concepto;
    
    $db->doInsert(
        "notificacion",
        "notificacion_titulo, notificacion_mensaje, notificacion_tipo, 
         usuario_id, notificacion_fecha, notificacion_leida, 
         notificacion_activo, notificacion_eliminado, compania_id",
        "'Transferencia recibida', '$mensaje_notificacion', 'transferencia',
         '$usuarioRecibeId', '$fecha_actual', 0,
         1, 0, '$compania'"
    );
    
    // 6. Agregar/actualizar contacto automáticamente
    try {
        // Verificar que la tabla de contactos existe
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

        // Guardar contacto automáticamente
        $db->doQuery("INSERT INTO contactos_transferencias 
            (usuario_id, contacto_usuario_id, fecha_agregado, ultima_transferencia, total_transferencias, compania_id)
            VALUES ('$usuario_id', '$usuarioRecibeId', '$fecha_actual', '$fecha_actual', 1, '$compania')
            ON DUPLICATE KEY UPDATE
                ultima_transferencia = '$fecha_actual',
                total_transferencias = total_transferencias + 1,
                activo = 1"
        );
    } catch (Exception $e) {
        // Si falla el guardado de contacto, no afecta la transferencia
        error_log("Error guardando contacto: " . $e->getMessage());
    }
    
    // 7. Confirmar transacción
    $db->doQuery("COMMIT");
    
    // 8. Obtener el nuevo saldo del usuario
    $saldos_nuevo = $db->doSelect(
        "COALESCE(SUM(CASE 
            WHEN l_tipomov_id IN (1, 3) THEN mov_monto 
            WHEN l_tipomov_id IN (2, 4) THEN -mov_monto 
            ELSE 0 
        END), 0) as saldo_disponible",
        "movimiento",
        "(usuario_id = '$usuario_id' OR usuario_iddestino = '$usuario_id')
         AND compania_id = '$compania' 
         AND mov_activo = 1 
         AND mov_eliminado = 0"
    );
    
    $nuevo_saldo = $saldos_nuevo[0]['saldo_disponible'];
    
    // Respuesta exitosa
    echo json_encode(array(
        'code' => 0,
        'message' => 'Transferencia realizada exitosamente',
        'data' => array(
            'id' => $movimiento_debito_id,
            'monto' => $monto,
            'concepto' => $concepto,
            'destinatario' => array(
                'id' => $destinatario['usuario_id'],
                'nombre' => $destinatario['usuario_nombre'],
                'email' => $destinatario['usuario_email']
            ),
            'fecha' => $fecha_actual,
            'nuevo_saldo' => $nuevo_saldo,
            'estado' => 'completada'
        )
    ));
    
} catch (Exception $e) {
    $db->doQuery("ROLLBACK");
    echo json_encode(array(
        'code' => 500,
        'message' => 'Error al realizar la transferencia: ' . $e->getMessage()
    ));
}
?>