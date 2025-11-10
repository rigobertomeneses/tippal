<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set("display_errors", "0");

include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../models/lista.php';
require_once '../../vendor/autoload.php';

use ExpoSDK\Expo;
use ExpoSDK\ExpoMessage;

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
    (isset($valoresPost['pago_id'])) ? $pago_id=$valoresPost['pago_id'] : $pago_id='';
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
    } else if ($pago_id == "") {
        $valores = array(
            "code" => 102,
            "message" => "ID de pago requerido",
            "data" => [],
        );
    } else if ($estatus == "") {
        $valores = array(
            "code" => 102,
            "message" => "Estatus requerido",
            "data" => [],
        );
    } else {

        $fechaactual = formatoFechaHoraBd(1);
        $fechaactualreg = formatoFechaHoraBd();

        $instancialista = new Lista();

        $obtenerIdLista = 2;
        $obtenerTipoLista = 55;
        $idestatusaprobado = $instancialista->ObtenerIdLista($obtenerIdLista, $obtenerTipoLista);

        $obtenerIdLista = 3;
        $obtenerTipoLista = 55;
        $idestatusrechazado = $instancialista->ObtenerIdLista($obtenerIdLista, $obtenerTipoLista);

        // Obtener datos del pago
        $arrresultado = $conexion->doSelect(
            "pago.pago_id, pago.usuario_id, pago.cuenta_id, pago.compania_id, pago.pago_procesado,
            pago.pago_monto, pago.l_moneda_id,
            usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_notas as usuario_pushtoken,
            tipotransaccion.lista_cod as tipotransaccion_cod,
            pagotransaccion.trans_id",

            "pago
            INNER JOIN usuario ON usuario.usuario_id = pago.usuario_id
            LEFT JOIN pagotransaccion ON pagotransaccion.pago_id = pago.pago_id
            LEFT JOIN transaccion ON transaccion.trans_id = pagotransaccion.trans_id
            LEFT JOIN lista tipotransaccion ON tipotransaccion.lista_id = transaccion.l_tipotrans_id",

            "pago.pago_activo = '1' AND pago.pago_id = '$pago_id'"
        );

        if (count($arrresultado) > 0) {

            $valor = $arrresultado[0];

            $tipotransaccion_cod = utf8_encode($valor["tipotransaccion_cod"]);
            $trans_id = utf8_encode($valor["trans_id"]);
            $pago_usuario_id = utf8_encode($valor["usuario_id"]);
            $pago_cuenta_id = utf8_encode($valor["cuenta_id"]);
            $pago_compania_id = utf8_encode($valor["compania_id"]);
            $pago_procesado = utf8_encode($valor["pago_procesado"]);
            $pago_monto = utf8_encode($valor["pago_monto"]);
            $l_moneda_id = utf8_encode($valor["l_moneda_id"]);
            $usuario_nombre = utf8_encode($valor["usuario_nombre"]);
            $usuario_apellido = utf8_encode($valor["usuario_apellido"]);
            $usuario_pushtoken = utf8_encode($valor["usuario_pushtoken"]);

            // Convertir de moneda a monedas de la app (para compañía 395)
            if ($pago_compania_id == "395") {
                $arrresultado2 = $conexion->doSelect(
                    "tasacambio_id, l_moneda_idorigen, l_moneda_iddestino, tasacambiofuente_id,
                    tasacambio_ventaporcagregado, tasacambio_ventamontoagregado,
                    tasacambio_ventavalororig, tasacambio_ventavalor, tasacambio_compraporcagregado,
                    tasacambio_compramontoagregado, tasacambio_compravalororig, tasacambio_compravalor,
                    tasacambio_activo, tasacambio_eliminado,
                    DATE_FORMAT(tasacambio_fechareg,'%d/%m/%Y %H:%i:%s') as tasacambio_fechareg,
                    monedaorigen.lista_nombredos as monedaorigen_nombredos,
                    monedadestino.lista_nombredos as monedadestino_nombredos",

                    "tasacambio
                    LEFT JOIN lista monedaorigen ON monedaorigen.lista_id = tasacambio.l_moneda_idorigen
                    LEFT JOIN lista monedadestino ON monedadestino.lista_id = tasacambio.l_moneda_iddestino",

                    "tasacambio.compania_id = '$pago_compania_id' AND tasacambio.l_moneda_iddestino = '$l_moneda_id' AND tasacambio_activo = '1'"
                );

                if (count($arrresultado2) > 0) {
                    foreach($arrresultado2 as $nn=>$valor2) {
                        $tasacambio_ventavalor = ($valor2["tasacambio_ventavalor"]);
                    }
                    $pago_monto = round(($pago_monto / $tasacambio_ventavalor), 2);
                }
            }

            // Procesar según tipo de transacción
            if ($tipotransaccion_cod == "23") {
                // Proyecto Propuesta

                if ($pago_procesado != "1" && ($estatus == $idestatusaprobado || $estatus == $idestatusrechazado)) {

                    $arrresultado22 = $conexion->doSelect(
                        "trans_id, proy_id, propuestaproy_id",
                        "transaccionproyecto",
                        "trans_id = '$trans_id'"
                    );

                    foreach($arrresultado22 as $inn=>$valor22) {
                        $proy_id = ($valor22["proy_id"]);
                        $propuestaproy_id = ($valor22["propuestaproy_id"]);
                    }

                    if ($estatus == $idestatusaprobado) {
                        $obtenerCodigoListaProyecto = 3; // Trabajando
                        $obtenerCodigoListaPropuesta = 6; // En curso

                        $texresp = "Cambiado el Estatus Correctamente";
                        $correomasivocampana_titulo = "Pago aprobado";
                        $correomasivocampana_descripcorta = "Se ha aprobado el pago en su cuenta";
                    } else {
                        $obtenerCodigoListaProyecto = 2; // Seleccionado - Esperando pago en garantía
                        $obtenerCodigoListaPropuesta = 2; // Aceptada Propuesta, Esperando pago en garantía

                        $texresp = "Cambiado el Estatus Correctamente";
                        $correomasivocampana_titulo = "Pago rechazado";
                        $correomasivocampana_descripcorta = "Se ha rechazado el pago";
                    }

                    $obtenerTipoListaProyecto = 374; // Estatus del proyecto
                    $estatussidproyecto = $instancialista->ObtenerIdLista($obtenerCodigoListaProyecto, $obtenerTipoListaProyecto);

                    $obtenerTipoListaPropuesta = 375; // Propuestas para Proyectos
                    $estatussidpropuesta = $instancialista->ObtenerIdLista($obtenerCodigoListaPropuesta, $obtenerTipoListaPropuesta);

                    $resultado = $conexion->doUpdate(
                        "proyecto",
                        "estatus_id = '$estatussidproyecto'",
                        "proy_id = '$proy_id'"
                    );

                    $resultado = $conexion->doUpdate(
                        "propuesta_proyecto",
                        "estatus_id = '$estatussidpropuesta'",
                        "propuestaproy_id = '$propuestaproy_id'"
                    );

                } else {
                    $texresp = "Cambiado el Estatus Correctamente";
                    $correomasivocampana_titulo = "Su pago fue cambiado de estatus";
                    $correomasivocampana_descripcorta = "Se ha cambiado el estatus de su pago";
                }

            } else {
                // Proceso normal de depósito

                if ($pago_procesado != "1" && ($estatus == $idestatusaprobado || $estatus == $idestatusrechazado)) {

                    // Obtener balance del usuario
                    $arrresultado2 = $conexion->doSelect(
                        "usuariobalance_total, usuariobalance_bloqueado, usuariobalance_disponible, usuariobalance_pendiente, usuariobalance.usuariobalance_id",
                        "usuariobalance",
                        "usuariobalance_eliminado = '0' AND usuariobalance.usuario_id = '$pago_usuario_id' AND usuariobalance.compania_id = '$pago_compania_id'"
                    );

                    $usuariobalance_id = "";
                    $usuariobalance_total = 0;
                    $usuariobalance_disponible = 0;
                    $usuariobalance_bloqueado = 0;

                    foreach($arrresultado2 as $n=>$valor2) {
                        $usuariobalance_id = utf8_encode($valor2["usuariobalance_id"]);
                        $usuariobalance_total = utf8_encode($valor2["usuariobalance_total"]);
                        $usuariobalance_disponible = utf8_encode($valor2["usuariobalance_disponible"]);
                        $usuariobalance_bloqueado = utf8_encode($valor2["usuariobalance_bloqueado"]);
                    }

                    if ($estatus == $idestatusaprobado) {
                        // Aprobar: mover de bloqueado a disponible, incrementar total
                        $usuariobalance_bloqueado = $usuariobalance_bloqueado - $pago_monto;
                        $usuariobalance_disponible = $usuariobalance_disponible + $pago_monto;
                        $usuariobalance_total = $usuariobalance_total + $pago_monto;
                    } else if ($estatus == $idestatusrechazado) {
                        // Rechazar: quitar de bloqueado y de total
                        $usuariobalance_bloqueado = $usuariobalance_bloqueado - $pago_monto;
                        $usuariobalance_total = $usuariobalance_total - $pago_monto;
                    }

                    // Actualizar balance
                    $resultado = $conexion->doUpdate(
                        "usuariobalance",
                        "usuariobalance_bloqueado = '$usuariobalance_bloqueado',
                        usuariobalance_disponible = '$usuariobalance_disponible',
                        usuariobalance_total = '$usuariobalance_total'",
                        "usuario_id = '$pago_usuario_id' AND usuariobalance_eliminado = '0'"
                    );

                    // Marcar pago como procesado
                    $resultado = $conexion->doUpdate(
                        "pago",
                        "pago_procesado = '1'",
                        "pago_id = '$pago_id'"
                    );

                    $texresp = "Cambiado el Estatus Correctamente y se hizo efectivo el monto al usuario";

                    $correomasivocampana_titulo = "Depósito aprobado";
                    $correomasivocampana_descripcorta = "Se ha aprobado el depósito en su cuenta";

                } else {
                    $texresp = "Cambiado el Estatus Correctamente";
                    $correomasivocampana_titulo = "Su depósito fue cambiado de estatus";
                    $correomasivocampana_descripcorta = "Se ha cambiado el estatus de su depósito";
                }
            }

            // Enviar notificación push
            $obtenerCodigoLista = 2; // Enviado
            $obtenerTipoLista = 158; // Estatus de Correo Masivos
            $estatusenviado = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

            enviarNotificacionPushFunciones(0, $usuario_nombre, $usuario_apellido, null, $pago_usuario_id, $estatusenviado, $fechaactual, $correomasivocampana_descripcorta, $correomasivocampana_titulo, $usuario_pushtoken, $pago_cuenta_id, $pago_compania_id, $pago_id);

            // Actualizar estatus del pago
            $resultado = $conexion->doUpdate(
                "pago",
                "l_estatus_id = '$estatus'",
                "pago_id = '$pago_id'"
            );

            // Actualizar movimiento relacionado
            $resultado = $conexion->doUpdate(
                "movimiento",
                "l_estatus_id = '$estatus'",
                "elemento_id = '$pago_id'"
            );

            if ($resultado) {
                $valores = array(
                    "code" => 100,
                    "message" => $texresp,
                    "data" => array(
                        "pago_id" => $pago_id,
                        "l_estatus_id" => $estatus
                    ),
                );
            } else {
                $valores = array(
                    "code" => 105,
                    "message" => "Error al cambiar estatus",
                    "data" => [],
                );
            }

        } else {
            $valores = array(
                "code" => 106,
                "message" => "Pago no encontrado",
                "data" => [],
            );
        }
    }
}

// Función auxiliar para enviar notificaciones push
function enviarNotificacionPushFunciones($correomasivo_id=null, $usuario_nombre=null, $usuario_apellido=null, $usuario_email=null, $usuario_id=null, $estatusenviado=null, $fechaactual=null, $descripcion=null, $titulo=null, $usuario_pushtoken=null, $cuenta_id=null, $compania_id=null, $elemento_id=null) {

    if ($usuario_pushtoken == "") {
        return true;
    }

    $instancialista = new Lista();

    $obtenerCodigoLista = 3; // Usuarios
    $obtenerTipoLista = 276; // Tipo Notificación Mensaje Destino
    $tiponotificaciondestino = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

    $obtenerCodigoLista = 2; // Notificación a la App
    $obtenerTipoLista = 284; // Tipo Notificación Envío
    $tiponotificacionenvio = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

    $obtenerCodigoLista = 3; // No Leído
    $obtenerTipoLista = 158; // Estatus de Correo Masivos
    $estatusnoleido = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

    $conexion = new ConexionBd();

    $resultado = $conexion->doInsert(
        "correomasivo (correomasivocampana_id, correomasivo_fechaprog, correomasivo_fechaenvio,
        correomasivo_activo, correomasivo_eliminado, correomasivo_fechareg,
        l_estatus_id, cuenta_id, compania_id, usuario_idreg, correomasivo_nombre,
        correomasivo_cantidad, l_tiponotificaciondestino_id, l_tiponotificacionenvio_id)",

        "'0', null, '$fechaactual',
        '1', '0', '$fechaactual',
        '$estatusenviado', '$cuenta_id', '$compania_id', '$usuario_id', '$titulo',
        '1', '$tiponotificaciondestino', '$tiponotificacionenvio'"
    );

    $arrresultado2 = $conexion->doSelect("MAX(correomasivo_id) as correomasivo_id", "correomasivo");
    if (count($arrresultado2) > 0) {
        foreach($arrresultado2 as $i=>$valor) {
            $correomasivo_id = ($valor["correomasivo_id"]);
        }
    }

    $resultado = $conexion->doInsert(
        "correomasivodetalle (correomasivo_id, correomasivodet_usuarionombre, correomasivodet_usuarioemail, usuario_id,
        l_estatus_id, correomasivodet_activo, correomasivodet_eliminado, correomasivodet_fechareg,
        correomasivodet_usuariopush, correomasivodet_titulo, correomasivodet_descrip, elemento_id,
        usuario_iddestino)",

        "'$correomasivo_id', '$usuario_nombre $usuario_apellido', '$usuario_email', '$usuario_id',
        '$estatusnoleido', '1', '0', '$fechaactual',
        '$usuario_pushtoken', '$titulo', '$descripcion', '$elemento_id', '$usuario_id'"
    );

    $arrresultado2 = $conexion->doSelect("MAX(correomasivodet_id) as correomasivodet_id", "correomasivodetalle");
    if (count($arrresultado2) > 0) {
        foreach($arrresultado2 as $i=>$valor) {
            $correomasivodet_id = ($valor["correomasivodet_id"]);
        }
    }

    $valores = array(
        "id" => $elemento_id,
        "tipo" => "registroeventousuario"
    );

    $object = (object) $valores;

    if ($descripcion != "") {
        $messages = [
            new ExpoMessage([
                'title' => $titulo,
                'body' => $descripcion,
                'data' => $object
            ]),
        ];
    } else {
        $messages = [
            new ExpoMessage([
                'title' => $titulo,
                'data' => $object
            ]),
        ];
    }

    $defaultRecipients = [
        $usuario_pushtoken
    ];

    try {
        $response = (new Expo)->send($messages)->to($defaultRecipients)->push();
        $data = $response->getData();

        foreach($data as $i=>$valor) {
            $id = utf8_encode($valor["id"]);
            $status = utf8_encode($valor["status"]);

            if ($status == "ok") {
                $resultado = $conexion->doUpdate(
                    "correomasivodetalle",
                    "correomasivodet_usuariopushresponse = '1'",
                    "correomasivodet_id = '$correomasivodet_id'"
                );

                return true;
            }
        }
    } catch (Exception $e) {
        // Error al enviar notificación, pero no detener el proceso
        return false;
    }

    return true;
}

$resultado = json_encode($valores);
echo $resultado;
?>
