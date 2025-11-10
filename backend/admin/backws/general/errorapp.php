<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');


ini_set ("display_errors","0");

include_once '../../lib/mysqlclass.php';
include_once '../../lib/phpmailer/libemail.php';
include_once '../../lib/funciones.php';

$libemail = new LibEmail();  

$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo=="POST"){// Registrar error

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	(isset($valoresPost['mensaje'])) ? $mensaje=$valoresPost['mensaje'] :$mensaje='';
	(isset($valoresPost['version'])) ? $version=$valoresPost['version'] :$version='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}

	$asunto = "Error en App: $compania_id";
	$mensaje = "Compania: $compania_id - Version: $version ".$mensaje;

	$resultado = $libemail->enviarcorreo("meneses.rigoberto@gmail.com", $asunto, $mensaje, $compania_id);
	$valores = array(
		"code" => 0,
		"data" => null,
		"message" => "Envio Correcto"
	);	

}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>