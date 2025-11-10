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
include('../vendor/autoload.php');

$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo=="POST"){// consultar pin
	
	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
    (isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['codigo'])) ? $codigo=$valoresPost['codigo'] :$codigo='';
	if ($compania_id==""){
		$compania_id = 0;
	}

	$verificado = false;

    $conexion = new ConexionBd();

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

    $arrresultado2 = $conexion->doSelect("usuario_id, usuario_codpass","usuario","usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);
		$usuario_codpass = utf8_encode($valor2["usuario_codpass"]);
	}

	if($usuario_id==""){

		$valores = array(
			"code" => 101,
			"message" => "Error: El usuario no esta conectado"
		);

	}else{

		if ($usuario_codpass==$codigo){
			$verificado = true;

			$valores = array(
				"code" => 0,
				"data" => $verificado,
				"message" => "Verificado el pin correctamente"
			);	
			
		}else{
			$valores = array(
				"code" => 106,
				"data" => null,
				"message" => "Pin invalido"
			);
		}
	}
}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>