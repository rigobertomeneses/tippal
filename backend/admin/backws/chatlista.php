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

if ($metodo=="POST"){// Lista de chat
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
			"message" => "Token no encontrado. url: chatlista",
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
			"message" => "Usuario / Token no activo. url: chatlista",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

	if ($compania_id=="459" || $compania_id=="466"){

		$innerjoin = "
			inner join transacciontaxinotificacion on transacciontaxinotificacion.transtaxinotif_id = chat.elemento_id
			inner join lista on lista.lista_id = transacciontaxinotificacion.l_estatus_id
			and lista.lista_cod in (1,2,5,8)
		
		";

		// 			

	}

	$arrresultado = $conexion->doSelect("
		chat.chat_id, chat_titulo, chat_ultmsje, chat_tipo,
		usuario_idorigen, usuario_iddestino, chat_leido, 
		chat.modulo_id, chat.elemento_id, chat.cuenta_id, chat.compania_id, 
		chat_activo, chat_eliminado, 
		chat.usuario_idcliente, chat.usuario_idpropietario, 
		chat.chat_msjes, chat.chat_leidoorigen, chat.chat_leidodestino,

		DATE_FORMAT(chat.chat_fechareg,'$formatofechaSQL %H:%i:%s') as chat_fechareg,
		DATE_FORMAT(chat.chat_ultfecha,'$formatofechaSQL %H:%i:%s') as chat_ultfecha,
				
		usuarioorigen.usuario_nombre as usuarioorigen_nombre, 
		usuarioorigen.usuario_apellido as usuarioorigen_apellido, 
		usuarioorigen.usuario_img as usuarioorigen_img,

		usuariodestino.usuario_nombre as usuariodestino_nombre, 
		usuariodestino.usuario_apellido as usuariodestino_apellido, 
		usuariodestino.usuario_img as usuariodestino_img 
		",
		"chat
			inner join usuario usuarioorigen on usuarioorigen.usuario_id = chat.usuario_idorigen
			inner join usuario usuariodestino on usuariodestino.usuario_id = chat.usuario_iddestino

			$innerjoin

		",
		"chat_activo = '1' and chat.compania_id = '$compania_id' and (chat.usuario_idorigen = '$usuario_id' or chat.usuario_iddestino = '$usuario_id' ) ", null, "chat_id desc");

	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){
	
			$chat_id = utf8_encode($valor["chat_id"]);
			$chat_titulo = utf8_encode($valor["chat_titulo"]);
			$chat_ultmsje = utf8_encode($valor["chat_ultmsje"]);
			$usuario_idorigen = utf8_encode($valor["usuario_idorigen"]);
			$usuario_iddestino = utf8_encode($valor["usuario_iddestino"]);
			$chat_leido = utf8_encode($valor["chat_leido"]);
			$modulo_id = utf8_encode($valor["modulo_id"]);
			$elemento_id = utf8_encode($valor["elemento_id"]);
			
			$cuenta_id = utf8_encode($valor["cuenta_id"]);
			$compania_id = utf8_encode($valor["compania_id"]);
			$chat_activo = utf8_encode($valor["chat_activo"]);
			$usuario_idcliente = utf8_encode($valor["usuario_idcliente"]);
			$usuario_idpropietario = utf8_encode($valor["usuario_idpropietario"]);
			$chat_msjes = utf8_encode($valor["chat_msjes"]);
			$chat_leidoorigen = utf8_encode($valor["chat_leidoorigen"]);
			$chat_leidodestino = utf8_encode($valor["chat_leidodestino"]);
			$chat_fechareg = utf8_encode($valor["chat_fechareg"]);
			$chat_ultfecha = utf8_encode($valor["chat_ultfecha"]);
			$chat_tipo = utf8_encode($valor["chat_tipo"]);			

			$usuarioorigen_nombre = utf8_encode($valor["usuarioorigen_nombre"]);
			$usuarioorigen_apellido = utf8_encode($valor["usuarioorigen_apellido"]);
			$usuarioorigen_img = utf8_encode($valor["usuarioorigen_img"]);
			$usuarioorigen = $usuarioorigen_nombre." ".$usuarioorigen_apellido." ";	

			$usuariodestino_nombre = utf8_encode($valor["usuariodestino_nombre"]);
			$usuariodestino_apellido = utf8_encode($valor["usuariodestino_apellido"]);
			$usuariodestino_img = utf8_encode($valor["usuariodestino_img"]);		
			$usuariodestino = $usuariodestino_nombre." ".$usuariodestino_apellido." ";	

			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_img = utf8_encode($valor["usuario_img"]);

			$usuarioorigen_img = ObtenerUrlArch($compania_id)."/$usuarioorigen_img";
			$usuariodestino_img = ObtenerUrlArch($compania_id)."/$usuariodestino_img";

			if ($usuario_id==$usuario_iddestino){
				$usuariodestino_colocar = $usuarioorigen;
				$usuariodestino_img_colocar = $usuarioorigen_img;
				$usuariodestino_id_colocar = $usuario_idorigen;

				$usuarioorigen_colocar = $usuariodestino;
				$usuarioorigen_img_colocar = $usuariodestino_img;
				$usuarioorigen_id_colocar = $usuario_iddestino;

			}else{

				$usuariodestino_colocar = $usuariodestino;
				$usuariodestino_img_colocar = $usuariodestino_img;
				$usuariodestino_id_colocar = $usuario_iddestino;

				$usuarioorigen_colocar = $usuarioorigen;
				$usuarioorigen_img_colocar = $usuarioorigen_img;
				$usuarioorigen_id_colocar = $usuario_idorigen;
			}
			
			$data = array(
				"id" => $chat_id,
				"elemento_id" => $elemento_id,
				"chat_titulo" => $chat_titulo,
				"chat_ultmsje" => $chat_ultmsje,
				"chat_leido" => $chat_leido,
				"chat_ultfecha" => $chat_ultfecha,
				"chat_tipo" => $chat_tipo,
				
				"usuarioorigen" => $usuarioorigen_colocar,
				"usuarioorigen_id" => $usuarioorigen_id_colocar,
				"usuarioorigen_img" => $usuarioorigen_img_colocar,

				"usuariodestino" => $usuariodestino_colocar,
				"usuariodestino_id" => $usuariodestino_id_colocar,
				"usuariodestino_img" => $usuariodestino_img_colocar,

				"chat_msjes" => $chat_msjes,
				"chat_leido" => $chat_leido
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