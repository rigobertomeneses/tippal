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

if ($metodo=="POST"){// Guardar Opciones de Retiro del Usuario

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['formapago'])) ? $formapago=$valoresPost['formapago'] :$formapago='';
	(isset($valoresPost['cuenta'])) ? $cuenta=$valoresPost['cuenta'] :$cuenta='';
	(isset($valoresPost['titular'])) ? $titular=$valoresPost['titular'] :$titular='';
	(isset($valoresPost['email'])) ? $email=$valoresPost['email'] :$email='';
	(isset($valoresPost['documento'])) ? $documento=$valoresPost['documento'] :$documento='';
	(isset($valoresPost['banco'])) ? $banco=$valoresPost['banco'] :$banco='';
	(isset($valoresPost['bancoid'])) ? $bancoid=$valoresPost['bancoid'] :$bancoid='';
	(isset($valoresPost['tipocuenta'])) ? $tipocuenta=$valoresPost['tipocuenta'] :$tipocuenta='';
	(isset($valoresPost['tipocuentaid'])) ? $tipocuentaid=$valoresPost['tipocuentaid'] :$tipocuentaid='';
	(isset($valoresPost['observaciones'])) ? $observaciones=$valoresPost['observaciones'] :$observaciones='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}

	$formapago = utf8_decode($formapago);
	$cuenta = utf8_decode($cuenta);
	$titular = utf8_decode($titular);
	$email = utf8_decode($email);
	$documento = utf8_decode($documento);
	$banco = utf8_decode($banco);
	$tipocuenta = utf8_decode($tipocuenta);
	$observaciones = utf8_decode($observaciones);

	if ($formapago==""){$formapago=0;}
	if ($bancoid==""){$bancoid=0;}
	if ($tipocuentaid==""){$tipocuentaid=0;}
	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: usuarioretiroopcionguardar",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$arrresultado2 = $conexion->doSelect("usuario_id","usuario","usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuarioretiroopcionguardar",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}


	$resultado = $conexion->doUpdate("usuarioretiro", "
	usuarioretiro_activo = '0',
	usuarioretiro_eliminado = '1'	
	", "usuario_id='$usuario_id' and usuarioretiro_eliminado = '0' and l_formapago_id = '$formapago' ");	


	$resultado = $conexion->doInsert("
	usuarioretiro
		(usuarioretiro_descripcion, usuarioretiro_email, usuarioretiro_fechareg, 
		usuarioretiro_activo, usuarioretiro_eliminado, usuario_id, l_formapago_id, 
		usuarioretiro_banco, usuarioretiro_titular, usuarioretiro_tipocuenta, usuarioretiro_documento, 
		usuarioretiro_nrocuenta, l_banco_id, l_tipocuenta_id)
	",
	"'$observaciones', '$email', '$fechaactual',
	'1', '0', '$usuario_id', '$formapago',
	'$banco', '$titular','$tipocuenta','$documento',
	'$cuenta', '$bancoid', '$tipocuentaid'");

	$total = 1;		

	if($total>0){		

		$valores = array(
			"code" => 0,
			"data" => [],
			"message" => "Registro Correcto"
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