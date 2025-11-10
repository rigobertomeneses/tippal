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

if ($metodo == "POST") {

    $conexion = new ConexionBd();
    $valoresPost = json_decode(file_get_contents('php://input'), true);

    // Obtener parámetros
    (isset($valoresPost['token'])) ? $token = $valoresPost['token'] : $token = '';
    (isset($valoresPost['compania'])) ? $compania_id = $valoresPost['compania'] : $compania_id = '';
    (isset($valoresPost['producto_id'])) ? $producto_id = $valoresPost['producto_id'] : $producto_id = '';
    (isset($valoresPost['tipo_compartir'])) ? $tipo_compartir = $valoresPost['tipo_compartir'] : $tipo_compartir = 'producto';

    if ($compania_id == "") {
        $compania_id = 0;
    }

    // Validar token y obtener usuario
    $usuario_id = "";
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
    } else if ($producto_id == "" && $tipo_compartir == "producto") {
        $valores = array(
            "code" => 102,
            "message" => "Producto ID requerido",
            "data" => [],
        );
    } else {
        // Generar código único para el link
        $codigo_unico = generarCodigoUnico($usuario_id, $producto_id);

        // Obtener información del producto si aplica
        $producto_nombre = "";
        $producto_imagen = "";
        $producto_precio = "";

        if ($producto_id != "") {
            $arrproducto = $conexion->doSelect(
                "prod_nombre, prod_imagen1, prod_precio",
                "producto",
                "prod_id = '$producto_id' and compania_id = '$compania_id'"
            );

            foreach($arrproducto as $p => $prod) {
                $producto_nombre = utf8_encode($prod["prod_nombre"]);
                $producto_imagen = $prod["prod_imagen1"];
                $producto_precio = $prod["prod_precio"];
            }
        }

        // URLs base según el tipo
        $url_base_web = "https://www.agrocomercioec.com/";
        $url_base_app = "agrocomercio://";

        // Generar diferentes tipos de links
        if ($tipo_compartir == "producto" && $producto_id != "") {
            // Link directo al producto con referido
            $link_web = $url_base_web . "p/" . $codigo_unico;
            $link_compartir_whatsapp = "https://wa.me/?text=" . urlencode(
                "🌾 Te comparto este producto de AgroComercio:\n\n" .
                "*" . $producto_nombre . "*\n" .
                "Precio: $" . $producto_precio . "\n\n" .
                "Ver más detalles: " . $link_web
            );
            $link_directo = $link_web;

        } else {
            // Link de referido general (invitación a la app)
            $link_web = $url_base_web . "ref/" . $codigo_unico;
            $link_compartir_whatsapp = "https://wa.me/?text=" . urlencode(
                "🌾 Te invito a unirte a AgroComercio\n\n" .
                "La mejor plataforma para comprar y vender productos agrícolas.\n\n" .
                "Regístrate con mi código y obtén beneficios: " . $link_web
            );
            $link_directo = $link_web;
        }

        // Registrar intento de compartir en estadísticas
        actualizarEstadisticasCompartir($conexion, $usuario_id, $compania_id);

        $valores = array(
            "code" => 100,
            "message" => "Link generado exitosamente",
            "data" => array(
                "codigo_unico" => $codigo_unico,
                "link_directo" => $link_directo,
                "link_whatsapp" => $link_compartir_whatsapp,
                "link_corto" => $link_web,
                "mensaje_compartir" => ($tipo_compartir == "producto") ?
                    "🌾 Mira este producto en AgroComercio: " . $producto_nombre . " - " . $link_web :
                    "🌾 Únete a AgroComercio con mi código: " . $link_web,
                "producto" => array(
                    "id" => $producto_id,
                    "nombre" => $producto_nombre,
                    "imagen" => ($producto_imagen != "") ? "https://www.gestiongo.com/admin/arch/" . $producto_imagen : "",
                    "precio" => $producto_precio
                ),
                "referidor" => array(
                    "id" => $usuario_id,
                    "nombre" => $usuario_nombre
                )
            )
        );
    }
}

$resultado = json_encode($valores);
echo $resultado;

// Función para generar código único
function generarCodigoUnico($usuario_id, $producto_id) {
    // Formato: AGR-USERID-PRODID-RANDOM
    $prefijo = "AGR";
    $user_part = base_convert($usuario_id, 10, 36); // Convertir a base 36 para acortar
    $prod_part = ($producto_id != "") ? base_convert($producto_id, 10, 36) : "REF";
    $random = substr(md5(uniqid()), 0, 4);

    return strtoupper($prefijo . $user_part . "-" . $prod_part . "-" . $random);
}

// Función para actualizar estadísticas
function actualizarEstadisticasCompartir($conexion, $usuario_id, $compania_id) {
    // Verificar si existe registro de estadísticas
    $existe = $conexion->doSelect(
        "stat_id",
        "usuario_referidos_stats",
        "usuario_id = '$usuario_id' AND compania_id = '$compania_id'"
    );

    if (count($existe) > 0) {
        // Actualizar contador
        $conexion->doUpdate(
            "usuario_referidos_stats",
            "total_compartidos = total_compartidos + 1",
            "usuario_id = '$usuario_id' AND compania_id = '$compania_id'"
        );
    } else {
        // Crear registro
        $conexion->doInsert(
            "usuario_referidos_stats (usuario_id, total_compartidos, compania_id)",
            "'$usuario_id', 1, '$compania_id'"
        );
    }
}
?>