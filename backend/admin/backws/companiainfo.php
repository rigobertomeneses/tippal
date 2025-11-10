<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');


ini_set ("display_errors","0");

include_once '../lib/funciones.php';
include_once '../lib/mysqlclass.php';
include_once '../lib/phpmailer/libemail.php';

$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo=="POST"){// Consultar Info de compañia

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';	
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}
	
	$conexion = new ConexionBd();
	
	$arrresultado = $conexion->doSelect("compania_id, compania_nombre, compania_telf, compania_whatsapp, compania_email, compania_url, compania_img 
	    ",
		"compania",
		"compania_id = '$compania_id' ");

	if(count($arrresultado)>0){


		foreach($arrresultado as $i=>$valor){

			$compania_id = utf8_encode($valor["compania_id"]);
			$compania_nombre = utf8_encode($valor["compania_nombre"]);
			$compania_telf = utf8_encode($valor["compania_telf"]);
			$compania_whatsapp = utf8_encode($valor["compania_whatsapp"]);
			$compania_email = utf8_encode($valor["compania_email"]);
			$compania_url = utf8_encode($valor["compania_url"]);
			$compania_urlmostrar = $compania_url;

			$compania_urlmostrar = str_replace("https://", "", $compania_urlmostrar);

			$compania_img = utf8_encode($valor["compania_img"]);
			
			$imagen = ObtenerUrlArch($compania_id)."/$compania_img";

		}


		$data = array(
			"compania_id" => $compania_id,			
			"compania_nombre" => $compania_nombre,
			"compania_telf" => $compania_telf,
			"compania_whatsapp" => $compania_whatsapp,
			"compania_whatsappmostrar" => $compania_whatsapp,
			"compania_email" => $compania_email,
			"compania_url" => $compania_url,
			"compania_urlmostrar" => $compania_urlmostrar,
			"compania_img" => $imagen
		);

		$valores = array(
			"code" => 0,
			"data" => $data,
			"message" => "Consulta Correcta"
		);
	}else{

		$valores = array(
			"code" => 100,
			"message" => "Sin registros",
			"data" => null,
		);

	}

	
}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>