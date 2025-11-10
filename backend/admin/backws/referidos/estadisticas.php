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
    "message" => "Error al obtener estadísticas",
    "data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo == "POST") {

    $conexion = new ConexionBd();
    $valoresPost = json_decode(file_get_contents('php://input'), true);

    // Obtener parámetros
    (isset($valoresPost['token'])) ? $token = $valoresPost['token'] : $token = '';
    (isset($valoresPost['compania'])) ? $compania_id = $valoresPost['compania'] : $compania_id = '';
    (isset($valoresPost['tipo'])) ? $tipo = $valoresPost['tipo'] : $tipo = 'resumen'; // resumen, detalle, referidos
    (isset($valoresPost['fecha_inicio'])) ? $fecha_inicio = $valoresPost['fecha_inicio'] : $fecha_inicio = '';
    (isset($valoresPost['fecha_fin'])) ? $fecha_fin = $valoresPost['fecha_fin'] : $fecha_fin = '';

    if ($compania_id == "") {
        $compania_id = 387;
    }

    // Validar token y obtener usuario
    $usuario_id = "";
    $usuario_nombre = "";
    $arrresultado = $conexion->doSelect(
        "usuario_id, usuario_nombre",
        "usuario",
        "usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$compania_id'"
    );

    foreach($arrresultado as $n => $valor) {
        $usuario_id = $valor["usuario_id"];
        $usuario_nombre = utf8_encode($valor["usuario_nombre"]);
    }

    if ($usuario_id == "") {
        $valores = array(
            "code" => 103,
            "message" => "Token inválido",
            "data" => [],
        );
    } else {

        $data = array();

        if ($tipo == "resumen") {
            // Obtener estadísticas generales del usuario
            $stats = $conexion->doSelect(
                "total_compartidos, total_clicks, total_registros_web, total_conversiones, puntos_ganados",
                "usuario_referidos_stats",
                "usuario_id = '$usuario_id' AND compania_id = '$compania_id'"
            );

            if (count($stats) > 0) {
                $data['estadisticas'] = array(
                    "total_compartidos" => intval($stats[0]['total_compartidos']),
                    "total_clicks" => intval($stats[0]['total_clicks']),
                    "total_registros_web" => intval($stats[0]['total_registros_web']),
                    "total_conversiones" => intval($stats[0]['total_conversiones']),
                    "puntos_ganados" => floatval($stats[0]['puntos_ganados']),
                    "tasa_conversion" => ($stats[0]['total_clicks'] > 0) ?
                        round(($stats[0]['total_conversiones'] / $stats[0]['total_clicks']) * 100, 2) : 0
                );
            } else {
                // Usuario sin estadísticas aún
                $data['estadisticas'] = array(
                    "total_compartidos" => 0,
                    "total_clicks" => 0,
                    "total_registros_web" => 0,
                    "total_conversiones" => 0,
                    "puntos_ganados" => 0,
                    "tasa_conversion" => 0
                );
            }

            // Obtener últimos referidos convertidos
            $referidos_recientes = $conexion->doSelect(
                "u.usuario_id, u.usuario_nombre, u.usuario_email, u.fecha_referido, u.usuario_imagen",
                "usuario u",
                "u.referido_por_usuario_id = '$usuario_id' AND u.compania_id = '$compania_id'",
                "",
                "u.fecha_referido DESC LIMIT 5"
            );

            $data['referidos_recientes'] = array();
            foreach($referidos_recientes as $ref) {
                $data['referidos_recientes'][] = array(
                    "id" => $ref['usuario_id'],
                    "nombre" => utf8_encode($ref['usuario_nombre']),
                    "email" => utf8_encode($ref['usuario_email']),
                    "fecha" => $ref['fecha_referido'],
                    "imagen" => ($ref['usuario_imagen'] != '') ?
                        'https://www.gestiongo.com/admin/arch/' . $ref['usuario_imagen'] : null,
                    "puntos_otorgados" => 10
                );
            }

        } else if ($tipo == "detalle") {
            // Obtener historial detallado de referidos
            $where = "referidor_usuario_id = '$usuario_id' AND compania_id = '$compania_id'";

            if ($fecha_inicio != '' && $fecha_fin != '') {
                $where .= " AND DATE(fecha_click) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            }

            $historial = $conexion->doSelect(
                "referido_id, email, nombre, producto_id, estado, fecha_click, fecha_registro_web, fecha_conversion",
                "referidos_pendientes",
                $where,
                "",
                "created_at DESC LIMIT 50"
            );

            $data['historial'] = array();
            foreach($historial as $h) {
                // Obtener información del producto si existe
                $producto_nombre = "";
                if ($h['producto_id'] != '') {
                    $prod = $conexion->doSelect(
                        "prod_nombre",
                        "producto",
                        "prod_id = '" . $h['producto_id'] . "' AND compania_id = '$compania_id'"
                    );
                    if (count($prod) > 0) {
                        $producto_nombre = utf8_encode($prod[0]['prod_nombre']);
                    }
                }

                $data['historial'][] = array(
                    "id" => $h['referido_id'],
                    "email" => utf8_encode($h['email']),
                    "nombre" => utf8_encode($h['nombre']),
                    "producto" => $producto_nombre,
                    "estado" => $h['estado'],
                    "fecha_click" => $h['fecha_click'],
                    "fecha_registro_web" => $h['fecha_registro_web'],
                    "fecha_conversion" => $h['fecha_conversion'],
                    "puntos" => ($h['estado'] == 'convertido') ? 10 : 0
                );
            }

        } else if ($tipo == "referidos") {
            // Lista completa de usuarios referidos
            $referidos = $conexion->doSelect(
                "u.usuario_id, u.usuario_nombre, u.usuario_email, u.usuario_telefono, u.fecha_referido,
                 u.usuario_imagen, u.perfil_id, p.perfil_nombre",
                "usuario u
                 LEFT JOIN perfil p ON u.perfil_id = p.perfil_id",
                "u.referido_por_usuario_id = '$usuario_id' AND u.compania_id = '$compania_id'",
                "",
                "u.fecha_referido DESC"
            );

            $data['referidos'] = array();
            $data['total_referidos'] = count($referidos);
            $data['puntos_totales'] = count($referidos) * 10;

            foreach($referidos as $ref) {
                // Calcular valor del referido (compras realizadas)
                $compras = $conexion->doSelect(
                    "COUNT(*) as total_pedidos, SUM(pedido_total) as total_gastado",
                    "pedido",
                    "usuario_id = '" . $ref['usuario_id'] . "' AND pedido_estado IN (4, 5) AND compania_id = '$compania_id'"
                );

                $valor_referido = 0;
                $total_pedidos = 0;
                if (count($compras) > 0) {
                    $valor_referido = floatval($compras[0]['total_gastado']);
                    $total_pedidos = intval($compras[0]['total_pedidos']);
                }

                $data['referidos'][] = array(
                    "id" => $ref['usuario_id'],
                    "nombre" => utf8_encode($ref['usuario_nombre']),
                    "email" => utf8_encode($ref['usuario_email']),
                    "telefono" => $ref['usuario_telefono'],
                    "fecha_registro" => $ref['fecha_referido'],
                    "perfil" => utf8_encode($ref['perfil_nombre']),
                    "imagen" => ($ref['usuario_imagen'] != '') ?
                        'https://www.gestiongo.com/admin/arch/' . $ref['usuario_imagen'] : null,
                    "total_pedidos" => $total_pedidos,
                    "valor_total" => $valor_referido,
                    "puntos_generados" => 10
                );
            }
        }

        // Obtener ranking si aplica
        if ($tipo == "resumen") {
            // Obtener posición en el ranking
            $ranking = $conexion->doSelect(
                "usuario_id, total_conversiones",
                "usuario_referidos_stats",
                "compania_id = '$compania_id'",
                "",
                "total_conversiones DESC"
            );

            $posicion = 0;
            foreach($ranking as $i => $r) {
                if ($r['usuario_id'] == $usuario_id) {
                    $posicion = $i + 1;
                    break;
                }
            }

            $data['ranking'] = array(
                "posicion" => $posicion,
                "total_usuarios" => count($ranking)
            );

            // Top 3 referidores
            $top_referidores = $conexion->doSelect(
                "s.usuario_id, s.total_conversiones, s.puntos_ganados, u.usuario_nombre, u.usuario_imagen",
                "usuario_referidos_stats s
                 INNER JOIN usuario u ON s.usuario_id = u.usuario_id",
                "s.compania_id = '$compania_id' AND u.usuario_activo = 1",
                "",
                "s.total_conversiones DESC LIMIT 3"
            );

            $data['top_referidores'] = array();
            foreach($top_referidores as $i => $top) {
                $data['top_referidores'][] = array(
                    "posicion" => $i + 1,
                    "nombre" => utf8_encode($top['usuario_nombre']),
                    "imagen" => ($top['usuario_imagen'] != '') ?
                        'https://www.gestiongo.com/admin/arch/' . $top['usuario_imagen'] : null,
                    "total_referidos" => intval($top['total_conversiones']),
                    "puntos" => floatval($top['puntos_ganados'])
                );
            }
        }

        $valores = array(
            "code" => 100,
            "message" => "Estadísticas obtenidas correctamente",
            "data" => $data
        );
    }
}

$resultado = json_encode($valores);
echo $resultado;
?>