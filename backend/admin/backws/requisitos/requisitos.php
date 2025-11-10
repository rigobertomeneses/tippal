<?php
/**
 * API REST - Configuración de Requisitos
 *
 * Endpoints para gestionar tipos de requisitos del sistema
 * Basado en controllers/requisitos.php y modificarrequisito.php
 *
 * @author API Migration
 * @date 2025-10-09
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, token, compania");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$conexion = new ConexionBd();

// ====================================================================
// GET - Listar requisitos o obtener uno específico
// ====================================================================
if ($metodo == "GET") {

    // Obtener parámetros
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    $compania_id = isset($_GET['compania']) ? $_GET['compania'] : '';
    $requisito_id = isset($_GET['id']) ? $_GET['id'] : '';
    $listacuenta_id = isset($_GET['lid']) ? $_GET['lid'] : '';

    // Validar token
    if (empty($token)) {
        echo json_encode(['code' => 102, 'message' => 'Token es requerido']);
        exit();
    }

    // Validar token y obtener datos de usuario
    $arrresultado = $conexion->doSelect(
        "usuario.usuario_id, perfil.perfil_idorig, usuario.usuario_activo, usuario.cuenta_id",
        "usuario
        inner join perfil on perfil.perfil_id = usuario.perfil_id",
        "usuario.usuario_codverif = '$token' and usuario.usuario_eliminado = '0'"
    );

    if (count($arrresultado) == 0) {
        echo json_encode(['code' => 104, 'message' => 'Token no encontrado']);
        exit();
    }

    $datosToken = $arrresultado[0];
    $perfil_id = $datosToken['perfil_idorig'];
    $usuario_activo = $datosToken['usuario_activo'];
    $cuenta_id = $datosToken['cuenta_id'];

    if ($usuario_activo != '1') {
        echo json_encode(['code' => 103, 'message' => 'Usuario no activo']);
        exit();
    }

    // Construir WHERE según perfil_id (multi-tenancy)
    $where = "lista.lista_eliminado = '0' and lista.tipolista_id = '49'";
    $wherelistacuenta = "";
    $wherecuenta = "";
    $wherecompania = "";

    if ($perfil_id == "1") {
        // Administrador del Sistema - Ve todo
        $where .= " and ((lista.lista_ppal = '1' and lista.lista_activo = '1') or (lista.lista_ppal = '0'))";
        $wherelistacuenta = "";
    } else if ($perfil_id == "2") {
        // Administrador de Cuenta - Solo ve requisitos de su cuenta
        $where .= " and ((lista.lista_ppal = '1' and lista.lista_activo = '1' and lista.cuenta_id = '$cuenta_id') or (lista.lista_ppal = '0' and lista.cuenta_id = '$cuenta_id'))";
        $wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta_id' ";
        $wherecuenta = " and listacuenta.cuenta_id = '$cuenta_id' ";
    } else {
        // Administrador de Compañía o menor - Solo ve requisitos de su compañía
        if (!empty($compania_id)) {
            $where .= " and ((lista.lista_ppal = '1' and lista.lista_activo = '1' and lista.compania_id = '$compania_id') or (lista.lista_ppal = '0' and lista.compania_id = '$compania_id'))";
            $wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta_id' and listacuenta.compania_id = '$compania_id' ";
            $wherecuenta = " and listacuenta.cuenta_id = '$cuenta_id' ";
            $wherecompania = " and listacuenta.compania_id = '$compania_id' ";
        } else {
            $where .= " and ((lista.lista_ppal = '1' and lista.lista_activo = '1') or (lista.lista_ppal = '0'))";
        }
    }

    // Si se solicita un requisito específico
    if (!empty($requisito_id)) {
        $where .= " and lista.lista_id = '$requisito_id' ";
        if (!empty($listacuenta_id)) {
            $where .= " and listacuenta.listacuenta_id = '$listacuenta_id' ";
        }
    }

    // Consultar requisitos
    $arrresultado = $conexion->doSelect(
        "lista.lista_id, lista.lista_cod, lista.lista_nombre, lista.lista_descrip, lista.lista_img,
        lista.lista_orden, lista.lista_activo, lista.lista_ppal,
        lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema,

        listacuenta.cuenta_id, listacuenta.compania_id,
        listacuenta.listacuenta_id, listacuenta.listacuenta_activo, listacuenta.listacuenta_eliminado,
        listacuenta.listacuenta_img, listacuenta.listacuenta_orden, listacuenta.listacuenta_nombre,
        listacuenta.listacuenta_descrip, lista.tipolista_id,

        cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
        cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre,

        cuentasistema.usuario_codigo as cuentasistema_codigo, cuentasistema.usuario_nombre as cuentasistema_nombre,
        cuentasistema.usuario_apellido as cuentasistema_apellido, companiasistema.compania_nombre as companiasistema_nombre,

        listarequisitoperfil.perfil_id, perfil.perfil_nombre, listarequisitoperfil.l_tipoarchivo_id,
        tipoarchivo.lista_nombre as tipoarchivo_nombre",

        "lista
        inner join usuario cuentasistema on cuentasistema.usuario_id = lista.cuenta_id
        inner join compania companiasistema on companiasistema.compania_id = lista.compania_id

        left join listacuenta on listacuenta.lista_id = lista.lista_id $wherelistacuenta
        left join usuario cuenta on cuenta.usuario_id = listacuenta.cuenta_id
        left join compania on compania.compania_id = listacuenta.compania_id
        $wherecuenta
        $wherecompania

        left join listarequisitoperfil on listarequisitoperfil.l_requisitolista_id = lista.lista_id and listarequisitoperfil.listarequisitoperfil_activo = '1'
        left join perfil on perfil.perfil_id = listarequisitoperfil.perfil_id
        left join lista tipoarchivo on tipoarchivo.lista_id = listarequisitoperfil.l_tipoarchivo_id and tipoarchivo.tipolista_id = '1'",

        $where,
        null,
        "lista.lista_orden asc"
    );

    $datos = [];
    $urlarch = ObtenerUrlArch($compania_id);

    foreach ($arrresultado as $valor) {
        $item = [];

        // Datos del sistema
        $item['lista_id'] = utf8_encode($valor['lista_id']);
        $item['lista_cod'] = utf8_encode($valor['lista_cod']);
        $item['lista_nombre'] = utf8_encode($valor['lista_nombre']);
        $item['lista_descrip'] = utf8_encode($valor['lista_descrip']);
        $item['lista_img'] = utf8_encode($valor['lista_img']);
        $item['lista_orden'] = utf8_encode($valor['lista_orden']);
        $item['lista_activo'] = utf8_encode($valor['lista_activo']);
        $item['lista_ppal'] = utf8_encode($valor['lista_ppal']);

        // Personalización de cuenta/compañía
        $item['listacuenta_id'] = utf8_encode($valor['listacuenta_id']);
        $item['listacuenta_nombre'] = utf8_encode($valor['listacuenta_nombre']);
        $item['listacuenta_descrip'] = utf8_encode($valor['listacuenta_descrip']);
        $item['listacuenta_img'] = utf8_encode($valor['listacuenta_img']);
        $item['listacuenta_orden'] = utf8_encode($valor['listacuenta_orden']);
        $item['listacuenta_activo'] = utf8_encode($valor['listacuenta_activo']);

        // Cuenta y compañía
        $item['cuenta_id'] = utf8_encode($valor['cuenta_id']);
        $item['compania_id'] = utf8_encode($valor['compania_id']);
        $item['cuenta_nombre'] = utf8_encode($valor['cuenta_nombre']);
        $item['cuenta_apellido'] = utf8_encode($valor['cuenta_apellido']);
        $item['compania_nombre'] = utf8_encode($valor['compania_nombre']);

        // Sistema original
        $item['cuenta_idsistema'] = utf8_encode($valor['cuenta_idsistema']);
        $item['compania_idsistema'] = utf8_encode($valor['compania_idsistema']);
        $item['cuentasistema_nombre'] = utf8_encode($valor['cuentasistema_nombre']);
        $item['cuentasistema_apellido'] = utf8_encode($valor['cuentasistema_apellido']);
        $item['companiasistema_nombre'] = utf8_encode($valor['companiasistema_nombre']);

        // Perfil asignado
        $item['perfil_id'] = utf8_encode($valor['perfil_id']);
        $item['perfil_nombre'] = utf8_encode($valor['perfil_nombre']);

        // Tipo de archivo esperado
        $item['l_tipoarchivo_id'] = utf8_encode($valor['l_tipoarchivo_id']);
        $item['tipoarchivo_nombre'] = utf8_encode($valor['tipoarchivo_nombre']);

        // Si tiene personalización, usar esos valores
        if (!empty($item['listacuenta_id'])) {
            if (!empty($item['listacuenta_nombre'])) $item['lista_nombre'] = $item['listacuenta_nombre'];
            if (!empty($item['listacuenta_descrip'])) $item['lista_descrip'] = $item['listacuenta_descrip'];
            if (!empty($item['listacuenta_img'])) $item['lista_img'] = $item['listacuenta_img'];
            if (!empty($item['listacuenta_orden'])) $item['lista_orden'] = $item['listacuenta_orden'];
            $item['lista_activo'] = $item['listacuenta_activo'];
        }

        // Si es principal del sistema y no está personalizado
        if ($item['lista_ppal'] == '1' && empty($item['cuenta_id'])) {
            $item['cuenta_nombre'] = $item['cuentasistema_nombre'];
            $item['cuenta_apellido'] = $item['cuentasistema_apellido'];
            $item['compania_nombre'] = $item['companiasistema_nombre'];
        }

        // URL completa de imagen
        if (!empty($item['lista_img']) && $item['lista_img'] != '0.jpg') {
            $item['lista_img_url'] = $urlarch . '/' . $item['lista_img'];
        } else {
            $item['lista_img_url'] = '';
        }

        $datos[] = $item;
    }

    echo json_encode([
        'code' => 100,
        'message' => 'Requisitos obtenidos exitosamente',
        'data' => $datos
    ]);
    exit();
}

// ====================================================================
// POST - Crear nuevo requisito
// ====================================================================
else if ($metodo == "POST") {

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Obtener parámetros
    $token = isset($data['token']) ? $data['token'] : '';
    $compania_id = isset($data['compania']) ? $data['compania'] : '';
    $lista_nombre = isset($data['lista_nombre']) ? utf8_decode($data['lista_nombre']) : '';
    $lista_descrip = isset($data['lista_descrip']) ? utf8_decode($data['lista_descrip']) : '';
    $lista_cod = isset($data['lista_cod']) ? utf8_decode($data['lista_cod']) : '';
    $lista_orden = isset($data['lista_orden']) ? $data['lista_orden'] : 0;
    $perfil_id_asignar = isset($data['perfil_id']) ? $data['perfil_id'] : '';
    $tipo_archivo = isset($data['tipo_archivo']) ? $data['tipo_archivo'] : '1'; // 1=Imagen, 2=Archivo/PDF, 3=Contrato
    $imagen = isset($data['imagen']) ? $data['imagen'] : ''; // Base64

    // Validar token
    if (empty($token)) {
        echo json_encode(['code' => 102, 'message' => 'Token es requerido']);
        exit();
    }

    // Validar datos requeridos
    if (empty($lista_nombre)) {
        echo json_encode(['code' => 102, 'message' => 'Nombre del requisito es requerido']);
        exit();
    }

    if (empty($perfil_id_asignar)) {
        echo json_encode(['code' => 102, 'message' => 'Perfil es requerido']);
        exit();
    }

    // Validar token y obtener datos de usuario
    // Si viene compania_id, filtrar por ella; si no, buscar sin filtro de compañía
    $where_token = "usuario.usuario_codverif = '$token' and usuario.usuario_eliminado = '0' and usuario.usuario_activo = '1'";
    if (!empty($compania_id) && $compania_id != "0") {
        $where_token .= " and usuario.compania_id = '$compania_id'";
    }

    $arrresultado = $conexion->doSelect(
        "usuario.usuario_id, perfil.perfil_idorig, usuario.usuario_activo, usuario.cuenta_id",
        "usuario
        inner join perfil on perfil.perfil_id = usuario.perfil_id",
        $where_token
    );

    if (count($arrresultado) == 0) {
        echo json_encode(['code' => 104, 'message' => 'Token no encontrado o usuario no activo']);
        exit();
    }

    $datosToken = $arrresultado[0];
    $usuario_id = $datosToken['usuario_id'];
    $perfil_id = $datosToken['perfil_idorig'];
    $usuario_activo = $datosToken['usuario_activo'];
    $cuenta_id_token = $datosToken['cuenta_id'];

    // Permisos: Solo Admin Sistema, Admin Cuenta y Admin Compañía pueden crear
    if (!in_array($perfil_id, ['1', '2', '3'])) {
        echo json_encode(['code' => 101, 'message' => 'Sin permisos para crear requisitos. Perfil requerido: 1, 2 o 3. Tu perfil: ' . $perfil_id]);
        exit();
    }

    // Procesar imagen si viene en base64
    $nombre_imagen = '0.jpg';
    if (!empty($imagen)) {
        // Validar que sea base64 válido
        if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $imagen, $matches)) {
            $extension = ($matches[1] == 'jpeg') ? 'jpg' : $matches[1];
            $imagen_base64 = preg_replace('/^data:image\/(jpeg|jpg|png|gif);base64,/', '', $imagen);
            $imagen_decoded = base64_decode($imagen_base64);

            if ($imagen_decoded !== false) {
                $nombre_imagen = uniqid() . '.' . $extension;
                $ruta_archivo = '../../arch/' . $nombre_imagen;
                file_put_contents($ruta_archivo, $imagen_decoded);
            }
        }
    }

    $fechaactual = formatoFechaHoraBd();
    $tipolista_id = 49; // Tipo de lista para requisitos

    // Obtener cuenta_id de la tabla compania
    $arrcompania = $conexion->doSelect(
        "cuenta_id",
        "compania",
        "compania_id = '$compania_id'"
    );

    $cuenta_guardar = (count($arrcompania) > 0) ? $arrcompania[0]['cuenta_id'] : $cuenta_id_token;
    $compania_guardar = !empty($compania_id) ? $compania_id : '';
    $lista_ppal = 0;

    // Usar función del sistema para guardar lista
    $resultadoGuardarLista = GuardarProcesoLista(
        $lista_nombre,      // lista_nombre
        '',                 // lista_nombredos
        $lista_descrip,     // lista_descrip
        $nombre_imagen,     // lista_img
        $lista_ppal,        // lista_ppal
        $lista_orden,       // lista_orden
        $tipolista_id,      // tipolista_id (49 = requisitos)
        $cuenta_guardar,    // cuenta_id
        $compania_guardar,  // compania_id
        '',                 // lista_icono
        '',                 // lista_color
        0,                  // lista_idrel
        '',                 // lista_url
        $fechaactual,       // fecha registro
        null,               // lista_nombredos2
        null,               // lista_nombredos3
        $lista_cod          // lista_cod
    );

    $lista_id = $resultadoGuardarLista["lista_id"];
    $listacuenta_id = $resultadoGuardarLista["listacuenta_id"];

    // Insertar relación con perfil (incluye tipo de archivo esperado)
    $resultado = $conexion->doInsert(
        "listarequisitoperfil
        (l_requisitolista_id, perfil_id, cuenta_id, compania_id, listarequisitoperfil_activo,
        listarequisitoperfil_eliminado, listarequisitoperfil_fechareg, usuario_idreg, l_tipoarchivo_id)",
        "'$lista_id', '$perfil_id_asignar', '$cuenta_guardar', '$compania_guardar',
        '1', '0', '$fechaactual', '$usuario_id', '$tipo_archivo'"
    );

    $urlarch = ObtenerUrlArch($compania_guardar);

    echo json_encode([
        'code' => 100,
        'message' => 'Requisito creado exitosamente',
        'data' => [
            'lista_id' => $lista_id,
            'listacuenta_id' => $listacuenta_id,
            'lista_nombre' => utf8_encode($lista_nombre),
            'lista_img' => $nombre_imagen,
            'lista_img_url' => $urlarch . '/' . $nombre_imagen
        ]
    ]);
    exit();
}

// ====================================================================
// PUT - Actualizar requisito existente
// ====================================================================
else if ($metodo == "PUT") {

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Obtener parámetros
    $token = isset($data['token']) ? $data['token'] : '';
    $compania_id = isset($data['compania']) ? $data['compania'] : '';
    $lista_id = isset($data['lista_id']) ? $data['lista_id'] : '';
    $listacuenta_id = isset($data['listacuenta_id']) ? $data['listacuenta_id'] : '';
    $lista_nombre = isset($data['lista_nombre']) ? utf8_decode($data['lista_nombre']) : '';
    $lista_descrip = isset($data['lista_descrip']) ? utf8_decode($data['lista_descrip']) : '';
    $lista_cod = isset($data['lista_cod']) ? utf8_decode($data['lista_cod']) : '';
    $lista_orden = isset($data['lista_orden']) ? $data['lista_orden'] : 0;
    $perfil_id_asignar = isset($data['perfil_id']) ? $data['perfil_id'] : '';
    $tipo_archivo = isset($data['tipo_archivo']) ? $data['tipo_archivo'] : '1'; // 1=Imagen, 2=Archivo/PDF, 3=Contrato
    $imagen = isset($data['imagen']) ? $data['imagen'] : ''; // Base64

    // Validar token
    if (empty($token)) {
        echo json_encode(['code' => 102, 'message' => 'Token es requerido']);
        exit();
    }

    // Validar ID
    if (empty($lista_id)) {
        echo json_encode(['code' => 102, 'message' => 'ID del requisito es requerido']);
        exit();
    }

    // Validar token y obtener datos de usuario
    $arrresultado = $conexion->doSelect(
        "usuario.usuario_id, perfil.perfil_idorig, usuario.usuario_activo, usuario.cuenta_id",
        "usuario
        inner join perfil on perfil.perfil_id = usuario.perfil_id",
        "usuario.usuario_codverif = '$token' and usuario.usuario_eliminado = '0'"
    );

    if (count($arrresultado) == 0) {
        echo json_encode(['code' => 104, 'message' => 'Token no encontrado']);
        exit();
    }

    $datosToken = $arrresultado[0];
    $usuario_id = $datosToken['usuario_id'];
    $perfil_id = $datosToken['perfil_idorig'];
    $usuario_activo = $datosToken['usuario_activo'];
    $cuenta_id_token = $datosToken['cuenta_id'];

    if ($usuario_activo != '1') {
        echo json_encode(['code' => 103, 'message' => 'Usuario no activo']);
        exit();
    }

    // Permisos: Solo Admin Sistema, Admin Cuenta y Admin Compañía pueden modificar
    if (!in_array($perfil_id, ['1', '2', '3'])) {
        echo json_encode(['code' => 101, 'message' => 'Sin permisos para modificar requisitos']);
        exit();
    }

    // Procesar imagen si viene en base64
    $nombre_imagen = '';
    if (!empty($imagen)) {
        // Validar que sea base64 válido
        if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $imagen, $matches)) {
            $extension = ($matches[1] == 'jpeg') ? 'jpg' : $matches[1];
            $imagen_base64 = preg_replace('/^data:image\/(jpeg|jpg|png|gif);base64,/', '', $imagen);
            $imagen_decoded = base64_decode($imagen_base64);

            if ($imagen_decoded !== false) {
                $nombre_imagen = uniqid() . '.' . $extension;
                $ruta_archivo = '../../arch/' . $nombre_imagen;
                file_put_contents($ruta_archivo, $imagen_decoded);
            }
        }
    }

    $fechaactual = formatoFechaHoraBd();
    $tipolista_id = 49;

    // Obtener cuenta_id de la tabla compania
    $arrcompania = $conexion->doSelect(
        "cuenta_id",
        "compania",
        "compania_id = '$compania_id'"
    );

    $cuenta_guardar = (count($arrcompania) > 0) ? $arrcompania[0]['cuenta_id'] : $cuenta_id_token;
    $compania_guardar = !empty($compania_id) ? $compania_id : '';
    $lista_ppal = 0;

    // Usar función del sistema para modificar lista
    $resultadoGuardarLista = GuardarProcesoModificarLista(
        $lista_id,          // lista_id
        $lista_nombre,      // lista_nombre
        '',                 // lista_nombredos
        $lista_descrip,     // lista_descrip
        $nombre_imagen,     // lista_img
        $lista_ppal,        // lista_ppal
        $lista_orden,       // lista_orden
        $tipolista_id,      // tipolista_id (49 = requisitos)
        $cuenta_guardar,    // cuenta_id
        $compania_guardar,  // compania_id
        '',                 // lista_icono
        '',                 // lista_color
        0,                  // lista_idrel
        '',                 // lista_url
        $fechaactual,       // fecha modificación
        $listacuenta_id,    // listacuenta_id
        null,               // lista_mostrarppal
        null,               // lista_idreldos
        $lista_cod,         // lista_cod
        null,               // lista_resumen
        null,               // lista_img2
        $usuario_id,        // usuario_id (quien modifica)
        $perfil_id          // perfil_id
    );

    // Verificar si existe relación con perfil
    $arrresultado = $conexion->doSelect(
        "listarequisitoperfil_id",
        "listarequisitoperfil",
        "l_requisitolista_id = '$lista_id'"
    );

    if (count($arrresultado) == 0) {
        // Insertar nueva relación (incluye tipo de archivo esperado)
        $resultado = $conexion->doInsert(
            "listarequisitoperfil
            (l_requisitolista_id, perfil_id, cuenta_id, compania_id, listarequisitoperfil_activo,
            listarequisitoperfil_eliminado, listarequisitoperfil_fechareg, usuario_idreg, l_tipoarchivo_id)",
            "'$lista_id', '$perfil_id_asignar', '$cuenta_guardar', '$compania_guardar',
            '1', '0', '$fechaactual', '$usuario_id', '$tipo_archivo'"
        );
    } else {
        // Actualizar relación existente (incluye tipo de archivo)
        if (!empty($perfil_id_asignar)) {
            $resultado = $conexion->doUpdate(
                "listarequisitoperfil",
                "perfil_id = '$perfil_id_asignar', l_tipoarchivo_id = '$tipo_archivo'",
                "l_requisitolista_id = '$lista_id'"
            );
        }
    }

    $urlarch = ObtenerUrlArch($compania_guardar);

    echo json_encode([
        'code' => 100,
        'message' => 'Requisito actualizado exitosamente',
        'data' => [
            'lista_id' => $lista_id,
            'listacuenta_id' => $resultadoGuardarLista["listacuenta_id"],
            'lista_nombre' => utf8_encode($lista_nombre),
            'lista_img' => !empty($nombre_imagen) ? $nombre_imagen : null,
            'lista_img_url' => !empty($nombre_imagen) ? $urlarch . '/' . $nombre_imagen : null
        ]
    ]);
    exit();
}

// ====================================================================
// DELETE - Eliminar requisito (borrado lógico)
// ====================================================================
else if ($metodo == "DELETE") {

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Obtener parámetros
    $token = isset($data['token']) ? $data['token'] : '';
    $lista_id = isset($data['lista_id']) ? $data['lista_id'] : '';
    $compania_id = isset($data['compania']) ? $data['compania'] : '';

    // Validar token
    if (empty($token)) {
        echo json_encode(['code' => 102, 'message' => 'Token es requerido']);
        exit();
    }

    // Validar ID
    if (empty($lista_id)) {
        echo json_encode(['code' => 102, 'message' => 'ID del requisito es requerido']);
        exit();
    }

    // Validar token y obtener datos de usuario
    $arrresultado = $conexion->doSelect(
        "usuario.usuario_id, perfil.perfil_idorig, usuario.usuario_activo",
        "usuario
        inner join perfil on perfil.perfil_id = usuario.perfil_id",
        "usuario.usuario_codverif = '$token' and usuario.usuario_eliminado = '0'"
    );

    if (count($arrresultado) == 0) {
        echo json_encode(['code' => 104, 'message' => 'Token no encontrado']);
        exit();
    }

    $datosToken = $arrresultado[0];
    $perfil_id = $datosToken['perfil_idorig'];
    $usuario_activo = $datosToken['usuario_activo'];

    if ($usuario_activo != '1') {
        echo json_encode(['code' => 103, 'message' => 'Usuario no activo']);
        exit();
    }

    // Permisos: Solo Admin Sistema, Admin Cuenta y Admin Compañía pueden eliminar
    if (!in_array($perfil_id, ['1', '2', '3'])) {
        echo json_encode(['code' => 101, 'message' => 'Sin permisos para eliminar requisitos']);
        exit();
    }

    // Verificar si el requisito existe
    $arrresultado = $conexion->doSelect(
        "lista_id, lista_ppal",
        "lista",
        "lista_id = '$lista_id' and lista_eliminado = '0' and tipolista_id = '49'"
    );

    if (count($arrresultado) == 0) {
        echo json_encode(['code' => 106, 'message' => 'Requisito no encontrado']);
        exit();
    }

    $lista_ppal = $arrresultado[0]['lista_ppal'];

    // Si es principal del sistema, solo marcar inactivo
    if ($lista_ppal == '1') {
        $resultado = $conexion->doUpdate(
            "lista",
            "lista_activo = '0'",
            "lista_id = '$lista_id'"
        );
    } else {
        // Si es personalizado, hacer borrado lógico
        $resultado = $conexion->doUpdate(
            "lista",
            "lista_activo = '0', lista_eliminado = '1'",
            "lista_id = '$lista_id'"
        );

        // También eliminar la personalización si existe
        if (!empty($compania_id)) {
            $conexion->doUpdate(
                "listacuenta",
                "listacuenta_activo = '0', listacuenta_eliminado = '1'",
                "lista_id = '$lista_id' and compania_id = '$compania_id'"
            );
        }
    }

    if ($resultado) {
        echo json_encode([
            'code' => 100,
            'message' => 'Requisito eliminado exitosamente'
        ]);
    } else {
        echo json_encode([
            'code' => 105,
            'message' => 'Error al eliminar requisito'
        ]);
    }
    exit();
}

else {
    echo json_encode(['code' => 105, 'message' => 'Método no soportado']);
    exit();
}
?>
