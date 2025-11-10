<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");

include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../models/lista.php';

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
    (isset($_GET['compania'])) ? $compania_id=$_GET['compania'] : $compania_id='';
    (isset($_GET['id'])) ? $id=$_GET['id'] : $id='';
    (isset($_GET['estatus'])) ? $estatus=$_GET['estatus'] : $estatus='';
    (isset($_GET['fechadesde'])) ? $fechadesde=$_GET['fechadesde'] : $fechadesde='';
    (isset($_GET['fechahasta'])) ? $fechahasta=$_GET['fechahasta'] : $fechahasta='';
    (isset($_GET['page'])) ? $page=$_GET['page'] : $page='1';
    (isset($_GET['limit'])) ? $limit=$_GET['limit'] : $limit='50';
    (isset($_GET['search'])) ? $search=$_GET['search'] : $search='';

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
        $resultado = json_encode($valores);
        echo $resultado;
        exit();
    }

    // Construir WHERE según perfil
    $where = "";

    if ($perfil_id == "1") {
        // Administrador del Sistema - Ve todo
        $where = "";
    } else if ($perfil_id == "2") {
        // Administrador de Cuenta - Solo su cuenta
        $where = " AND pago.cuenta_id = '$cuenta_id' ";
    } else if ($perfil_id == "3" || $perfil_id == "7") {
        // Administrador de Compañía o Empleados - Solo su cuenta y compañía
        $where = " AND pago.cuenta_id = '$cuenta_id' AND pago.compania_id = '$compania_id' ";
    } else {
        // Otros perfiles - Solo sus propios depósitos
        $where = " AND pago.cuenta_id = '$cuenta_id' AND pago.compania_id = '$compania_id' AND pago.usuario_id = '$usuario_id' ";
    }

    // Filtro por ID específico
    if ($id != "") {
        $where .= " AND pago.pago_id = '$id' ";
    }

    // Filtro por estatus
    if ($estatus != "") {
        $where .= " AND pago.l_estatus_id = '$estatus' ";
    }

    // Filtro por rango de fechas
    if ($fechadesde != "" && $fechahasta != "") {
        // Convertir formato dd/mm/yyyy a yyyy-mm-dd
        if (strpos($fechadesde, '/') !== false) {
            $fechadesde = ConvertirFechaNormalFechaBd($fechadesde);
        }
        if (strpos($fechahasta, '/') !== false) {
            $fechahasta = ConvertirFechaNormalFechaBd($fechahasta);
        }
        $where .= " AND (pago.pago_fechareg BETWEEN '$fechadesde 00:00:00' AND '$fechahasta 23:59:59') ";
    } else if ($fechadesde != "") {
        if (strpos($fechadesde, '/') !== false) {
            $fechadesde = ConvertirFechaNormalFechaBd($fechadesde);
        }
        $where .= " AND pago.pago_fechareg >= '$fechadesde 00:00:00' ";
    } else if ($fechahasta != "") {
        if (strpos($fechahasta, '/') !== false) {
            $fechahasta = ConvertirFechaNormalFechaBd($fechahasta);
        }
        $where .= " AND pago.pago_fechareg <= '$fechahasta 23:59:59' ";
    }

    // Filtro de búsqueda
    if ($search != "") {
        $search_decoded = utf8_decode($search);
        $where .= " AND (usuario.usuario_nombre LIKE '%$search_decoded%' OR usuario.usuario_apellido LIKE '%$search_decoded%' OR pago.pago_referencia LIKE '%$search_decoded%' OR pago.pago_banco LIKE '%$search_decoded%' OR pago.pago_codint LIKE '%$search_decoded%') ";
    }

    // Calcular offset para paginación
    $offset = ($page - 1) * $limit;

    // Obtener listado de depósitos
    $arrresultado = $conexion->doSelect(
        "pago.pago_id, pago.pago_monto, pago.pago_referencia, pago.pago_banco,
        pago.pago_comentario, pago.pago_img, pago.l_formapago_id, pago.usuario_id,
        pago.pago_activo, pago.cuenta_id, pago.compania_id, pago.l_tipoarchivo_id,
        pago.l_estatus_id, pago.l_moneda_id, pago.pago_procesado, pago.pago_archoriginal,
        pago.pago_codint, pago.pago_codexterno, pago.modulo_id, pago.elemento_id,

        DATE_FORMAT(pago.pago_fechareg,'%d/%m/%Y %H:%i:%s') as pago_fechareg,
        DATE_FORMAT(pago.pago_fecha,'%d/%m/%Y') as pago_fecha,

        estatus.lista_nombre as estatus_nombre,
        estatus.lista_cod as estatus_cod,

        usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_alias, usuario.usuario_img,

        cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
        cuenta.usuario_apellido as cuenta_apellido,

        compania.compania_nombre, compania.compania_urlweb,

        moneda.lista_nombre as moneda_nombre, moneda.lista_nombredos as moneda_siglas,

        formapago.lista_nombre as formapago_nombre,

        tipopago.lista_cod as tipopago_cod, tipopago.lista_nombre as tipopago_nombre,

        modulo.modulo_nombreunico",

        "pago
        LEFT JOIN lista moneda ON moneda.lista_id = pago.l_moneda_id
        LEFT JOIN usuario ON usuario.usuario_id = pago.usuario_id
        LEFT JOIN usuario cuenta ON cuenta.usuario_id = pago.cuenta_id
        LEFT JOIN compania ON compania.compania_id = pago.compania_id
        LEFT JOIN lista estatus ON estatus.lista_id = pago.l_estatus_id
        LEFT JOIN lista formapago ON formapago.lista_id = pago.l_formapago_id
        LEFT JOIN lista tipopago ON tipopago.lista_id = pago.l_tipopago_id
        LEFT JOIN modulo ON modulo.modulo_id = pago.modulo_id",

        "pago.pago_eliminado = '0' $where",
        null,
        "pago.pago_id DESC LIMIT $limit OFFSET $offset"
    );

    // Si es consulta por ID específico, devolver un solo objeto
    if ($id != "") {
        if (count($arrresultado) > 0) {
            $valor = $arrresultado[0];

            $pago_img = utf8_encode($valor["pago_img"]);
            $usuario_img = utf8_encode($valor["usuario_img"]);

            $data = array(
                "pago_id" => utf8_encode($valor["pago_id"]),
                "pago_codint" => utf8_encode($valor["pago_codint"]),
                "pago_codexterno" => utf8_encode($valor["pago_codexterno"]),
                "pago_monto" => utf8_encode($valor["pago_monto"]),
                "pago_fecha" => utf8_encode($valor["pago_fecha"]),
                "pago_fechareg" => utf8_encode($valor["pago_fechareg"]),
                "pago_referencia" => utf8_encode($valor["pago_referencia"]),
                "pago_banco" => utf8_encode($valor["pago_banco"]),
                "pago_comentario" => utf8_encode($valor["pago_comentario"]),
                "pago_img" => ($pago_img != "" && $pago_img != "0.jpg") ? ObtenerUrlArch($compania_id) . "/" . $pago_img : "",
                "pago_archoriginal" => utf8_encode($valor["pago_archoriginal"]),
                "pago_activo" => utf8_encode($valor["pago_activo"]),
                "pago_procesado" => utf8_encode($valor["pago_procesado"]),
                "usuario_id" => utf8_encode($valor["usuario_id"]),
                "usuario_nombre" => utf8_encode($valor["usuario_nombre"]),
                "usuario_apellido" => utf8_encode($valor["usuario_apellido"]),
                "usuario_alias" => utf8_encode($valor["usuario_alias"]),
                "usuario_img" => ($usuario_img != "" && $usuario_img != "1.png") ? ObtenerUrlArch($compania_id) . "/" . $usuario_img : "",
                "cuenta_id" => utf8_encode($valor["cuenta_id"]),
                "cuenta_codigo" => utf8_encode($valor["cuenta_codigo"]),
                "cuenta_nombre" => utf8_encode($valor["cuenta_nombre"]) . " " . utf8_encode($valor["cuenta_apellido"]),
                "compania_id" => utf8_encode($valor["compania_id"]),
                "compania_nombre" => utf8_encode($valor["compania_nombre"]),
                "compania_urlweb" => utf8_encode($valor["compania_urlweb"]),
                "l_estatus_id" => utf8_encode($valor["l_estatus_id"]),
                "estatus_nombre" => utf8_encode($valor["estatus_nombre"]),
                "estatus_cod" => utf8_encode($valor["estatus_cod"]),
                "l_formapago_id" => utf8_encode($valor["l_formapago_id"]),
                "formapago_nombre" => utf8_encode($valor["formapago_nombre"]),
                "l_moneda_id" => utf8_encode($valor["l_moneda_id"]),
                "moneda_nombre" => utf8_encode($valor["moneda_nombre"]),
                "moneda_siglas" => utf8_encode($valor["moneda_siglas"]),
                "l_tipoarchivo_id" => utf8_encode($valor["l_tipoarchivo_id"]),
                "tipopago_cod" => utf8_encode($valor["tipopago_cod"]),
                "tipopago_nombre" => utf8_encode($valor["tipopago_nombre"]),
                "modulo_id" => utf8_encode($valor["modulo_id"]),
                "modulo_nombreunico" => utf8_encode($valor["modulo_nombreunico"]),
                "elemento_id" => utf8_encode($valor["elemento_id"])
            );

            $valores = array(
                "code" => 100,
                "message" => "Depósito obtenido correctamente",
                "data" => $data,
            );
        } else {
            $valores = array(
                "code" => 106,
                "message" => "Depósito no encontrado",
                "data" => [],
            );
        }
    } else {
        // Listado de depósitos
        $dataArray = array();

        foreach($arrresultado as $i=>$valor) {
            $pago_img = utf8_encode($valor["pago_img"]);
            $usuario_img = utf8_encode($valor["usuario_img"]);

            $data = array(
                "pago_id" => utf8_encode($valor["pago_id"]),
                "pago_codint" => utf8_encode($valor["pago_codint"]),
                "pago_codexterno" => utf8_encode($valor["pago_codexterno"]),
                "pago_monto" => utf8_encode($valor["pago_monto"]),
                "pago_fecha" => utf8_encode($valor["pago_fecha"]),
                "pago_fechareg" => utf8_encode($valor["pago_fechareg"]),
                "pago_referencia" => utf8_encode($valor["pago_referencia"]),
                "pago_banco" => utf8_encode($valor["pago_banco"]),
                "pago_comentario" => utf8_encode($valor["pago_comentario"]),
                "pago_img" => ($pago_img != "" && $pago_img != "0.jpg") ? ObtenerUrlArch($compania_id) . "/" . $pago_img : "",
                "pago_archoriginal" => utf8_encode($valor["pago_archoriginal"]),
                "pago_activo" => utf8_encode($valor["pago_activo"]),
                "pago_procesado" => utf8_encode($valor["pago_procesado"]),
                "usuario_id" => utf8_encode($valor["usuario_id"]),
                "usuario_nombre" => utf8_encode($valor["usuario_nombre"]),
                "usuario_apellido" => utf8_encode($valor["usuario_apellido"]),
                "usuario_alias" => utf8_encode($valor["usuario_alias"]),
                "usuario_img" => ($usuario_img != "" && $usuario_img != "1.png") ? ObtenerUrlArch($compania_id) . "/" . $usuario_img : "",
                "cuenta_id" => utf8_encode($valor["cuenta_id"]),
                "cuenta_codigo" => utf8_encode($valor["cuenta_codigo"]),
                "cuenta_nombre" => utf8_encode($valor["cuenta_nombre"]) . " " . utf8_encode($valor["cuenta_apellido"]),
                "compania_id" => utf8_encode($valor["compania_id"]),
                "compania_nombre" => utf8_encode($valor["compania_nombre"]),
                "l_estatus_id" => utf8_encode($valor["l_estatus_id"]),
                "estatus_nombre" => utf8_encode($valor["estatus_nombre"]),
                "estatus_cod" => utf8_encode($valor["estatus_cod"]),
                "l_formapago_id" => utf8_encode($valor["l_formapago_id"]),
                "formapago_nombre" => utf8_encode($valor["formapago_nombre"]),
                "l_moneda_id" => utf8_encode($valor["l_moneda_id"]),
                "moneda_nombre" => utf8_encode($valor["moneda_nombre"]),
                "moneda_siglas" => utf8_encode($valor["moneda_siglas"]),
                "tipopago_cod" => utf8_encode($valor["tipopago_cod"]),
                "tipopago_nombre" => utf8_encode($valor["tipopago_nombre"]),
                "modulo_id" => utf8_encode($valor["modulo_id"]),
                "modulo_nombreunico" => utf8_encode($valor["modulo_nombreunico"]),
                "elemento_id" => utf8_encode($valor["elemento_id"])
            );

            array_push($dataArray, $data);
        }

        // Obtener total de registros para paginación
        $arrTotal = $conexion->doSelect(
            "COUNT(*) as total",
            "pago
            LEFT JOIN usuario ON usuario.usuario_id = pago.usuario_id",
            "pago.pago_eliminado = '0' $where"
        );

        $total = 0;
        if (count($arrTotal) > 0) {
            $total = $arrTotal[0]["total"];
        }

        $valores = array(
            "code" => 100,
            "message" => "Depósitos obtenidos correctamente",
            "data" => $dataArray,
            "total" => intval($total),
            "page" => intval($page),
            "limit" => intval($limit)
        );
    }
}

$resultado = json_encode($valores);
echo $resultado;
?>
