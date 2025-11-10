<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');


ini_set ("display_errors","0");

include_once '../lib/mysqlclass.php';
include_once '../lib/funciones.php';
include_once '../lib/phpmailer/libemail.php';


$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];
$metodo = "POST";

if ($metodo=="POST"){// Guardar Direcciones

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['id'])) ? $get_id=$valoresPost['id'] :$get_id='';
	(isset($valoresPost['pais_id'])) ? $pais_id=$valoresPost['pais_id'] :$pais_id='0';
	(isset($valoresPost['direccion'])) ? $direccion=$valoresPost['direccion'] :$direccion='';
	(isset($valoresPost['estado'])) ? $estado=$valoresPost['estado'] :$estado='';
	(isset($valoresPost['ciudad'])) ? $ciudad=$valoresPost['ciudad'] :$ciudad='';
	(isset($valoresPost['codpostal'])) ? $codpostal=$valoresPost['codpostal'] :$codpostal='';
	(isset($valoresPost['observacion'])) ? $observacion=$valoresPost['observacion'] :$observacion='';
	(isset($valoresPost['contactonombre'])) ? $contactonombre=$valoresPost['contactonombre'] :$contactonombre='';
	(isset($valoresPost['contactotelf'])) ? $contactotelf=$valoresPost['contactotelf'] :$contactotelf='';
	(isset($valoresPost['dirmapa'])) ? $dirmapa=$valoresPost['dirmapa'] :$dirmapa='';
	(isset($valoresPost['dirlatitud'])) ? $dirlatitud=$valoresPost['dirlatitud'] :$dirlatitud='';
	(isset($valoresPost['dirlongitud'])) ? $dirlongitud=$valoresPost['dirlongitud'] :$dirlongitud='';
	(isset($valoresPost['usuario'])) ? $usuario=$valoresPost['usuario'] :$usuario='';


	if ($compania_id==""){
		$compania_id = 0;
	}

	
	$direccion = utf8_decode($direccion);
	$estado = utf8_decode($estado);
	$ciudad = utf8_decode($ciudad);
	$codpostal = utf8_decode($codpostal);
	$observacion = utf8_decode($observacion);
	$contactonombre = utf8_decode($contactonombre);
	$contactotelf = utf8_decode($contactotelf);
	$dirmapa = utf8_decode($dirmapa);


	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: direccionesregistro",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$arrresultado2 = $conexion->doSelect("usuario_id","usuario","usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: direccionesregistro",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	if ($get_id!=""){
		$resultado = $conexion->doUpdate("usuariodireccion", "
		usuariodireccion_activo ='0',
		usuariodireccion_eliminado ='1'
		",
		"usuario_id='$usuario_id' and usuariodireccion_id = '$get_id' ");
	}

	if ($usuario!=""){
		$usuario_id = $usuario;
	}

	$resultado = $conexion->doUpdate("usuariodireccion", "
		usuariodireccion_ppal ='0'
		",
		"usuario_id='$usuario_id'");
	
	$resultado = $conexion->doInsert("
	usuariodireccion
		(usuario_id, l_pais_id, usuariodireccion_direccion, usuariodireccion_estado, 
		usuariodireccion_ciudad, usuariodireccion_codpostal, usuariodireccion_observacion,
		usuariodireccion_fechareg, usuariodireccion_activo, usuariodireccion_eliminado,
		usuariodireccion_point, usuariodireccion_dirmapa, usuariodireccion_contactonombre, usuariodireccion_contactotelf, usuariodireccion_ppal)
	",
	"'$usuario_id', '$pais_id', '$direccion', '$estado', 
	'$ciudad', '$codpostal', '$observacion',
	'$fechaactual','1','0',
	POINT($dirlatitud,$dirlongitud), '$dirmapa', '$contactonombre', '$contactotelf','1'
	");


	$valores = array(
		"code" => 0,
		"message" => "Se inserto correctamente la direccion",
		"data" => []
	);


}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>