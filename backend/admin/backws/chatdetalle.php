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

if ($metodo=="POST"){// Guardar chat
	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['mensaje'])) ? $mensaje=$valoresPost['mensaje'] :$mensaje='';
	(isset($valoresPost['elemento_id'])) ? $elemento_id=$valoresPost['elemento_id'] :$elemento_id='';
	(isset($valoresPost['usuariodestino'])) ? $usuariodestino=$valoresPost['usuariodestino'] :$usuariodestino='0';
	(isset($valoresPost['tipocarga'])) ? $tipocarga=$valoresPost['tipocarga'] :$tipocarga='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['chatmsje_idultimo'])) ? $get_chatmsje_idultimo=$valoresPost['chatmsje_idultimo'] :$get_chatmsje_idultimo='';

	if ($compania_id==""){
		$compania_id = 0;
	}

	$mensaje = utf8_decode($mensaje);

	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: chatdetalle",
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
			"message" => "Usuario / Token no activo. url: chatdetalle",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

	if ($get_chatmsje_idultimo!=""){
		$where .= " and chatmsje.chatmsje_id > '$get_chatmsje_idultimo' ";
	}

	$arrresultado = $conexion->doSelect("
		chatmsje.chatmsje_id, chatmsje.chat_id, chatmsje_titulo, chatmsje_arch, chatmsje_archorig, 
		chatmsje_texto, chatmsje.usuario_idorigen, chatmsje.usuario_iddestino, 
		chatmsje_leido, chatmsje_activo, chatmsje_eliminado,
		DATE_FORMAT(chatmsje_fechareg,'$formatofechaSQL %H:%i:%s') as chatmsje_fechareg,
		chat_tipo,
		
		chatmsje.l_tipoarchivo_id,
		usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_img, 
		usuario.usuario_id,

		usuariodestino.usuario_nombre as usuariodestino_nombre, 
		usuariodestino.usuario_apellido as usuariodestino_apellido, 
		usuariodestino.usuario_img as usuariodestino_img,
		usuariodestino.usuario_id as usuariodestino_id

		",
		"chatmsje
			inner join chat on chat.chat_id = chatmsje.chat_id
			left join usuario on chatmsje.usuario_idorigen = usuario.usuario_id		    
			left join usuario usuariodestino on usuariodestino.usuario_id = chat.usuario_iddestino		    
		",
		"chatmsje_activo = '1' and chat.elemento_id = '$elemento_id' and chat.compania_id = '$compania_id' $where", null, "chatmsje_id desc");

		// and chatmsje.chat_id = '$chat_id'
	$total = count($arrresultado);	
	
	$chatmsje_idultimo = "";

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$chatmsje_id = utf8_encode($valor["chatmsje_id"]);

			if ($chatmsje_idultimo==""){
				$chatmsje_idultimo = $chatmsje_id;
			}

			$usuario_idorigen = utf8_encode($valor["usuario_idorigen"]);
			$usuario_iddestino = utf8_encode($valor["usuario_iddestino"]);

			if ($usuario_id==$usuario_iddestino){
				$resultado = $conexion->doUpdate("chatmsje", 
				"chatmsje_leido = '1'", 
				"chatmsje.chatmsje_id = '$chatmsje_id'");
			}
			
			$chat_id = utf8_encode($valor["chat_id"]);
			$chat_tipo = utf8_encode($valor["chat_tipo"]);
			
			$chatmsje_titulo = utf8_encode($valor["chatmsje_titulo"]);
			$chatmsje_arch = utf8_encode($valor["chatmsje_arch"]);
			$chatmsje_archorig = utf8_encode($valor["chatmsje_archorig"]);
			$chatmsje_texto = utf8_encode($valor["chatmsje_texto"]);
			
			$chatmsje_leido = utf8_encode($valor["chatmsje_leido"]);
			$chatmsje_activo = utf8_encode($valor["chatmsje_activo"]);
			$chatmsje_fechareg = utf8_encode($valor["chatmsje_fechareg"]);
			$l_tipoarchivo_id = utf8_encode($valor["l_tipoarchivo_id"]);

			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_img = utf8_encode($valor["usuario_img"]);
			$usuarioid = utf8_encode($valor["usuario_id"]);

			$usuario = $usuario_nombre." ".$usuario_apellido." ";	

			$usuariodestino_nombre = utf8_encode($valor["usuariodestino_nombre"]);
			$usuariodestino_apellido = utf8_encode($valor["usuariodestino_apellido"]);
			$usuariodestino_img = utf8_encode($valor["usuariodestino_img"]);
			$usuariodestino_id = utf8_encode($valor["usuariodestino_id"]);

			$usuariodestino = $usuariodestino_nombre." ".$usuariodestino_apellido." ";	

			$agregarchivoadjunto = "";
			$labeladjunto = "";

			$imagen = ObtenerUrlArch($compania_id)."/$usuario_img";
			$imagendestino = ObtenerUrlArch($compania_id)."/$usuariodestino_img";
			
			$data = array(
				"id" => $chatmsje_id,
				"mensaje" => $chatmsje_texto,
				"usuarioid" => $usuario_idorigen,
				"usuario" => $usuario,
				"usuarioimg" => $imagen,
				"usuariodestino" => $usuariodestino,
				"usuariodestinoimg" => $imagendestino,
				"fecha" => $chatmsje_fechareg,
				"leido" => $chatmsje_leido
			);

			array_push($datatotal, $data);

		}

		
		$dataInfo = array(				
			"usuarioorigen" => $usuario,
			"usuarioorigenid" => $usuarioid,
			"usuarioorigenimg" => $imagen,
			"usuariodestino" => $usuariodestino,
			"usuariodestinoid" => $usuariodestino_id,
			"usuariodestinoimg" => $imagendestino,
			"chat_id" => $chat_id,
			"chatmsje_idultimo" => $chatmsje_idultimo,
			"where" => $where,
			"chat_tipo" => $chat_tipo
		);

		


		$dataReturn = array(				
			"items" => $datatotal,
			"info" => $dataInfo
		);

		$valores = array(
			"code" => 0,
			"data" => $dataReturn,
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