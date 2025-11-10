<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");

include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include('../../vendor/autoload.php');

$valores = array(
    "code" => 101,
    "message" => "Sin permisos",
    "data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo == "PUT") {

    $conexion = new ConexionBd();
    $valoresPost = json_decode(file_get_contents('php://input'), true);

    // Obtener parámetros
    (isset($valoresPost['token'])) ? $token=$valoresPost['token'] : $token='';
    (isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] : $compania_id='';
    (isset($valoresPost['usuariobalanceretiro_id'])) ? $usuariobalanceretiro_id=$valoresPost['usuariobalanceretiro_id'] : $usuariobalanceretiro_id='';
    (isset($valoresPost['estatus'])) ? $estatus=$valoresPost['estatus'] : $estatus='';

    if ($compania_id == "") {
        $compania_id = 0;
    }

    // Validar token
    $arrresultado = $conexion->doSelect(
        "usuario_id, perfil_id, cuenta_id",
        "usuario",
        "usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$compania_id'"
    );

    $usuario_id = "";
    $perfil_id = "";
    $cuenta_id = "";

    foreach($arrresultado as $n=>$valor) {
        $usuario_id = $valor["usuario_id"];
        $perfil_id = $valor["perfil_id"];
        $cuenta_id = $valor["cuenta_id"];
    }

    if ($usuario_id == "") {
        $valores = array(
            "code" => 103,
            "message" => "Usuario / Token no activo",
            "data" => [],
        );
    } else if ($usuariobalanceretiro_id == "" || $estatus == "") {
        $valores = array(
            "code" => 102,
            "message" => "Faltan parámetros requeridos (usuariobalanceretiro_id, estatus)",
            "data" => [],
        );
    } else {

        // Obtener información del retiro
        $arrresultado = $conexion->doSelect(
            "usuariobalanceretiro.usuariobalanceretiro_id, usuariobalanceretiro.usuariobalanceretiro_monto,
            usuariobalanceretiro.usuario_id, usuariobalanceretiro.l_estatus_id,
            usuariobalanceretiro.usuariobalanceretiro_procesado, usuariobalanceretiro.l_moneda_id,
            usuariobalanceretiro.cuenta_id, usuariobalanceretiro.compania_id,
            usuario.usuario_notas",
            "usuariobalanceretiro
            LEFT JOIN usuario ON usuario.usuario_id = usuariobalanceretiro.usuario_id",
            "usuariobalanceretiro.usuariobalanceretiro_id = '$usuariobalanceretiro_id' AND usuariobalanceretiro.usuariobalanceretiro_eliminado = '0'"
        );

        if (count($arrresultado) == 0) {
            $valores = array(
                "code" => 106,
                "message" => "Retiro no encontrado",
                "data" => [],
            );
        } else {

            $retiro = $arrresultado[0];
            $retiro_monto = $retiro["usuariobalanceretiro_monto"];
            $retiro_usuario_id = $retiro["usuario_id"];
            $estatus_anterior = $retiro["l_estatus_id"];
            $retiro_procesado = $retiro["usuariobalanceretiro_procesado"];
            $usuario_notas = $retiro["usuario_notas"];

            // Obtener información del estatus nuevo
            $arrEstatus = $conexion->doSelect(
                "lista_cod, lista_nombre",
                "lista",
                "lista_id = '$estatus' AND tipolista_id = '64'"
            );

            if (count($arrEstatus) == 0) {
                $valores = array(
                    "code" => 107,
                    "message" => "Estatus inválido",
                    "data" => [],
                );
                $resultado = json_encode($valores);
                echo $resultado;
                exit();
            }

            $estatus_cod = $arrEstatus[0]["lista_cod"];
            $estatus_nombre = utf8_encode($arrEstatus[0]["lista_nombre"]);

            // Actualizar estatus del retiro
            $resultado = $conexion->doUpdate(
                "usuariobalanceretiro",
                "l_estatus_id = '$estatus'",
                "usuariobalanceretiro_id = '$usuariobalanceretiro_id'"
            );

            if (!$resultado) {
                $valores = array(
                    "code" => 105,
                    "message" => "Error al cambiar estatus",
                    "data" => [],
                );
                $resultado = json_encode($valores);
                echo $resultado;
                exit();
            }

            $mensaje = "Cambiado el Estatus Correctamente";

            // Procesar balance solo si no ha sido procesado antes
            if ($retiro_procesado == "0") {

                // Código 2 = Aprobado (debitar balance bloqueado)
                if ($estatus_cod == "2") {

                    // Obtener balance actual del usuario
                    $arrBalance = $conexion->doSelect(
                        "usuariobalance_bloqueado, usuariobalance_disponible, usuariobalance_total",
                        "usuariobalance",
                        "usuario_id = '$retiro_usuario_id' AND compania_id = '$compania_id'"
                    );

                    if (count($arrBalance) > 0) {
                        $usuariobalance_bloqueado = $arrBalance[0]["usuariobalance_bloqueado"];
                        $usuariobalance_disponible = $arrBalance[0]["usuariobalance_disponible"];
                        $usuariobalance_total = $arrBalance[0]["usuariobalance_total"];

                        // Restar del bloqueado y del total
                        $usuariobalance_bloqueado = $usuariobalance_bloqueado - $retiro_monto;
                        $usuariobalance_total = $usuariobalance_total - $retiro_monto;

                        // Actualizar balance
                        $conexion->doUpdate(
                            "usuariobalance",
                            "usuariobalance_bloqueado = '$usuariobalance_bloqueado',
                            usuariobalance_total = '$usuariobalance_total'",
                            "usuario_id = '$retiro_usuario_id' AND compania_id = '$compania_id'"
                        );

                        // Marcar como procesado
                        $conexion->doUpdate(
                            "usuariobalanceretiro",
                            "usuariobalanceretiro_procesado = '1'",
                            "usuariobalanceretiro_id = '$usuariobalanceretiro_id'"
                        );

                        // Enviar notificación push
                        $titulo = "Retiro Aprobado";
                        $cuerpo = "Tu retiro por $" . number_format($retiro_monto, 2, '.', ',') . " ha sido aprobado";
                        enviarNotificacionPushFunciones($retiro_usuario_id, $titulo, $cuerpo, $usuario_notas, $compania_id);

                        $mensaje = "Cambiado el Estatus Correctamente y se procesó el retiro";
                    }

                // Código 3 = Rechazado (devolver al disponible)
                } else if ($estatus_cod == "3") {

                    // Obtener balance actual del usuario
                    $arrBalance = $conexion->doSelect(
                        "usuariobalance_bloqueado, usuariobalance_disponible, usuariobalance_total",
                        "usuariobalance",
                        "usuario_id = '$retiro_usuario_id' AND compania_id = '$compania_id'"
                    );

                    if (count($arrBalance) > 0) {
                        $usuariobalance_bloqueado = $arrBalance[0]["usuariobalance_bloqueado"];
                        $usuariobalance_disponible = $arrBalance[0]["usuariobalance_disponible"];
                        $usuariobalance_total = $arrBalance[0]["usuariobalance_total"];

                        // Restar del bloqueado y sumar al disponible
                        $usuariobalance_bloqueado = $usuariobalance_bloqueado - $retiro_monto;
                        $usuariobalance_disponible = $usuariobalance_disponible + $retiro_monto;

                        // Actualizar balance
                        $conexion->doUpdate(
                            "usuariobalance",
                            "usuariobalance_bloqueado = '$usuariobalance_bloqueado',
                            usuariobalance_disponible = '$usuariobalance_disponible'",
                            "usuario_id = '$retiro_usuario_id' AND compania_id = '$compania_id'"
                        );

                        // Marcar como procesado
                        $conexion->doUpdate(
                            "usuariobalanceretiro",
                            "usuariobalanceretiro_procesado = '1'",
                            "usuariobalanceretiro_id = '$usuariobalanceretiro_id'"
                        );

                        // Enviar notificación push
                        $titulo = "Retiro Rechazado";
                        $cuerpo = "Tu retiro por $" . number_format($retiro_monto, 2, '.', ',') . " ha sido rechazado. El monto ha sido devuelto a tu balance disponible";
                        enviarNotificacionPushFunciones($retiro_usuario_id, $titulo, $cuerpo, $usuario_notas, $compania_id);

                        $mensaje = "Cambiado el Estatus Correctamente y se devolvió el monto al usuario";
                    }
                }
            }

            $valores = array(
                "code" => 100,
                "message" => $mensaje,
                "data" => array(
                    "usuariobalanceretiro_id" => $usuariobalanceretiro_id,
                    "l_estatus_id" => $estatus,
                    "estatus_nombre" => $estatus_nombre
                ),
            );
        }
    }
}

$resultado = json_encode($valores);
echo $resultado;
?>
