<?php
/**
 * Endpoint para restablecer contraseña con código de verificación
 * La Kress - Company ID: 473
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Manejo de preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir archivos necesarios
require_once 'conexion/conexion.php';
require_once 'funciones/funciones.php';

// Obtener datos POST
$input = file_get_contents('php://input');
$datos = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $datos = $_POST;
}

// Función para responder JSON
function responderJSON($success, $data = null, $error = null) {
    $response = ['success' => $success];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($error !== null) {
        $response['error'] = $error;
        $response['code'] = 101;
        $response['message'] = $error;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// Validar datos requeridos
$email = $datos['email'] ?? $datos['usuario_email'] ?? '';
$codigo = $datos['codigo'] ?? $datos['codigo_recuperacion'] ?? '';
$password_nueva = $datos['password_nueva'] ?? $datos['nueva_clave'] ?? $datos['clave_nueva'] ?? '';
$compania_id = $datos['compania'] ?? $datos['compania_id'] ?? 0;

if (empty($email)) {
    responderJSON(false, null, 'Email es requerido');
}

if (empty($codigo)) {
    responderJSON(false, null, 'Código de recuperación es requerido');
}

if (empty($password_nueva)) {
    responderJSON(false, null, 'Nueva contraseña es requerida');
}

// Validar longitud mínima de contraseña
if (strlen($password_nueva) < 6) {
    responderJSON(false, null, 'La contraseña debe tener al menos 6 caracteres');
}

if ($compania_id != 473) {
    responderJSON(false, null, 'Compañía no válida');
}

try {
    // Verificar si el usuario existe
    $sql = "SELECT usuario_id, usuario_nombre, usuario_apellido, usuario_email,
                   usuario_codverif, perfil_idorig
            FROM usuario
            WHERE usuario_email = '" . $conexion->real_escape_string($email) . "'
            AND compania_id = " . intval($compania_id) . "
            LIMIT 1";

    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Verificar el código de recuperación
        $codigo_valido = false;

        // Opción 1: Verificar contra usuario_codverif
        if ($usuario['usuario_codverif'] == $codigo) {
            $codigo_valido = true;
        }

        // Opción 2: Verificar en tabla de códigos temporales
        if (!$codigo_valido) {
            $sql_codigo = "SELECT codigo_id, codigo_valor
                          FROM codigos_verificacion
                          WHERE usuario_id = " . intval($usuario['usuario_id']) . "
                          AND codigo_valor = '" . $conexion->real_escape_string($codigo) . "'
                          AND codigo_tipo = 'recuperacion'
                          AND codigo_usado = '0'
                          AND codigo_expiracion > NOW()
                          LIMIT 1";

            $result_codigo = $conexion->query($sql_codigo);
            if ($result_codigo && $result_codigo->num_rows > 0) {
                $codigo_valido = true;
                $codigo_data = $result_codigo->fetch_assoc();

                // Marcar código como usado
                $sql_update = "UPDATE codigos_verificacion
                              SET codigo_usado = '1', codigo_fechauso = NOW()
                              WHERE codigo_id = " . intval($codigo_data['codigo_id']);
                $conexion->query($sql_update);
            }
        }

        // Opción 3: Verificar en tabla de recuperación de GestionGo
        if (!$codigo_valido) {
            $sql_recuperacion = "SELECT recuperacion_id
                                FROM usuario_recuperacion
                                WHERE usuario_id = " . intval($usuario['usuario_id']) . "
                                AND recuperacion_codigo = '" . $conexion->real_escape_string($codigo) . "'
                                AND recuperacion_usado = '0'
                                AND recuperacion_expiracion > NOW()
                                LIMIT 1";

            $result_rec = $conexion->query($sql_recuperacion);
            if ($result_rec && $result_rec->num_rows > 0) {
                $codigo_valido = true;
                $rec_data = $result_rec->fetch_assoc();

                // Marcar como usado
                $sql_update = "UPDATE usuario_recuperacion
                              SET recuperacion_usado = '1', recuperacion_fechauso = NOW()
                              WHERE recuperacion_id = " . intval($rec_data['recuperacion_id']);
                $conexion->query($sql_update);
            }
        }

        if ($codigo_valido) {
            // Encriptar nueva contraseña
            $password_encriptada = md5($password_nueva); // GestionGo usa MD5

            // Actualizar contraseña
            $sql_update_password = "UPDATE usuario
                                   SET usuario_clave = '" . $conexion->real_escape_string($password_encriptada) . "',
                                       usuario_fechamodif = NOW(),
                                       usuario_activo = '1'
                                   WHERE usuario_id = " . intval($usuario['usuario_id']);

            if ($conexion->query($sql_update_password)) {
                // Generar nuevo token de sesión
                $nuevo_token = generarToken();
                $sql_token = "UPDATE usuario
                             SET usuario_codverif = '" . $conexion->real_escape_string($nuevo_token) . "'
                             WHERE usuario_id = " . intval($usuario['usuario_id']);
                $conexion->query($sql_token);

                // Limpiar intentos de recuperación antiguos
                $sql_limpiar = "DELETE FROM codigos_verificacion
                               WHERE usuario_id = " . intval($usuario['usuario_id']) . "
                               AND codigo_tipo = 'recuperacion'";
                $conexion->query($sql_limpiar);

                // También limpiar tabla de GestionGo si existe
                $sql_limpiar2 = "DELETE FROM usuario_recuperacion
                                WHERE usuario_id = " . intval($usuario['usuario_id']);
                $conexion->query($sql_limpiar2);

                // Responder con éxito
                responderJSON(true, [
                    'mensaje' => 'Contraseña actualizada exitosamente',
                    'usuario_id' => intval($usuario['usuario_id']),
                    'usuario_nombre' => $usuario['usuario_nombre'],
                    'usuario_apellido' => $usuario['usuario_apellido'],
                    'usuario_email' => $usuario['usuario_email'],
                    'token' => $nuevo_token,
                    'perfil_tipo' => $usuario['perfil_idorig'] == 21 ? 'creador' : 'cliente'
                ]);

            } else {
                responderJSON(false, null, 'Error al actualizar la contraseña');
            }

        } else {
            responderJSON(false, null, 'Código de recuperación inválido o expirado');
        }

    } else {
        responderJSON(false, null, 'Usuario no encontrado');
    }

} catch (Exception $e) {
    error_log("Error en restablecer_password.php: " . $e->getMessage());
    responderJSON(false, null, 'Error al restablecer contraseña');
}

/**
 * Generar token único
 */
function generarToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Cerrar conexión
$conexion->close();
?>