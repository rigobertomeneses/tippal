<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");

include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';

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
    (isset($valoresPost['activo'])) ? $activo=$valoresPost['activo'] : $activo='';

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
    } else if ($usuariobalanceretiro_id == "" || $activo === "") {
        $valores = array(
            "code" => 102,
            "message" => "Faltan parámetros requeridos (usuariobalanceretiro_id, activo)",
            "data" => [],
        );
    } else {

        // Verificar que el retiro existe
        $arrresultado = $conexion->doSelect(
            "usuariobalanceretiro_id",
            "usuariobalanceretiro",
            "usuariobalanceretiro_id = '$usuariobalanceretiro_id' AND usuariobalanceretiro_eliminado = '0'"
        );

        if (count($arrresultado) > 0) {

            // Cambiar estado activo
            $resultado = $conexion->doUpdate(
                "usuariobalanceretiro",
                "usuariobalanceretiro_activo = '$activo'",
                "usuariobalanceretiro_id = '$usuariobalanceretiro_id'"
            );

            if ($resultado) {
                $mensaje = ($activo == "1") ? "Retiro activado correctamente" : "Retiro desactivado correctamente";

                $valores = array(
                    "code" => 100,
                    "message" => $mensaje,
                    "data" => array(
                        "usuariobalanceretiro_id" => $usuariobalanceretiro_id,
                        "usuariobalanceretiro_activo" => $activo
                    ),
                );
            } else {
                $valores = array(
                    "code" => 105,
                    "message" => "Error al cambiar estado activo",
                    "data" => [],
                );
            }

        } else {
            $valores = array(
                "code" => 106,
                "message" => "Retiro no encontrado",
                "data" => [],
            );
        }
    }
}

$resultado = json_encode($valores);
echo $resultado;
?>
