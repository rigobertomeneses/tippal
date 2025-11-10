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

if ($metodo=="POST"){// Consultar Referidos del Usuario

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}
	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: usuarioreferidos",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$arrresultado2 = $conexion->doSelect("usuario_id, cuenta_id","usuario","usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuarioreferidos",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

	$arrresultado = $conexion->doSelect("
		usuario.usuario_id, usuario_nombre, usuario_apellido, usuario_email, usuario_whatsapp,
		DATE_FORMAT(usuario_fechareg,'$formatofechaSQL %H:%i:%s') as usuario_fechareg,
		estatus.lista_nombre as estatus_nombre,
		perfil.perfil_nombre
		",
		"usuario						
			inner join lista estatus on estatus.lista_id = usuario.l_estatus_id		
		    inner join perfil on perfil.perfil_id = usuario.perfil_id		
		",
		"usuario_activo = '1' and usuario.compania_id = '$compania_id' and usuario.usuario_idreferido = '$usuario_id'", null, "usuario.usuario_id desc");
			
	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$usuario_id = utf8_encode($valor["usuario_id"]);
			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_email = utf8_encode($valor["usuario_email"]);
			$usuario_whatsapp = utf8_encode($valor["usuario_whatsapp"]);			
			$usuario_fechareg = utf8_encode($valor["usuario_fechareg"]);			
			$estatus_nombre = utf8_encode($valor["estatus_nombre"]);
			$perfil_nombre = utf8_encode($valor["perfil_nombre"]);			

			$data = array(				
				"id" => $usuario_id,
				"nombre" => $usuario_nombre,
				"apellido" => $usuario_apellido,
				"email" => $usuario_email,
				"whatsapp" => $usuario_whatsapp,
				"fecharegistro" => $usuario_fechareg,
				"estatus" => $estatus_nombre,
				"perfil" => $perfil_nombre
			);
	
			array_push($datatotal, $data);

		}


		$valores = array(
			"code" => 0,
			"data" => $datatotal,
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