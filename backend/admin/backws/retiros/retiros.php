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
        $where = " AND usuariobalanceretiro.cuenta_id = '$cuenta_id' ";
    } else if ($perfil_id == "3" || $perfil_id == "7") {
        // Administrador de Compañía o Empleados - Solo su cuenta y compañía
        $where = " AND usuariobalanceretiro.cuenta_id = '$cuenta_id' AND usuariobalanceretiro.compania_id = '$compania_id' ";
    } else {
        // Otros perfiles - Solo sus propios retiros
        $where = " AND usuariobalanceretiro.cuenta_id = '$cuenta_id' AND usuariobalanceretiro.compania_id = '$compania_id' AND usuariobalanceretiro.usuario_id = '$usuario_id' ";
    }

    // Filtro por ID específico
    if ($id != "") {
        $where .= " AND usuariobalanceretiro.usuariobalanceretiro_id = '$id' ";
    }

    // Filtro por estatus
    if ($estatus != "") {
        $where .= " AND usuariobalanceretiro.l_estatus_id = '$estatus' ";
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
        $where .= " AND (usuariobalanceretiro.usuariobalanceretiro_fechareg BETWEEN '$fechadesde 00:00:00' AND '$fechahasta 23:59:59') ";
    } else if ($fechadesde != "") {
        if (strpos($fechadesde, '/') !== false) {
            $fechadesde = ConvertirFechaNormalFechaBd($fechadesde);
        }
        $where .= " AND usuariobalanceretiro.usuariobalanceretiro_fechareg >= '$fechadesde 00:00:00' ";
    } else if ($fechahasta != "") {
        if (strpos($fechahasta, '/') !== false) {
            $fechahasta = ConvertirFechaNormalFechaBd($fechahasta);
        }
        $where .= " AND usuariobalanceretiro.usuariobalanceretiro_fechareg <= '$fechahasta 23:59:59' ";
    }

    // Filtro de búsqueda
    if ($search != "") {
        $search_decoded = utf8_decode($search);
        $where .= " AND (usuario.usuario_nombre LIKE '%$search_decoded%' OR usuario.usuario_apellido LIKE '%$search_decoded%' OR usuariobalanceretiro.usuariobalanceretiro_cod LIKE '%$search_decoded%' OR usuarioretiro.usuarioretiro_banco LIKE '%$search_decoded%' OR usuarioretiro.usuarioretiro_nrocuenta LIKE '%$search_decoded%' OR usuarioretiro.usuarioretiro_titular LIKE '%$search_decoded%') ";
    }

    // Calcular offset para paginación
    $offset = ($page - 1) * $limit;

    // Obtener listado de retiros
    $arrresultado = $conexion->doSelect(
        "usuariobalanceretiro.usuariobalanceretiro_id, usuariobalanceretiro.usuariobalanceretiro_monto,
        usuariobalanceretiro.usuariobalanceretiro_cod, usuariobalanceretiro.usuariobalanceretiro_observ,
        usuariobalanceretiro.usuariobalanceretiro_activo, usuariobalanceretiro.usuario_id,
        usuariobalanceretiro.cuenta_id, usuariobalanceretiro.compania_id, usuariobalanceretiro.usuarioretiro_id,
        usuariobalanceretiro.l_estatus_id, usuariobalanceretiro.l_moneda_id,
        usuariobalanceretiro.usuariobalanceretiro_procesado,

        DATE_FORMAT(usuariobalanceretiro.usuariobalanceretiro_fechareg,'%d/%m/%Y %H:%i:%s') as usuariobalanceretiro_fechareg,

        estatus.lista_nombre as estatus_nombre,
        estatus.lista_cod as estatus_cod,

        usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_alias, usuario.usuario_img,

        cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
        cuenta.usuario_apellido as cuenta_apellido,

        compania.compania_nombre, compania.compania_urlweb,

        moneda.lista_nombre as moneda_nombre, moneda.lista_nombredos as moneda_siglas,

        formapago.lista_nombre as formapago_nombre,

        usuarioretiro.usuarioretiro_banco, usuarioretiro.usuarioretiro_titular,
        usuarioretiro.usuarioretiro_tipocuenta, usuarioretiro.usuarioretiro_documento,
        usuarioretiro.usuarioretiro_nrocuenta, usuarioretiro.l_formapago_id as usuarioretiro_formapago_id",

        "usuariobalanceretiro
        LEFT JOIN lista moneda ON moneda.lista_id = usuariobalanceretiro.l_moneda_id
        LEFT JOIN usuario ON usuario.usuario_id = usuariobalanceretiro.usuario_id
        LEFT JOIN usuario cuenta ON cuenta.usuario_id = usuariobalanceretiro.cuenta_id
        LEFT JOIN compania ON compania.compania_id = usuariobalanceretiro.compania_id
        LEFT JOIN lista estatus ON estatus.lista_id = usuariobalanceretiro.l_estatus_id
        LEFT JOIN usuarioretiro ON usuarioretiro.usuarioretiro_id = usuariobalanceretiro.usuarioretiro_id
        LEFT JOIN lista formapago ON formapago.lista_id = usuarioretiro.l_formapago_id",

        "usuariobalanceretiro.usuariobalanceretiro_eliminado = '0' $where",
        null,
        "usuariobalanceretiro.usuariobalanceretiro_id DESC LIMIT $limit OFFSET $offset"
    );

    // Si es consulta por ID específico, devolver un solo objeto
    if ($id != "") {
        if (count($arrresultado) > 0) {
            $valor = $arrresultado[0];

            $usuario_img = utf8_encode($valor["usuario_img"]);

            $data = array(
                "usuariobalanceretiro_id" => utf8_encode($valor["usuariobalanceretiro_id"]),
                "usuariobalanceretiro_monto" => utf8_encode($valor["usuariobalanceretiro_monto"]),
                "usuariobalanceretiro_fechareg" => utf8_encode($valor["usuariobalanceretiro_fechareg"]),
                "usuariobalanceretiro_cod" => utf8_encode($valor["usuariobalanceretiro_cod"]),
                "usuariobalanceretiro_observ" => utf8_encode($valor["usuariobalanceretiro_observ"]),
                "usuariobalanceretiro_activo" => utf8_encode($valor["usuariobalanceretiro_activo"]),
                "usuariobalanceretiro_procesado" => utf8_encode($valor["usuariobalanceretiro_procesado"]),
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
                "l_formapago_id" => utf8_encode($valor["usuarioretiro_formapago_id"]),
                "formapago_nombre" => utf8_encode($valor["formapago_nombre"]),
                "l_moneda_id" => utf8_encode($valor["l_moneda_id"]),
                "moneda_nombre" => utf8_encode($valor["moneda_nombre"]),
                "moneda_siglas" => utf8_encode($valor["moneda_siglas"]),
                "usuarioretiro_id" => utf8_encode($valor["usuarioretiro_id"]),
                "usuarioretiro_banco" => utf8_encode($valor["usuarioretiro_banco"]),
                "usuarioretiro_titular" => utf8_encode($valor["usuarioretiro_titular"]),
                "usuarioretiro_tipocuenta" => utf8_encode($valor["usuarioretiro_tipocuenta"]),
                "usuarioretiro_documento" => utf8_encode($valor["usuarioretiro_documento"]),
                "usuarioretiro_nrocuenta" => utf8_encode($valor["usuarioretiro_nrocuenta"])
            );

            $valores = array(
                "code" => 100,
                "message" => "Retiro obtenido correctamente",
                "data" => $data,
            );
        } else {
            $valores = array(
                "code" => 106,
                "message" => "Retiro no encontrado",
                "data" => [],
            );
        }
    } else {
        // Listado de retiros
        $dataArray = array();

        foreach($arrresultado as $i=>$valor) {
            $usuario_img = utf8_encode($valor["usuario_img"]);

            $data = array(
                "usuariobalanceretiro_id" => utf8_encode($valor["usuariobalanceretiro_id"]),
                "usuariobalanceretiro_monto" => utf8_encode($valor["usuariobalanceretiro_monto"]),
                "usuariobalanceretiro_fechareg" => utf8_encode($valor["usuariobalanceretiro_fechareg"]),
                "usuariobalanceretiro_cod" => utf8_encode($valor["usuariobalanceretiro_cod"]),
                "usuariobalanceretiro_observ" => utf8_encode($valor["usuariobalanceretiro_observ"]),
                "usuariobalanceretiro_activo" => utf8_encode($valor["usuariobalanceretiro_activo"]),
                "usuariobalanceretiro_procesado" => utf8_encode($valor["usuariobalanceretiro_procesado"]),
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
                "l_formapago_id" => utf8_encode($valor["usuarioretiro_formapago_id"]),
                "formapago_nombre" => utf8_encode($valor["formapago_nombre"]),
                "l_moneda_id" => utf8_encode($valor["l_moneda_id"]),
                "moneda_nombre" => utf8_encode($valor["moneda_nombre"]),
                "moneda_siglas" => utf8_encode($valor["moneda_siglas"]),
                "usuarioretiro_banco" => utf8_encode($valor["usuarioretiro_banco"]),
                "usuarioretiro_titular" => utf8_encode($valor["usuarioretiro_titular"]),
                "usuarioretiro_tipocuenta" => utf8_encode($valor["usuarioretiro_tipocuenta"]),
                "usuarioretiro_nrocuenta" => utf8_encode($valor["usuarioretiro_nrocuenta"])
            );

            array_push($dataArray, $data);
        }

        // Obtener total de registros para paginación
        $arrTotal = $conexion->doSelect(
            "COUNT(*) as total",
            "usuariobalanceretiro
            LEFT JOIN usuario ON usuario.usuario_id = usuariobalanceretiro.usuario_id
            LEFT JOIN usuarioretiro ON usuarioretiro.usuarioretiro_id = usuariobalanceretiro.usuarioretiro_id",
            "usuariobalanceretiro.usuariobalanceretiro_eliminado = '0' $where"
        );

        $total = 0;
        if (count($arrTotal) > 0) {
            $total = $arrTotal[0]["total"];
        }

        $valores = array(
            "code" => 100,
            "message" => "Retiros obtenidos correctamente",
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
