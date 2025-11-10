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

if ($metodo == "PUT" || $metodo == "POST") {

    $conexion = new ConexionBd();
    $valoresPost = json_decode(file_get_contents('php://input'), true);

    // Obtener parámetros (ajx_fnci.php línea 33255)
    (isset($valoresPost['token'])) ? $token=$valoresPost['token'] : $token='';
    (isset($valoresPost['compania'])) ? $t_compania_id=$valoresPost['compania'] : $t_compania_id='';
    (isset($valoresPost['cuenta'])) ? $cuenta=$valoresPost['cuenta'] : $cuenta='';
    (isset($valoresPost['lista_id'])) ? $lista_id=$valoresPost['lista_id'] : $lista_id='';
    (isset($valoresPost['activo'])) ? $estatus=$valoresPost['activo'] : $estatus='';

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

    // Si no viene cuenta en el POST, usar la del token
    if ($cuenta == "") {
        $cuenta = $cuenta_id_token;
    }

    if ($usuario_id == "") {
        $valores = array(
            "code" => 103,
            "message" => "Usuario / Token no activo",
            "data" => [],
        );
    } else if ($lista_id == "") {
        $valores = array(
            "code" => 102,
            "message" => "ID de forma de pago requerido",
            "data" => [],
        );
    } else if ($estatus === "") {
        $valores = array(
            "code" => 102,
            "message" => "Estado activo requerido (0 o 1)",
            "data" => [],
        );
    } else {

        // Validar predeterminada (ajx_fnci.php líneas 33259-33264)
        $listadefecto = VerificaListaDefecto($lista_id);
        if ($listadefecto == true) {
            $valores = array(
                "code" => 101,
                "message" => "Este registro no puede ser cambiado de estatus porque es predeterminado para el sistema",
                "data" => [],
            );
        } else {

            // Ajustar cuenta según perfil (ajx_fnci.php líneas 33273-33278)
            if ($perfil_id == "2") {
                $cuenta = $cuenta_id_token;
            } else if ($perfil_id == "3") {
                $cuenta = $cuenta_id_token;
                $t_compania_id = $t_compania_id;
            }

            // Obtener listacuenta_id (ajx_fnci.php líneas 33283-33289)
            $arrresultado2 = $conexion->doSelect(
                "listacuenta_id",
                "listacuenta",
                "lista_id = '$lista_id' and cuenta_id = '$cuenta' and compania_id = '$t_compania_id'"
            );

            $listacuenta_id = "";
            foreach($arrresultado2 as $i=>$valor) {
                $listacuenta_id = $valor["listacuenta_id"];
            }

            $fechaactual = formatoFechaHoraBd(null, null, null, null, $t_compania_id);

            // Si es del sistema (ajx_fnci.php líneas 33291-33298)
            if ($cuenta == "2" && $t_compania_id == "1") {

                $resultado = $conexion->doUpdate("lista", "
                    lista_activo = '$estatus'
                    ", "lista_id='$lista_id' and compania_id = '$t_compania_id' and cuenta_id = '$cuenta' ");

                $resultado = $conexion->doUpdate("listacuenta",
                    "listacuenta_activo = '$estatus'",
                    "listacuenta_id='$listacuenta_id'");

            } else { // Es personalizada (ajx_fnci.php líneas 33299-33346)

                if ($listacuenta_id == "") {

                    // Si no existe listacuenta, obtener compañía
                    if ($t_compania_id == "") {
                        $arrresultado2 = $conexion->doSelect(
                            "compania_id",
                            "compania",
                            "cuenta_id = '$cuenta' and compania_activo = '1'"
                        );
                        foreach($arrresultado2 as $i=>$valor) {
                            $t_compania_id = $valor["compania_id"];
                        }
                    }

                    // Obtener datos de la lista del sistema
                    $arrresultado2 = $conexion->doSelect(
                        "lista_nombre, lista_orden, lista_img, lista_descrip",
                        "lista",
                        "lista_id = '$lista_id' and cuenta_id = '2' and compania_id = '1'"
                    );

                    $lista_nombre = "";
                    $lista_orden = "";
                    $lista_descrip = "";
                    $lista_img = "";

                    foreach($arrresultado2 as $i=>$valor) {
                        $lista_nombre = $valor["lista_nombre"];
                        $lista_orden = $valor["lista_orden"];
                        $lista_descrip = $valor["lista_descrip"];
                        $lista_img = $valor["lista_img"];
                    }

                    // Crear listacuenta con el nuevo estado
                    $resultado = $conexion->doInsert("
                        listacuenta
                            (lista_id, cuenta_id, compania_id, listacuenta_nombre, listacuenta_nombredos, listacuenta_descrip, listacuenta_img,
                             listacuenta_fechareg, listacuenta_activo, listacuenta_eliminado, usuario_idreg, listacuenta_orden)
                        ",
                        "'$lista_id', '$cuenta', '$t_compania_id','$lista_nombre', '','', '$lista_img',
                        '$fechaactual', '$estatus','0','$usuario_id','$lista_orden'");

                } else {

                    // Si existe listacuenta, actualizarla
                    $resultado = $conexion->doUpdate("listacuenta",
                        "listacuenta_activo = '$estatus'",
                        "listacuenta_id='$listacuenta_id'");
                }

                // Actualizar también la lista
                $resultado = $conexion->doUpdate("lista", "
                    lista_activo = '$estatus'
                    ", "lista_id='$lista_id' and compania_id = '$t_compania_id' and cuenta_id = '$cuenta' ");
            }

            if ($resultado) {
                $valores = array(
                    "code" => 100,
                    "message" => "Cambiado el Estatus Correctamente",
                    "data" => array(
                        "lista_id" => $lista_id,
                        "lista_activo" => $estatus
                    ),
                );
            } else {
                $valores = array(
                    "code" => 105,
                    "message" => "Error cambiando el estatus",
                    "data" => [],
                );
            }
        }
    }
}

$resultado = json_encode($valores);
echo $resultado;
?>
