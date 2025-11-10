<?php
/**
 * Función para comprobar la validez de un token
 * 
 * @param string $token Token a verificar
 * @param string $compania ID de la compañía
 * @return array|false Datos del usuario si el token es válido, false si no lo es
 */
function comprobartoken($token, $compania) {
    if (empty($token)) {
        return false;
    }
    
    include_once dirname(__FILE__) . '/../lib/mysqlclass.php';
    $db = new ConexionBd();
    
    // Escapar valores para prevenir SQL injection
    $token = addslashes($token);
    $compania = addslashes($compania);
    
    try {
        // Buscar el usuario por token
        $usuarios = $db->doSelect(
            "usuario_id, usuario_nombre, usuario_email, usuario_token, usuario_tokenexp",
            "usuario",
            "usuario_token = '$token' 
             AND compania_id = '$compania' 
             AND usuario_activo = 1 
             AND usuario_eliminado = 0"
        );
        
        if (!$usuarios || count($usuarios) == 0) {
            return false;
        }
        
        $usuario = $usuarios[0];
        
        // Verificar si el token ha expirado
        if (!empty($usuario['usuario_tokenexp'])) {
            $fecha_expiracion = strtotime($usuario['usuario_tokenexp']);
            $fecha_actual = time();
            
            if ($fecha_actual > $fecha_expiracion) {
                // Token expirado
                return false;
            }
        }
        
        // Token válido, devolver datos del usuario
        return array(
            'usuario_id' => $usuario['usuario_id'],
            'usuario_nombre' => $usuario['usuario_nombre'],
            'usuario_email' => $usuario['usuario_email']
        );
        
    } catch (Exception $e) {
        // Error al verificar token
        return false;
    }
}
?>