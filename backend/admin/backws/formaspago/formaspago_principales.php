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

if ($metodo == "GET") {

    $conexion = new ConexionBd();

    // Obtener parámetros GET
    (isset($_GET['token'])) ? $token=$_GET['token'] : $token='';
    (isset($_GET['compania'])) ? $t_compania_id=$_GET['compania'] : $t_compania_id='';

    if ($t_compania_id == "") {
        $t_compania_id = 0;
    }

    // Validar token
    $arrresultado = $conexion->doSelect(
        "usuario_id, perfil_id, cuenta_id",
        "usuario",
        "usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$t_compania_id'"
    );

    $usuario_id = "";
    $perfil_id = "";
    $cuenta_id_token = "";

    foreach($arrresultado as $n=>$valor) {
        $usuario_id = $valor["usuario_id"];
        $perfil_id = $valor["perfil_id"];
        $cuenta_id_token = $valor["cuenta_id"];
    }

    if ($usuario_id == "") {
        $valores = array(
            "code" => 103,
            "message" => "Usuario / Token no activo",
            "data" => [],
        );
    } else {

        // Obtener listado de formas de pago principales (del sistema)
        // Estas son las formas de pago predeterminadas que se usan como "Plataforma de Pago"
        $arrresultado = $conexion->doSelect(
            "lista.lista_id, lista.lista_nombre, lista.lista_cod, lista.lista_orden, lista.lista_img",
            "lista",
            "lista.lista_activo = '1' and lista.tipolista_id = '21' and lista.cuenta_id = '2' and lista.compania_id = '1'",
            null,
            "lista.lista_nombre asc"
        );

        $data = array();
        foreach($arrresultado as $i=>$valor) {
            $data[] = array(
                "lista_id" => utf8_encode($valor["lista_id"]),
                "lista_nombre" => utf8_encode($valor["lista_nombre"]),
                "lista_cod" => utf8_encode($valor["lista_cod"]),
                "lista_orden" => utf8_encode($valor["lista_orden"]),
                "lista_img" => utf8_encode($valor["lista_img"])
            );
        }

        $valores = array(
            "code" => 100,
            "message" => "Formas de pago principales obtenidas correctamente",
            "data" => $data,
        );
    }
}

$resultado = json_encode($valores);
echo $resultado;
?>
