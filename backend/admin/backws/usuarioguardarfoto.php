<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set ("display_errors","0");

include_once '../lib/mysqlclass.php';
include_once '../lib/funciones.php';
include_once '../lib/phpmailer/libemail.php';
include_once '../models/lista.php';

$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];
$metodo = "POST";

if ($metodo=="POST"){// Guardar foto del usuario

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['archivo'])) ? $archivo=$valoresPost['archivo'] :$archivo='';
	(isset($valoresPost['usuarioid'])) ? $usuarioid=$valoresPost['usuarioid'] :$usuarioid='';
	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['tipo'])) ? $tipo=$valoresPost['tipo'] :$tipo='perfil'; // 'perfil' o 'portada'


	if ($compania_id==""){
		$compania_id = 381;
	}

	
 	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: usuarioguardarfoto",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}


	$arrresultado2 = $conexion->doSelect("usuario_id, cuenta_id, perfil_id",
	"usuario",
	"usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);		
		$perfil_id = utf8_encode($valor2["perfil_id"]);						
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuarioguardarfoto",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$primerCaracterBase64 = substr($archivo, 0, 1); 
	$extensionArchivo = "jpg";
	if ($primerCaracterBase64=="/"){
		$extensionArchivo = "jpg";
	}else if ($primerCaracterBase64=="i"){
		$extensionArchivo = "png";
	}else if ($primerCaracterBase64=="J"){
		$extensionArchivo = "pdf";
	}
	
	$tipoarchivo = obtenerExtensionArchivo($extensionArchivo);

	$nombrecolocar = "";
	if ($archivo!=""){
		$nombrecolocar = uniqid().".$extensionArchivo";
		$status = file_put_contents("../arch/$nombrecolocar",base64_decode($archivo));

		// Determinar qué campo actualizar según el tipo
		$campoActualizar = "";
		if ($tipo == 'portada' || $tipo == 'cover') {
			$campoActualizar = "usuario_portada";
		} else {
			// Por defecto es imagen de perfil
			$campoActualizar = "usuario_img";
		}

		$resultado = $conexion->doUpdate("usuario", "
		$campoActualizar ='$nombrecolocar'
		",
		"usuario_id='$usuarioid'");

	}	

	if($resultado){

		// Obtener la URL completa de la imagen
		$urlArchivo = "";
		if ($nombrecolocar != "") {
			// Obtener la URL base del servidor
			$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
			$servidor = $_SERVER['HTTP_HOST'];
			$urlArchivo = $protocolo . "://" . $servidor . "/admin/arch/" . $nombrecolocar;
		}

		$valores = array(
			"code" => 0,
			"data" => [
				"campo" => $campoActualizar,
				"archivo" => $nombrecolocar,
				"url" => $urlArchivo,
				"tipo" => $tipo
			],
			"nombrecolocar" => $nombrecolocar,
			"message" => ($tipo == 'portada' ? "Imagen de portada" : "Foto de perfil") . " guardada correctamente"
		);

	}else{

		$valores = array(
			"code" => 100,
			"message" => "Error al guardar la imagen",
			"data" => null,
		);

	}

}


$resultado = json_encode($valores);

echo $resultado;

exit();

?>