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
    (isset($_GET['compania'])) ? $compania_id=$_GET['compania'] : $compania_id='';
    (isset($_GET['id'])) ? $lista_id=$_GET['id'] : $lista_id='';
    (isset($_GET['lid'])) ? $listacuenta_id_param=$_GET['lid'] : $listacuenta_id_param='';
    (isset($_GET['page'])) ? $page=$_GET['page'] : $page=1;
    (isset($_GET['limit'])) ? $limit=$_GET['limit'] : $limit=50;
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
        echo json_encode($valores);
        exit();
    }

    // Filtros por perfil (formapago.php líneas 30-52)
    $where = "";
    $wherecuenta = "";
    $wherecompania = "";
    $wherelistacuenta = "";
    $wherelistaactivo = "";

    if ($perfil_id == "1") { // Administrador del Sistema
        $where = "";
        $wherelistacuenta = "";

    } else if ($perfil_id == "2") { // Administrador de Cuenta
        $where = " and listacuenta.cuenta_id = '$cuenta_id' ";
        $wherecuenta = " and listacuenta.cuenta_id = '$cuenta_id' ";
        $wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta_id' ";
        $wherelistaactivo = " and lista.lista_activo = '1' ";

    } else { // Administrador de Compañía
        $where = " and listacuenta.cuenta_id = '$cuenta_id' and listacuenta.compania_id = '$compania_id' ";
        $wherecuenta = " and listacuenta.cuenta_id = '$cuenta_id' ";
        $wherecompania = " and listacuenta.compania_id = '$compania_id' ";
        $wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta_id' and listacuenta.compania_id = '$compania_id' ";
        $wherelistaactivo = " and lista.lista_activo = '1' ";
    }

    // Filtro por ID de lista
    $where_lista_id = "";
    if ($lista_id != "") {
        $where_lista_id = " and lista.lista_id = '$lista_id' ";
    }

    // Si viene ID de listacuenta específico (modificarformapago.php líneas 88-90)
    $whereid = "";
    if ($listacuenta_id_param != "") {
        $whereid = " and listacuenta.listacuenta_id = '$listacuenta_id_param' ";
    }

    // Búsqueda
    $where_search = "";
    if ($search != "") {
        $where_search = " and (lista.lista_nombre LIKE '%$search%' OR lista.lista_cod LIKE '%$search%')";
    }

    // SELECT exacto del controller original (formapago.php líneas 66-109)
    $arrresultado = $conexion->doSelect("
        lista.lista_id, lista.lista_nombre, lista.lista_img, lista.lista_orden, lista.lista_activo, lista.lista_ppal,
        lista.lista_cod, lista.lista_idrel,
        lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema,

        cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
        cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre as compania_nombre,
        cuenta.usuario_codigo as cuentasistema_codigo, cuenta.usuario_nombre as cuentasistema_nombre,
        cuenta.usuario_apellido as cuentasistema_apellido, companiasistema.compania_nombre as companiasistema_nombre,

        listacuenta.cuenta_id, listacuenta.compania_id,
        listacuenta.listacuenta_id, listacuenta.listacuenta_activo, listacuenta.listacuenta_eliminado,
        listacuenta.listacuenta_img, listacuenta.listacuenta_orden,
        listacuenta.listacuenta_nombre,
        lista.tipolista_id,

        listaformapago.listaformapago_id, listaformapago.l_formapago_id, listaformapago.listaformapago_titular,
        listaformapago.listaformapago_documento, listaformapago.listaformapago_email,
        listaformapago.listaformapago_banco, listaformapago.listaformapago_tipocuenta,
        listaformapago.listaformapago_nrocuenta, listaformapago.listaformapago_otros,
        listaformapago.usuario_idreg,
        DATE_FORMAT(listaformapago_fechareg,'%d/%m/%Y %H:%i:%s') as listaformapago_fechareg,
        listaformapago_token, listaformapago_clavepublica
    ",
    "
        lista

            inner join usuario cuentasistema on cuentasistema.usuario_id = lista.cuenta_id
            inner join compania companiasistema on companiasistema.compania_id = lista.compania_id

            left join listacuenta on listacuenta.lista_id = lista.lista_id
            $wherelistacuenta

            left join listaformapago on listaformapago.l_formapago_id = lista.lista_id
                      and listaformapago.listacuenta_id = listacuenta.listacuenta_id

            left join usuario cuenta on cuenta.usuario_id = listacuenta.cuenta_id
            left join compania on compania.compania_id = listacuenta.compania_id

            $wherecuenta
            $wherecompania

    ",
    "lista.lista_eliminado = '0' and lista.tipolista_id = '21' $where $where_lista_id $whereid $where_search and ((lista.lista_ppal = '1' $wherelistaactivo) or (lista.lista_ppal = '0' ))",
    null,
    "lista.lista_orden asc");


    $dataArray = array();

    // Procesar resultados (formapago.php líneas 112-224)
    foreach($arrresultado as $i=>$valor) {

        $cuenta_idsistema = utf8_encode($valor["cuenta_idsistema"]);
        $compania_idsistema = utf8_encode($valor["compania_idsistema"]);

        $t_listacuenta_id = utf8_encode($valor["listacuenta_id"]);
        $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);
        $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);
        $listacuenta_eliminado = utf8_encode($valor["listacuenta_eliminado"]);
        $listacuenta_orden = utf8_encode($valor["listacuenta_orden"]);
        $listacuenta_img = utf8_encode($valor["listacuenta_img"]);

        // Si está eliminado lógicamente, saltar
        if ($listacuenta_eliminado == "1") {
            continue;
        }

        $t_cuenta_id = utf8_encode($valor["cuenta_id"]);
        $t_compania_id = utf8_encode($valor["compania_id"]);

        $t_lista_id = utf8_encode($valor["lista_id"]);
        $lista_nombre = utf8_encode($valor["lista_nombre"]);
        $lista_cod = utf8_encode($valor["lista_cod"]);
        $lista_img = utf8_encode($valor["lista_img"]);
        $lista_orden = utf8_encode($valor["lista_orden"]);
        $lista_activo = utf8_encode($valor["lista_activo"]);
        $lista_ppal = utf8_encode($valor["lista_ppal"]);
        $lista_idrel = utf8_encode($valor["lista_idrel"]);

        $cuenta_nombre = utf8_encode($valor["cuenta_nombre"]);
        $cuenta_apellido = utf8_encode($valor["cuenta_apellido"]);
        $cuenta_codigo = utf8_encode($valor["cuenta_codigo"]);
        $cuenta = $cuenta_nombre." ".$cuenta_apellido;
        $compania_nombre = utf8_encode($valor["compania_nombre"]);

        $cuentasistema_nombre = utf8_encode($valor["cuentasistema_nombre"]);
        $cuentasistema_apellido = utf8_encode($valor["cuentasistema_apellido"]);
        $cuentasistema_codigo = utf8_encode($valor["cuentasistema_codigo"]);
        $cuentasistema = $cuentasistema_nombre." ".$cuentasistema_apellido;
        $companiasistema_nombre = utf8_encode($valor["companiasistema_nombre"]);

        $listaformapago_id = utf8_encode($valor["listaformapago_id"]);
        $l_formapago_id = utf8_encode($valor["l_formapago_id"]);
        $listaformapago_titular = utf8_encode($valor["listaformapago_titular"]);
        $listaformapago_documento = utf8_encode($valor["listaformapago_documento"]);
        $listaformapago_email = utf8_encode($valor["listaformapago_email"]);
        $listaformapago_banco = utf8_encode($valor["listaformapago_banco"]);
        $listaformapago_tipocuenta = utf8_encode($valor["listaformapago_tipocuenta"]);
        $listaformapago_nrocuenta = utf8_encode($valor["listaformapago_nrocuenta"]);
        $listaformapago_otros = utf8_encode($valor["listaformapago_otros"]);
        $listaformapago_fechareg = utf8_encode($valor["listaformapago_fechareg"]);
        $listaformapago_token = utf8_encode($valor["listaformapago_token"]);
        $listaformapago_clavepublica = utf8_encode($valor["listaformapago_clavepublica"]);

        // Si tiene personalización, usar datos de listacuenta
        if ($t_listacuenta_id != "") {
            if ($listacuenta_nombre != "") {
                $lista_nombre = $listacuenta_nombre;
            }
            if ($listacuenta_orden != "") {
                $lista_orden = $listacuenta_orden;
            }
            if ($listacuenta_img != "") {
                $lista_img = $listacuenta_img;
            }
            $lista_activo = $listacuenta_activo;
        }

        // Si es del sistema y no está personalizado
        if ($lista_ppal == "1" && $t_cuenta_id == "") {
            $cuenta = $cuentasistema;
            $compania_nombre = $companiasistema_nombre;
        }

        // URL de imagen
        $lista_img_url = ($lista_img != "") ? ObtenerUrlArch($compania_id) . "/" . $lista_img : "";

        $data = array(
            "lista_id" => $t_lista_id,
            "lista_nombre" => $lista_nombre,
            "lista_cod" => $lista_cod,
            "lista_img" => $lista_img,
            "lista_img_url" => $lista_img_url,
            "lista_orden" => $lista_orden,
            "lista_activo" => $lista_activo,
            "lista_ppal" => $lista_ppal,
            "lista_idrel" => $lista_idrel,
            "listacuenta_id" => $t_listacuenta_id,
            "cuenta_id" => $t_cuenta_id,
            "cuenta_nombre" => $cuenta,
            "compania_id" => $t_compania_id,
            "compania_nombre" => $compania_nombre,
            "listaformapago_id" => $listaformapago_id,
            "listaformapago_titular" => $listaformapago_titular,
            "listaformapago_documento" => $listaformapago_documento,
            "listaformapago_email" => $listaformapago_email,
            "listaformapago_banco" => $listaformapago_banco,
            "listaformapago_tipocuenta" => $listaformapago_tipocuenta,
            "listaformapago_nrocuenta" => $listaformapago_nrocuenta,
            "listaformapago_otros" => $listaformapago_otros,
            "listaformapago_token" => $listaformapago_token,
            "listaformapago_clavepublica" => $listaformapago_clavepublica,
            "listaformapago_fechareg" => $listaformapago_fechareg
        );

        array_push($dataArray, $data);
    }

    $valores = array(
        "code" => 100,
        "message" => "Formas de pago obtenidas correctamente",
        "data" => $dataArray,
        "total" => count($dataArray)
    );

} else if ($metodo == "POST") {

    // CREAR nueva forma de pago

    $conexion = new ConexionBd();
    $valoresPost = json_decode(file_get_contents('php://input'), true);

    // Obtener parámetros
    (isset($valoresPost['token'])) ? $token=$valoresPost['token'] : $token='';
    (isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] : $compania_id='';
    (isset($valoresPost['cuenta'])) ? $cuenta=$valoresPost['cuenta'] : $cuenta='';

    (isset($valoresPost['lista_nombre'])) ? $lista_nombre=$valoresPost['lista_nombre'] : $lista_nombre='';
    (isset($valoresPost['lista_cod'])) ? $lista_cod=$valoresPost['lista_cod'] : $lista_cod='';
    (isset($valoresPost['lista_orden'])) ? $lista_orden=$valoresPost['lista_orden'] : $lista_orden='';
    (isset($valoresPost['lista_idrel'])) ? $lista_idrel=$valoresPost['lista_idrel'] : $lista_idrel='';

    (isset($valoresPost['listaformapago_titular'])) ? $listaformapago_titular=$valoresPost['listaformapago_titular'] : $listaformapago_titular='';
    (isset($valoresPost['listaformapago_documento'])) ? $listaformapago_documento=$valoresPost['listaformapago_documento'] : $listaformapago_documento='';
    (isset($valoresPost['listaformapago_email'])) ? $listaformapago_email=$valoresPost['listaformapago_email'] : $listaformapago_email='';
    (isset($valoresPost['listaformapago_banco'])) ? $listaformapago_banco=$valoresPost['listaformapago_banco'] : $listaformapago_banco='';
    (isset($valoresPost['listaformapago_tipocuenta'])) ? $listaformapago_tipocuenta=$valoresPost['listaformapago_tipocuenta'] : $listaformapago_tipocuenta='';
    (isset($valoresPost['listaformapago_nrocuenta'])) ? $listaformapago_nrocuenta=$valoresPost['listaformapago_nrocuenta'] : $listaformapago_nrocuenta='';
    (isset($valoresPost['listaformapago_otros'])) ? $listaformapago_otros=$valoresPost['listaformapago_otros'] : $listaformapago_otros='';
    (isset($valoresPost['listaformapago_token'])) ? $listaformapago_token=$valoresPost['listaformapago_token'] : $listaformapago_token='';
    (isset($valoresPost['listaformapago_clavepublica'])) ? $listaformapago_clavepublica=$valoresPost['listaformapago_clavepublica'] : $listaformapago_clavepublica='';

    (isset($valoresPost['imagen_base64'])) ? $imagen_base64=$valoresPost['imagen_base64'] : $imagen_base64='';

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
    } else if ($lista_nombre == "") {
        $valores = array(
            "code" => 102,
            "message" => "Nombre de forma de pago requerido",
            "data" => [],
        );
    } else {

        // UTF-8 decode (uploadformapago.php líneas 65-76)
        $lista_nombre = utf8_decode($lista_nombre);
        $lista_cod = utf8_decode($lista_cod);
        $lista_orden = utf8_decode($lista_orden);
        $listaformapago_titular = utf8_decode($listaformapago_titular);
        $listaformapago_documento = utf8_decode($listaformapago_documento);
        $listaformapago_email = utf8_decode($listaformapago_email);
        $listaformapago_banco = utf8_decode($listaformapago_banco);
        $listaformapago_tipocuenta = utf8_decode($listaformapago_tipocuenta);
        $listaformapago_nrocuenta = utf8_decode($listaformapago_nrocuenta);
        $listaformapago_otros = utf8_decode($listaformapago_otros);
        $listaformapago_token = utf8_decode($listaformapago_token);

        if ($lista_orden == "") { $lista_orden = 0; }

        $fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

        $tipolista_id = "21"; // Formas de Pago
        $lista_img = "0.jpg"; // Imagen por defecto

        // Procesar imagen Base64 si viene
        if ($imagen_base64 != "") {
            if (preg_match('/^data:image\/(\w+);base64,/', $imagen_base64, $type)) {
                $extension = strtolower($type[1]);

                // Validar extensión
                $verificartipodeextension = verificarExtensionArchivoImagen($extension);
                if ($verificartipodeextension != "1") {
                    $valores = array(
                        "code" => 101,
                        "message" => "El tipo de archivo debe ser una imagen. Formatos permitidos: JPG, PNG",
                        "data" => [],
                    );
                    echo json_encode($valores);
                    exit();
                }

                // Remover prefijo Base64
                $imagen_base64 = substr($imagen_base64, strpos($imagen_base64, ',') + 1);

                // Decodificar Base64
                $imagen_data = base64_decode($imagen_base64);

                if ($imagen_data === false) {
                    $valores = array(
                        "code" => 105,
                        "message" => "Error al decodificar imagen Base64",
                        "data" => [],
                    );
                    echo json_encode($valores);
                    exit();
                }

                // Generar nombre único
                $lista_img = uniqid() . "." . $extension;

                // Guardar archivo físico en arch/
                $urlarchivo = "../../arch/" . $lista_img;

                $resultado_guardado = file_put_contents($urlarchivo, $imagen_data);

                if ($resultado_guardado === false) {
                    $valores = array(
                        "code" => 105,
                        "message" => "Error al guardar imagen en el servidor",
                        "data" => [],
                    );
                    echo json_encode($valores);
                    exit();
                }
            } else {
                $valores = array(
                    "code" => 101,
                    "message" => "Formato de imagen Base64 inválido. Debe incluir prefijo data:image/TYPE;base64,",
                    "data" => [],
                );
                echo json_encode($valores);
                exit();
            }
        }

        // Determinar si es del sistema o personalizada (uploadformapago.php líneas 193-197)
        if ($cuenta == "2" && $compania_id == "1") {
            $lista_ppal = 1;
        } else {
            $lista_ppal = 0;
        }

        // Guardar lista y listacuenta usando función del sistema (uploadformapago.php línea 213)
        $resultadoGuardarLista = GuardarProcesoLista(
            $lista_nombre,  // lista_nombre
            null,           // lista_nombredos
            null,           // lista_descrip
            $lista_img,     // lista_img
            $lista_ppal,    // lista_ppal
            $lista_orden,   // lista_orden
            $tipolista_id,  // tipolista_id
            $cuenta,        // cuenta_id
            $compania_id,   // compania_id
            null,           // lista_icono
            null,           // lista_color
            $lista_idrel,   // lista_idrel
            null,           // lista_url
            $fechaactual,   // fechaactual
            null,           // lista_mostrarppal
            null,           // lista_idreldos
            $lista_cod,     // lista_cod
            null,           // lista_resumen
            null,           // lista_img2
            $usuario_id     // usuario_id (nuevo parámetro)
        );

        $lista_id = $resultadoGuardarLista["lista_id"];
        $listacuenta_id = $resultadoGuardarLista["listacuenta_id"];

        // Guardar datos de forma de pago (uploadformapago.php línea 221)
        $resultadoGuardarListaFormaPago = GuardarListaFormaPago(
            "",                         // listaformapago_id (vacío = INSERT)
            $lista_id,                  // lista_id
            $listaformapago_titular,
            $listaformapago_documento,
            $listaformapago_email,
            $listaformapago_banco,
            $listaformapago_tipocuenta,
            $listaformapago_nrocuenta,
            $listaformapago_otros,
            $fechaactual,
            $cuenta,
            $compania_id,
            $listacuenta_id,
            $listaformapago_token,
            $listaformapago_clavepublica,
            $usuario_id
        );

        // Guardar relaciones Web y Sistema (uploadformapago.php líneas 224-240)
        $tipolista_idrel = 117; // Web
        $resultado = $conexion->doInsert("
            listacuentarel
                (tipolista_id, lista_id, cuenta_id, compania_id, listacuentarel_fechareg, listacuentarel_activo,
                listacuentarel_eliminado, usuario_idreg)
            ",
            "'$tipolista_idrel', '$lista_id', '$cuenta', '$compania_id', '$fechaactual', '1',
            '0', '$usuario_id'");

        $tipolista_idrel = 118; // Sistema
        $resultado = $conexion->doInsert("
            listacuentarel
                (tipolista_id, lista_id, cuenta_id, compania_id, listacuentarel_fechareg, listacuentarel_activo,
                listacuentarel_eliminado, usuario_idreg)
            ",
            "'$tipolista_idrel', '$lista_id', '$cuenta', '$compania_id', '$fechaactual', '1',
            '0', '$usuario_id'");

        $valores = array(
            "code" => 100,
            "message" => "Forma de Pago Guardado Correctamente",
            "data" => array(
                "lista_id" => $lista_id,
                "listacuenta_id" => $listacuenta_id
            ),
        );
    }

} else if ($metodo == "PUT") {

    // ACTUALIZAR forma de pago existente

    $conexion = new ConexionBd();
    $valoresPost = json_decode(file_get_contents('php://input'), true);

    // Obtener parámetros
    (isset($valoresPost['token'])) ? $token=$valoresPost['token'] : $token='';
    (isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] : $compania_id='';
    (isset($valoresPost['cuenta'])) ? $cuenta=$valoresPost['cuenta'] : $cuenta='';

    (isset($valoresPost['lista_id'])) ? $lista_id=$valoresPost['lista_id'] : $lista_id='';
    (isset($valoresPost['listacuenta_id'])) ? $listacuenta_id=$valoresPost['listacuenta_id'] : $listacuenta_id='';
    (isset($valoresPost['listaformapago_id'])) ? $listaformapago_id=$valoresPost['listaformapago_id'] : $listaformapago_id='';

    (isset($valoresPost['lista_nombre'])) ? $lista_nombre=$valoresPost['lista_nombre'] : $lista_nombre='';
    (isset($valoresPost['lista_cod'])) ? $lista_cod=$valoresPost['lista_cod'] : $lista_cod='';
    (isset($valoresPost['lista_orden'])) ? $lista_orden=$valoresPost['lista_orden'] : $lista_orden='';
    (isset($valoresPost['lista_idrel'])) ? $lista_idrel=$valoresPost['lista_idrel'] : $lista_idrel='';

    (isset($valoresPost['listaformapago_titular'])) ? $listaformapago_titular=$valoresPost['listaformapago_titular'] : $listaformapago_titular='';
    (isset($valoresPost['listaformapago_documento'])) ? $listaformapago_documento=$valoresPost['listaformapago_documento'] : $listaformapago_documento='';
    (isset($valoresPost['listaformapago_email'])) ? $listaformapago_email=$valoresPost['listaformapago_email'] : $listaformapago_email='';
    (isset($valoresPost['listaformapago_banco'])) ? $listaformapago_banco=$valoresPost['listaformapago_banco'] : $listaformapago_banco='';
    (isset($valoresPost['listaformapago_tipocuenta'])) ? $listaformapago_tipocuenta=$valoresPost['listaformapago_tipocuenta'] : $listaformapago_tipocuenta='';
    (isset($valoresPost['listaformapago_nrocuenta'])) ? $listaformapago_nrocuenta=$valoresPost['listaformapago_nrocuenta'] : $listaformapago_nrocuenta='';
    (isset($valoresPost['listaformapago_otros'])) ? $listaformapago_otros=$valoresPost['listaformapago_otros'] : $listaformapago_otros='';
    (isset($valoresPost['listaformapago_token'])) ? $listaformapago_token=$valoresPost['listaformapago_token'] : $listaformapago_token='';
    (isset($valoresPost['listaformapago_clavepublica'])) ? $listaformapago_clavepublica=$valoresPost['listaformapago_clavepublica'] : $listaformapago_clavepublica='';

    (isset($valoresPost['imagen_base64'])) ? $imagen_base64=$valoresPost['imagen_base64'] : $imagen_base64='';

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
    } else {

        // Validar que no sea predeterminada (uploadformapago.php líneas 53-58)
        $listadefecto = VerificaListaDefecto($lista_id);
        if ($listadefecto == true) {
            $valores = array(
                "code" => 101,
                "message" => "Este registro no puede ser modificado porque es predeterminado para el sistema",
                "data" => [],
            );
        } else {

            // UTF-8 decode
            $lista_nombre = utf8_decode($lista_nombre);
            $lista_cod = utf8_decode($lista_cod);
            $lista_orden = utf8_decode($lista_orden);
            $listaformapago_titular = utf8_decode($listaformapago_titular);
            $listaformapago_documento = utf8_decode($listaformapago_documento);
            $listaformapago_email = utf8_decode($listaformapago_email);
            $listaformapago_banco = utf8_decode($listaformapago_banco);
            $listaformapago_tipocuenta = utf8_decode($listaformapago_tipocuenta);
            $listaformapago_nrocuenta = utf8_decode($listaformapago_nrocuenta);
            $listaformapago_otros = utf8_decode($listaformapago_otros);
            $listaformapago_token = utf8_decode($listaformapago_token);

            if ($lista_orden == "") { $lista_orden = 0; }

            $fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

            $tipolista_id = "21";
            $lista_ppal = 0; // Al modificar siempre es personalizada

            $nombrecolocar_img = ""; // Variable para la imagen procesada

            // Procesar imagen Base64 si viene
            if ($imagen_base64 != "") {
                if (preg_match('/^data:image\/(\w+);base64,/', $imagen_base64, $type)) {
                    $extension = strtolower($type[1]);

                    // Validar extensión
                    $verificartipodeextension = verificarExtensionArchivoImagen($extension);
                    if ($verificartipodeextension != "1") {
                        $valores = array(
                            "code" => 101,
                            "message" => "El tipo de archivo debe ser una imagen. Formatos permitidos: JPG, PNG",
                            "data" => [],
                        );
                        echo json_encode($valores);
                        exit();
                    }

                    // Remover prefijo Base64
                    $imagen_base64 = substr($imagen_base64, strpos($imagen_base64, ',') + 1);

                    // Decodificar Base64
                    $imagen_data = base64_decode($imagen_base64);

                    if ($imagen_data === false) {
                        $valores = array(
                            "code" => 105,
                            "message" => "Error al decodificar imagen Base64",
                            "data" => [],
                        );
                        echo json_encode($valores);
                        exit();
                    }

                    // Generar nombre único
                    $nombrecolocar_img = uniqid() . "." . $extension;

                    // Guardar archivo físico en arch/
                    $urlarchivo = "../../arch/" . $nombrecolocar_img;

                    $resultado_guardado = file_put_contents($urlarchivo, $imagen_data);

                    if ($resultado_guardado === false) {
                        $valores = array(
                            "code" => 105,
                            "message" => "Error al guardar imagen en el servidor",
                            "data" => [],
                        );
                        echo json_encode($valores);
                        exit();
                    }

                    // Obtener y eliminar imagen anterior si existe
                    if ($listacuenta_id != "") {
                        $arrimg = $conexion->doSelect(
                            "listacuenta_img",
                            "listacuenta",
                            "listacuenta_id = '$listacuenta_id'"
                        );
                        foreach($arrimg as $i=>$valor) {
                            $imagen_anterior = $valor["listacuenta_img"];
                            if ($imagen_anterior != "" && $imagen_anterior != "0.jpg" && $imagen_anterior != "1.png") {
                                $archivo_anterior = "../../arch/" . $imagen_anterior;
                                if (file_exists($archivo_anterior)) {
                                    unlink($archivo_anterior);
                                }
                            }
                        }
                    }

                } else {
                    $valores = array(
                        "code" => 101,
                        "message" => "Formato de imagen Base64 inválido. Debe incluir prefijo data:image/TYPE;base64,",
                        "data" => [],
                    );
                    echo json_encode($valores);
                    exit();
                }
            }

            // Actualizar usando función (uploadformapago.php línea 249)
            $resultadoGuardarLista = GuardarProcesoModificarLista(
                $lista_id,      // lista_id
                $lista_nombre,
                null,           // lista_nombredos
                null,           // lista_descrip
                $nombrecolocar_img,  // nombrecolocar (imagen procesada o vacío)
                $lista_ppal,
                $lista_orden,
                $tipolista_id,
                $cuenta,
                $compania_id,
                null,           // lista_icono
                null,           // lista_color
                $lista_idrel,
                null,           // lista_url
                $fechaactual,
                $listacuenta_id,
                null,
                null,
                $lista_cod,
                null,           // lista_resumen
                null,           // lista_img2
                $usuario_id,    // usuario_id (nuevo parámetro)
                $perfil_id      // perfil_id (nuevo parámetro)
            );

            $lista_id = $resultadoGuardarLista["lista_id"];
            $listacuenta_id = $resultadoGuardarLista["listacuenta_id"];

            // Actualizar datos de forma de pago (uploadformapago.php línea 259)
            $resultadoGuardarListaFormaPago = GuardarListaFormaPago(
                $listaformapago_id,         // listaformapago_id (con ID = UPDATE)
                $lista_id,
                $listaformapago_titular,
                $listaformapago_documento,
                $listaformapago_email,
                $listaformapago_banco,
                $listaformapago_tipocuenta,
                $listaformapago_nrocuenta,
                $listaformapago_otros,
                $fechaactual,
                $cuenta,
                $compania_id,
                $listacuenta_id,
                $listaformapago_token,
                $listaformapago_clavepublica,
                $usuario_id
            );

            // Eliminar lógicamente relaciones anteriores (uploadformapago.php líneas 262-267)
            $resultado = $conexion->doUpdate("listacuentarel", "
                listacuentarel_activo = '0',
                listacuentarel_eliminado = '1',
                listacuentarel_fechareg = '$fechaactual'
                ",
                "cuenta_id='$cuenta' and compania_id = '$compania_id' and tipolista_id in (117,118) and lista_id = '$lista_id' ");

            // Insertar nuevas relaciones Web y Sistema (uploadformapago.php líneas 269-285)
            $tipolista_idrel = 117; // Web
            $resultado = $conexion->doInsert("
                listacuentarel
                    (tipolista_id, lista_id, cuenta_id, compania_id, listacuentarel_fechareg, listacuentarel_activo,
                    listacuentarel_eliminado, usuario_idreg)
                ",
                "'$tipolista_idrel', '$lista_id', '$cuenta', '$compania_id', '$fechaactual', '1',
                '0', '$usuario_id'");

            $tipolista_idrel = 118; // Sistema
            $resultado = $conexion->doInsert("
                listacuentarel
                    (tipolista_id, lista_id, cuenta_id, compania_id, listacuentarel_fechareg, listacuentarel_activo,
                    listacuentarel_eliminado, usuario_idreg)
                ",
                "'$tipolista_idrel', '$lista_id', '$cuenta', '$compania_id', '$fechaactual', '1',
                '0', '$usuario_id'");

            $valores = array(
                "code" => 100,
                "message" => "Forma de Pago actualizada correctamente",
                "data" => array(
                    "lista_id" => $lista_id,
                    "listacuenta_id" => $listacuenta_id
                ),
            );
        }
    }

} else if ($metodo == "DELETE") {

    // ELIMINAR forma de pago (lógico)

    $conexion = new ConexionBd();
    $valoresPost = json_decode(file_get_contents('php://input'), true);

    // Obtener parámetros
    (isset($valoresPost['token'])) ? $token=$valoresPost['token'] : $token='';
    (isset($valoresPost['compania'])) ? $t_compania_id=$valoresPost['compania'] : $t_compania_id='';
    (isset($valoresPost['cuenta'])) ? $cuenta=$valoresPost['cuenta'] : $cuenta='';
    (isset($valoresPost['lista_id'])) ? $lista_id=$valoresPost['lista_id'] : $lista_id='';

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
    } else {

        // Validar predeterminada (ajx_fnci.php líneas 33120-33125)
        $listadefecto = VerificaListaDefecto($lista_id);
        if ($listadefecto == true) {
            $valores = array(
                "code" => 101,
                "message" => "Este registro no puede ser eliminado porque es predeterminado para el sistema",
                "data" => [],
            );
        } else {

            // Ajustar cuenta según perfil (ajx_fnci.php líneas 33135-33140)
            if ($perfil_id == "2") {
                $cuenta = $cuenta_id_token;
            } else if ($perfil_id == "3") {
                $cuenta = $cuenta_id_token;
                $t_compania_id = $t_compania_id;
            }

            // Obtener listacuenta_id (ajx_fnci.php líneas 33142-33149)
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

            // Si es del sistema (ajx_fnci.php líneas 33152-33164)
            if ($cuenta == "2" && $t_compania_id == "1") {

                $resultado = $conexion->doUpdate("lista", "
                    lista_activo = '0',
                    lista_eliminado = '1'
                    ", "lista_id='$lista_id' and compania_id = '$t_compania_id' and cuenta_id = '$cuenta' ");

                $resultado = $conexion->doUpdate("listacuenta",
                    "listacuenta_activo = '0',
                    listacuenta_eliminado = '1'
                    ",
                    "listacuenta_id='$listacuenta_id'");

            } else { // Es personalizada (ajx_fnci.php líneas 33165-33223)

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

                    // Crear listacuenta con estado eliminado
                    $resultado = $conexion->doInsert("
                        listacuenta
                            (lista_id, cuenta_id, compania_id, listacuenta_nombre, listacuenta_nombredos, listacuenta_descrip, listacuenta_img,
                             listacuenta_fechareg, listacuenta_activo, listacuenta_eliminado, usuario_idreg, listacuenta_orden)
                        ",
                        "'$lista_id', '$cuenta', '$t_compania_id','$lista_nombre', '','', '$lista_img',
                        '$fechaactual', '0','1','$usuario_id','$lista_orden'");

                    $resultado = $conexion->doUpdate("lista", "
                        lista_activo = '0',
                        lista_eliminado = '1'
                        ", "lista_id='$lista_id' and compania_id = '$t_compania_id' and cuenta_id = '$cuenta' ");

                } else {

                    // Si existe listacuenta, marcarla como eliminada
                    $resultado = $conexion->doUpdate("listacuenta",
                        "listacuenta_activo = '0',
                        listacuenta_eliminado = '1'
                        ",
                        "listacuenta_id='$listacuenta_id'");

                    $resultado = $conexion->doUpdate("lista", "
                        lista_activo = '0',
                        lista_eliminado = '1'
                        ", "lista_id='$lista_id' and compania_id = '$t_compania_id' and cuenta_id = '$cuenta' ");
                }
            }

            if ($resultado) {
                $valores = array(
                    "code" => 100,
                    "message" => "Eliminado Correctamente",
                    "data" => array(
                        "lista_id" => $lista_id
                    ),
                );
            } else {
                $valores = array(
                    "code" => 105,
                    "message" => "Error eliminando la forma de pago",
                    "data" => [],
                );
            }
        }
    }
}

$resultado = json_encode($valores);
echo $resultado;
?>
