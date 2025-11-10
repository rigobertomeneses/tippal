<?php
/**
 * API REST - Requisitos de Usuarios
 *
 * Endpoints para gestionar requisitos cargados por usuarios
 * Basado en controllers/requisitousuario.php, verrequisitousuario.php y cargarrequisito.php
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
include_once '../../models/lista.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$conexion = new ConexionBd();

// ====================================================================
// GET - Listar requisitos de usuarios o obtener uno específico
// ====================================================================
if ($metodo == "GET") {

    // Obtener parámetros
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    $compania_id = isset($_GET['compania']) ? $_GET['compania'] : '';
    $requisito_id = isset($_GET['id']) ? $_GET['id'] : '';
    $estatus = isset($_GET['estatus']) ? $_GET['estatus'] : '';
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
    $usuario_id_filtro = isset($_GET['usuario_id']) ? $_GET['usuario_id'] : '';
    $tipo_requisito = isset($_GET['tipo_requisito']) ? $_GET['tipo_requisito'] : '';
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;

    // Validar token
    if (empty($token)) {
        echo json_encode(['code' => 102, 'message' => 'Token es requerido']);
        exit();
    }

    // Validar token y obtener datos de usuario
    $whereToken = "usuario.usuario_codverif = '$token' and usuario.usuario_eliminado = '0'";
    if (!empty($compania_id)) {
        $whereToken .= " and usuario.compania_id = '$compania_id'";
    }

    $arrresultado = $conexion->doSelect(
        "usuario.usuario_id, perfil.perfil_idorig, usuario.usuario_activo, usuario.cuenta_id",
        "usuario
        inner join perfil on perfil.perfil_id = usuario.perfil_id",
        $whereToken
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
    $where = "listarequisito.lista_activo = '1' and listarequisito.tipolista_id = '49' and requisito.requisito_eliminado = '0'";

    if ($perfil_id == "1") {
        // Administrador del Sistema - Ve todo
    } else if ($perfil_id == "2") {
        // Administrador de Cuenta
        $where .= " and requisito.cuenta_id = '$cuenta_id' ";
    } else {
        // Administrador de Compañía o menor
        if (!empty($compania_id)) {
            $where .= " and requisito.cuenta_id = '$cuenta_id' and requisito.compania_id = '$compania_id' ";
        }
    }

    // Filtros adicionales
    if (!empty($requisito_id)) {
        $where .= " and requisito.requisito_id = '$requisito_id' ";
    }

    if (!empty($estatus)) {
        $where .= " and requisito.l_estatus_id = '$estatus' ";
    }

    if (!empty($fecha_desde) && !empty($fecha_hasta)) {
        $where .= " and DATE(requisito.requisito_fechareg) BETWEEN '$fecha_desde' and '$fecha_hasta' ";
    }

    if (!empty($usuario_id_filtro)) {
        $where .= " and requisito.usuario_id = '$usuario_id_filtro' ";
    }

    if (!empty($tipo_requisito)) {
        $where .= " and requisito.l_requisitolista_id = '$tipo_requisito' ";
    }

    // Calcular OFFSET para paginación
    $offset = ($pagina - 1) * $limite;

    // Consultar requisitos con archivos adjuntos
    $arrresultado = $conexion->doSelect(
        "requisito.requisito_id, requisito.l_requisitolista_id, requisito.requisito_descrip,
        requisito.l_tipoarchivo_id, requisito.requisito_arch, requisito.requisito_archnombre,
        requisito.requisito_cantarchivos, requisito.cuenta_id, requisito.compania_id,
        requisito.requisito_activo, requisito.requisito_eliminado,
        DATE_FORMAT(requisito.requisito_fechareg,'%d/%m/%Y %H:%i:%s') as requisito_fechareg,
        requisito.usuario_idreg, requisito.l_estatus_id, requisito.usuario_id,

        usuario.usuario_id, usuario.usuario_codigo, usuario.usuario_email,
        usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_telf,
        usuario.usuario_activo, usuario.usuario_eliminado, usuario.usuario_documento,
        usuario.usuario_img, usuario.perfil_id, usuario.l_tipousuarioserv_id,

        cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
        cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre, compania.compania_urlweb,

        listarequisito.lista_id as requisitolista_id,
        listarequisito.lista_nombre as requisitolista_nombre,
        estatus.lista_nombre as estatus_nombre,
        tipoarchivo.lista_nombre as tipoarchivo_nombre,
        tipoarchivo.lista_img as tipoarchivo_img",

        "lista listarequisito
        inner join requisito on requisito.l_requisitolista_id = listarequisito.lista_id
        left join lista tipoarchivo on tipoarchivo.lista_id = requisito.l_tipoarchivo_id
        left join lista estatus on estatus.lista_id = requisito.l_estatus_id
        left join usuario on usuario.usuario_id = requisito.usuario_id
        left join usuario cuenta on cuenta.usuario_id = requisito.cuenta_id
        left join compania on compania.compania_id = requisito.compania_id",

        $where,
        null,
        "requisito.requisito_fechareg desc LIMIT $limite OFFSET $offset"
    );

    // Si se solicita un requisito específico, incluir los archivos adjuntos
    $datos = [];
    $urlarch = ObtenerUrlArch($compania_id);

    foreach ($arrresultado as $valor) {
        $item = [];

        $item['requisito_id'] = utf8_encode($valor['requisito_id']);
        $item['l_requisitolista_id'] = utf8_encode($valor['l_requisitolista_id']);
        $item['requisito_descrip'] = utf8_encode($valor['requisito_descrip']);
        $item['l_tipoarchivo_id'] = utf8_encode($valor['l_tipoarchivo_id']);
        $item['requisito_arch'] = utf8_encode($valor['requisito_arch']);
        $item['requisito_archnombre'] = utf8_encode($valor['requisito_archnombre']);
        $item['requisito_cantarchivos'] = utf8_encode($valor['requisito_cantarchivos']);
        $item['cuenta_id'] = utf8_encode($valor['cuenta_id']);
        $item['compania_id'] = utf8_encode($valor['compania_id']);
        $item['requisito_activo'] = utf8_encode($valor['requisito_activo']);
        $item['requisito_fechareg'] = utf8_encode($valor['requisito_fechareg']);
        $item['l_estatus_id'] = utf8_encode($valor['l_estatus_id']);
        $item['usuario_id'] = utf8_encode($valor['usuario_id']);

        // Datos del usuario
        $item['usuario_codigo'] = utf8_encode($valor['usuario_codigo']);
        $item['usuario_email'] = utf8_encode($valor['usuario_email']);
        $item['usuario_nombre'] = utf8_encode($valor['usuario_nombre']);
        $item['usuario_apellido'] = utf8_encode($valor['usuario_apellido']);
        $item['usuario_telf'] = utf8_encode($valor['usuario_telf']);
        $item['usuario_documento'] = utf8_encode($valor['usuario_documento']);

        // Datos de cuenta y compañía
        $item['cuenta_nombre'] = utf8_encode($valor['cuenta_nombre']);
        $item['cuenta_apellido'] = utf8_encode($valor['cuenta_apellido']);
        $item['compania_nombre'] = utf8_encode($valor['compania_nombre']);
        $item['compania_urlweb'] = utf8_encode($valor['compania_urlweb']);

        // Datos del tipo de requisito
        $item['requisitolista_id'] = utf8_encode($valor['requisitolista_id']);
        $item['requisitolista_nombre'] = utf8_encode($valor['requisitolista_nombre']);

        // Estatus
        $item['estatus_nombre'] = utf8_encode($valor['estatus_nombre']);

        // Tipo de archivo
        $item['tipoarchivo_nombre'] = utf8_encode($valor['tipoarchivo_nombre']);
        $item['tipoarchivo_img'] = utf8_encode($valor['tipoarchivo_img']);

        // Si se solicita un requisito específico, incluir archivos adjuntos
        if (!empty($requisito_id)) {
            $arrarchivos = $conexion->doSelect(
                "requisitoarch_id, requisitoarch_arch, requisitoarch_nombre,
                l_tipoarchivo_id, requisitoarch_activo,
                DATE_FORMAT(requisitoarch_fechareg,'%d/%m/%Y %H:%i:%s') as requisitoarch_fechareg",
                "requisitoarchivo",
                "requisito_id = '{$item['requisito_id']}' and requisitoarch_eliminado = '0'",
                null,
                "requisitoarch_fechareg desc"
            );

            $archivos = [];
            foreach ($arrarchivos as $archivo) {
                $arch = [];
                $arch['requisitoarch_id'] = utf8_encode($archivo['requisitoarch_id']);
                $arch['requisitoarch_arch'] = utf8_encode($archivo['requisitoarch_arch']);
                $arch['requisitoarch_nombre'] = utf8_encode($archivo['requisitoarch_nombre']);
                $arch['l_tipoarchivo_id'] = utf8_encode($archivo['l_tipoarchivo_id']);
                $arch['requisitoarch_activo'] = utf8_encode($archivo['requisitoarch_activo']);
                $arch['requisitoarch_fechareg'] = utf8_encode($archivo['requisitoarch_fechareg']);
                $arch['requisitoarch_url'] = $urlarch . '/' . $arch['requisitoarch_arch'];

                $archivos[] = $arch;
            }

            $item['archivos'] = $archivos;
        }

        $datos[] = $item;
    }

    // Contar total de registros para paginación
    $arrTotal = $conexion->doSelect(
        "COUNT(*) as total",
        "lista listarequisito
        inner join requisito on requisito.l_requisitolista_id = listarequisito.lista_id",
        $where
    );
    $total = $arrTotal[0]['total'];

    echo json_encode([
        'code' => 100,
        'message' => 'Requisitos obtenidos exitosamente',
        'data' => $datos,
        'pagination' => [
            'pagina' => $pagina,
            'limite' => $limite,
            'total' => (int)$total,
            'total_paginas' => ceil($total / $limite)
        ]
    ]);
    exit();
}

// ====================================================================
// POST - Cargar nuevo requisito de usuario (con archivo)
// ====================================================================
else if ($metodo == "POST") {

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Obtener parámetros
    $token = isset($data['token']) ? $data['token'] : '';
    $compania_id = isset($data['compania']) ? $data['compania'] : '';
    $usuario_id_param = isset($data['usuario_id']) ? $data['usuario_id'] : '';
    $tipo_requisito = isset($data['tipo_requisito']) ? $data['tipo_requisito'] : '';
    $archivo = isset($data['archivo']) ? $data['archivo'] : ''; // Base64
    $archivo_nombre = isset($data['archivo_nombre']) ? utf8_decode($data['archivo_nombre']) : '';

    // Validar token
    if (empty($token)) {
        echo json_encode(['code' => 102, 'message' => 'Token es requerido']);
        exit();
    }

    // Validar datos requeridos
    if (empty($compania_id)) {
        echo json_encode(['code' => 102, 'message' => 'Compañía es requerida']);
        exit();
    }

    if (empty($usuario_id_param)) {
        echo json_encode(['code' => 102, 'message' => 'Usuario es requerido']);
        exit();
    }

    if (empty($tipo_requisito)) {
        echo json_encode(['code' => 102, 'message' => 'Tipo de requisito es requerido']);
        exit();
    }

    if (empty($archivo)) {
        echo json_encode(['code' => 102, 'message' => 'Archivo es requerido']);
        exit();
    }

    // Validar token y obtener datos de usuario
    $whereToken = "usuario.usuario_codverif = '$token' and usuario.usuario_eliminado = '0'";
    if (!empty($compania_id)) {
        $whereToken .= " and usuario.compania_id = '$compania_id'";
    }

    $arrresultado = $conexion->doSelect(
        "usuario.usuario_id, perfil.perfil_idorig, usuario.usuario_activo, usuario.cuenta_id",
        "usuario
        inner join perfil on perfil.perfil_id = usuario.perfil_id",
        $whereToken
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

    // Procesar archivo en base64
    $nombre_archivo_guardado = '';
    $nombre_archivo_original = $archivo_nombre;

    if (!empty($archivo)) {
        // Detectar tipo de archivo y validar
        $extension = '';

        if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $archivo, $matches)) {
            $extension = ($matches[1] == 'jpeg') ? 'jpg' : $matches[1];
            $archivo_base64 = preg_replace('/^data:image\/(jpeg|jpg|png|gif);base64,/', '', $archivo);
        } else if (preg_match('/^data:application\/pdf;base64,/', $archivo)) {
            $extension = 'pdf';
            $archivo_base64 = preg_replace('/^data:application\/pdf;base64,/', '', $archivo);
        } else if (preg_match('/^data:application\/(msword|vnd\.openxmlformats-officedocument\.wordprocessingml\.document);base64,/', $archivo, $matches)) {
            $extension = ($matches[1] == 'msword') ? 'doc' : 'docx';
            $archivo_base64 = preg_replace('/^data:application\/(msword|vnd\.openxmlformats-officedocument\.wordprocessingml\.document);base64,/', '', $archivo);
        } else if (preg_match('/^data:application\/(vnd\.ms-excel|vnd\.openxmlformats-officedocument\.spreadsheetml\.sheet);base64,/', $archivo, $matches)) {
            $extension = ($matches[1] == 'vnd.ms-excel') ? 'xls' : 'xlsx';
            $archivo_base64 = preg_replace('/^data:application\/(vnd\.ms-excel|vnd\.openxmlformats-officedocument\.spreadsheetml\.sheet);base64,/', '', $archivo);
        } else {
            // Intentar decodificar directamente
            $archivo_base64 = $archivo;
            // Detectar extensión del nombre si viene
            if (!empty($archivo_nombre)) {
                $extension = pathinfo($archivo_nombre, PATHINFO_EXTENSION);
            } else {
                $extension = 'bin'; // Archivo genérico
            }
        }

        $archivo_decoded = base64_decode($archivo_base64);

        if ($archivo_decoded !== false) {
            $nombre_archivo_guardado = uniqid() . '.' . $extension;
            $ruta_archivo = '../arch/' . $nombre_archivo_guardado;
            file_put_contents($ruta_archivo, $archivo_decoded);

            if (empty($nombre_archivo_original)) {
                $nombre_archivo_original = $nombre_archivo_guardado;
            }
        } else {
            echo json_encode(['code' => 105, 'message' => 'Error al procesar archivo']);
            exit();
        }
    }

    $fechaactual = formatoFechaHoraBd();

    // Usar el cuenta_id del admin que está asignando el requisito
    // Esto asegura que el admin pueda ver los requisitos que asignó
    $cuenta_guardar = $cuenta_id_token;

    // Obtener el tipo de archivo esperado desde la lista maestra de requisitos
    $arrTipoArchivo = $conexion->doSelect(
        "l_tipoarchivo_id",
        "listarequisitoperfil",
        "l_requisitolista_id = '$tipo_requisito' and listarequisitoperfil_activo = '1' and listarequisitoperfil_eliminado = '0'",
        null,
        null,
        1
    );

    // Si no se encuentra o es NULL, usar '1' (Imagen) por defecto
    $tipo_archivo = '1';
    if (count($arrTipoArchivo) > 0 && !empty($arrTipoArchivo[0]['l_tipoarchivo_id'])) {
        $tipo_archivo = $arrTipoArchivo[0]['l_tipoarchivo_id'];
    }

    // Obtener estatus pendiente de la lista
    $instancialista = new Lista();
    $obtenerIdLista = 5; // 5 = Pendiente por cargar
    $obtenerTipoLista = 50;
    $estatuspendiente = $instancialista->ObtenerIdLista($obtenerIdLista, $obtenerTipoLista);

    // Verificar si ya existe un requisito para este usuario y tipo (incluso si está eliminado)
    $arrresultado2 = $conexion->doSelect(
        "requisito_id",
        "requisito",
        "l_requisitolista_id = '$tipo_requisito' and usuario_id = '$usuario_id_param'"
    );

    if (count($arrresultado2) > 0) {
        // Ya existe, reactivarlo
        $requisito_id = $arrresultado2[0]['requisito_id'];

        // Reactivar el requisito si estaba inactivo o eliminado
        // También actualizar cuenta_id y compania_id para que coincida con el admin actual
        $conexion->doUpdate(
            "requisito",
            "requisito_activo = '1', requisito_eliminado = '0', l_estatus_id = '$estatuspendiente', cuenta_id = '$cuenta_guardar', compania_id = '$compania_id', requisito_fechareg = '$fechaactual'",
            "requisito_id = '$requisito_id'"
        );
    } else {
        // No existe, crear nuevo registro de requisito
        $resultado = $conexion->doInsert(
            "requisito
            (l_requisitolista_id, requisito_descrip, l_tipoarchivo_id, requisito_arch,
            requisito_archnombre, requisito_cantarchivos, cuenta_id, compania_id,
            requisito_activo, requisito_eliminado, requisito_fechareg,
            usuario_idreg, l_estatus_id, usuario_id)",
            "'$tipo_requisito', '', '$tipo_archivo', '$nombre_archivo_guardado',
            '$nombre_archivo_original', '1', '$cuenta_guardar', '$compania_id',
            '1', '0', '$fechaactual',
            '$usuario_id', '$estatuspendiente', '$usuario_id_param'"
        );

        $arrresultado2 = $conexion->doSelect(
            "max(requisito_id) as requisito_id",
            "requisito"
        );
        $requisito_id = $arrresultado2[0]['requisito_id'];
    }

    // Insertar archivo adjunto
    $resultado = $conexion->doInsert(
        "requisitoarchivo
        (requisito_id, requisitoarch_arch, requisitoarch_nombre, l_tipoarchivo_id,
        requisitoarch_activo, requisitoarch_eliminado, requisitoarch_fechareg, usuario_idreg, l_estatus_id)",
        "'$requisito_id', '$nombre_archivo_guardado', '$nombre_archivo_original', '0',
        '1', '0', '$fechaactual', '$usuario_id', '$estatuspendiente'"
    );

    // Actualizar contador de archivos
    $arrresultado2 = $conexion->doSelect(
        "count(requisito_id) as total",
        "requisitoarchivo",
        "requisito_id = '$requisito_id' and requisitoarch_activo = '1'"
    );
    $total = $arrresultado2[0]['total'];

    $conexion->doUpdate(
        "requisito",
        "requisito_cantarchivos = '$total'",
        "requisito_id = '$requisito_id'"
    );

    $urlarch = ObtenerUrlArch($compania_id);

    echo json_encode([
        'code' => 100,
        'message' => 'Requisito cargado exitosamente',
        'data' => [
            'requisito_id' => $requisito_id,
            'archivo_nombre' => $nombre_archivo_original,
            'archivo_guardado' => $nombre_archivo_guardado,
            'archivo_url' => $urlarch . '/' . $nombre_archivo_guardado,
            'total_archivos' => (int)$total
        ]
    ]);
    exit();
}

// ====================================================================
// PUT - Actualizar estatus de requisito o archivo
// ====================================================================
else if ($metodo == "PUT") {

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Obtener parámetros
    $token = isset($data['token']) ? $data['token'] : '';
    $accion = isset($data['accion']) ? $data['accion'] : ''; // 'cambiar_estatus' | 'cambiar_estatus_archivo'
    $requisito_id = isset($data['requisito_id']) ? $data['requisito_id'] : '';
    $requisitoarch_id = isset($data['requisitoarch_id']) ? $data['requisitoarch_id'] : '';
    $estatus = isset($data['estatus']) ? $data['estatus'] : '';

    // Validar token
    if (empty($token)) {
        echo json_encode(['code' => 102, 'message' => 'Token es requerido']);
        exit();
    }

    // Validar acción
    if (empty($accion)) {
        echo json_encode(['code' => 102, 'message' => 'Acción es requerida']);
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

    // Permisos: Solo Admin Sistema, Admin Cuenta y Admin Compañía
    if (!in_array($perfil_id, ['1', '2', '3'])) {
        echo json_encode(['code' => 101, 'message' => 'Sin permisos para modificar requisitos']);
        exit();
    }

    // Ejecutar acción correspondiente
    if ($accion == 'cambiar_estatus') {
        if (empty($requisito_id) || empty($estatus)) {
            echo json_encode(['code' => 102, 'message' => 'requisito_id y estatus son requeridos']);
            exit();
        }

        $resultado = $conexion->doUpdate(
            "requisito",
            "l_estatus_id = '$estatus'",
            "requisito_id = '$requisito_id'"
        );

        // Verificar estatus del usuario (función del sistema)
        $arrresultado = $conexion->doSelect(
            "usuario_id",
            "requisito",
            "requisito_id = '$requisito_id'"
        );
        if (count($arrresultado) > 0) {
            $usuario_id_verificar = $arrresultado[0]['usuario_id'];
            VerificarUsuarioEstatus($usuario_id_verificar);
        }

        if ($resultado) {
            echo json_encode([
                'code' => 100,
                'message' => 'Estatus actualizado exitosamente'
            ]);
        } else {
            echo json_encode([
                'code' => 105,
                'message' => 'Error al actualizar estatus'
            ]);
        }
        exit();

    } else if ($accion == 'cambiar_estatus_archivo') {
        if (empty($requisitoarch_id) || !isset($estatus)) {
            echo json_encode(['code' => 102, 'message' => 'requisitoarch_id y estatus son requeridos']);
            exit();
        }

        $resultado = $conexion->doUpdate(
            "requisitoarchivo",
            "requisitoarch_activo = '$estatus'",
            "requisitoarch_id = '$requisitoarch_id'"
        );

        if ($resultado) {
            echo json_encode([
                'code' => 100,
                'message' => 'Estatus del archivo actualizado exitosamente'
            ]);
        } else {
            echo json_encode([
                'code' => 105,
                'message' => 'Error al actualizar estatus del archivo'
            ]);
        }
        exit();

    } else {
        echo json_encode(['code' => 102, 'message' => 'Acción no válida']);
        exit();
    }
}

// ====================================================================
// DELETE - Eliminar requisito o archivo adjunto
// ====================================================================
else if ($metodo == "DELETE") {

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Obtener parámetros
    $token = isset($data['token']) ? $data['token'] : '';
    $tipo = isset($data['tipo']) ? $data['tipo'] : ''; // 'requisito' | 'archivo'
    $requisito_id = isset($data['requisito_id']) ? $data['requisito_id'] : '';
    $requisitoarch_id = isset($data['requisitoarch_id']) ? $data['requisitoarch_id'] : '';

    // Validar token
    if (empty($token)) {
        echo json_encode(['code' => 102, 'message' => 'Token es requerido']);
        exit();
    }

    // Validar tipo
    if (empty($tipo)) {
        echo json_encode(['code' => 102, 'message' => 'Tipo es requerido']);
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

    // Permisos: Solo Admin Sistema, Admin Cuenta y Admin Compañía
    if (!in_array($perfil_id, ['1', '2', '3'])) {
        echo json_encode(['code' => 101, 'message' => 'Sin permisos para eliminar']);
        exit();
    }

    // Ejecutar eliminación según tipo
    if ($tipo == 'requisito') {
        if (empty($requisito_id)) {
            echo json_encode(['code' => 102, 'message' => 'requisito_id es requerido']);
            exit();
        }

        $resultado = $conexion->doUpdate(
            "requisito",
            "requisito_activo = '0', requisito_eliminado = '1'",
            "requisito_id = '$requisito_id'"
        );

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

    } else if ($tipo == 'archivo') {
        if (empty($requisitoarch_id)) {
            echo json_encode(['code' => 102, 'message' => 'requisitoarch_id es requerido']);
            exit();
        }

        $resultado = $conexion->doUpdate(
            "requisitoarchivo",
            "requisitoarch_activo = '0', requisitoarch_eliminado = '1'",
            "requisitoarch_id = '$requisitoarch_id'"
        );

        if ($resultado) {
            echo json_encode([
                'code' => 100,
                'message' => 'Archivo eliminado exitosamente'
            ]);
        } else {
            echo json_encode([
                'code' => 105,
                'message' => 'Error al eliminar archivo'
            ]);
        }
        exit();

    } else {
        echo json_encode(['code' => 102, 'message' => 'Tipo no válido']);
        exit();
    }
}

else {
    echo json_encode(['code' => 105, 'message' => 'Método no soportado']);
    exit();
}
?>
