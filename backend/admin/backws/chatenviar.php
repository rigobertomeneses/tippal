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
include_once '../models/chat.php';
require_once '../vendor/autoload.php';


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
	(isset($valoresPost['usuariodestino'])) ? $usuariodestino=$valoresPost['usuariodestino'] :$usuariodestino='0';
	//(isset($valoresPost['id'])) ? $chat_id=$valoresPost['id'] :$chat_id='';
	(isset($valoresPost['moduloid'])) ? $moduloid=$valoresPost['moduloid'] :$moduloid='';	
	(isset($valoresPost['elemento_id'])) ? $elemento_id=$valoresPost['elemento_id'] :$elemento_id='';	
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['tipo'])) ? $tipo=$valoresPost['tipo'] :$tipo='';
	
	if ($compania_id==""){
		$compania_id = 0;
	}

	$mensaje = utf8_decode($mensaje);

	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: chatenviar",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$arrresultado2 = $conexion->doSelect("usuario_id, cuenta_id, usuario_nombre, usuario_apellido",
	"usuario",
	"usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);	
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);
		$usuario_nombreenvia = utf8_encode($valor2["usuario_nombre"]);
		$usuario_apellidoenvia = utf8_encode($valor2["usuario_apellido"]);
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: chatenviar",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$chat = new Chat();

	//$modulo_id = 8888;

	if ($moduloid==""){$moduloid=0;}

	if ($moduloid!=""){
		$modulo_id = $moduloid;
		//$usuariodestino = 0;
	}

	if ($moduloid=="987"){ // Chat de torneo
		$modulo_id = $moduloid;
		//$usuariodestino = 0;
	}

	$arrresultado2 = $conexion->doSelect("elemento_id, chat_id",
	"chat",
	"elemento_id = '$elemento_id' and compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$elemento_id = utf8_encode($valor2["elemento_id"]);
		$chat_id = utf8_encode($valor2["chat_id"]);	
	}

	if ($tipo=="propuestaproyecto"){
		$moduloid = "1000"; // Propuesta

		// Cambio de estatus a en conversacion, si esta en evaluando propuesta
		$arrresultado = $conexion->doSelect("estatus.lista_cod",
		"propuesta_proyecto
			inner join lista estatus on estatus.lista_id = propuesta_proyecto.estatus_id
		",
		"propuesta_proyecto.propuestaproy_id  = '$elemento_id'");
		foreach($arrresultado as $i=>$valor){
			$lista_cod = utf8_encode($valor["lista_cod"]);

			if ($lista_cod=="1"){

				$resultado = $conexion->doUpdate("propuesta_proyecto", "
				estatus_id = '2460'
				",
				"propuestaproy_id='$elemento_id'");
			}

		}

	}

	$resultadoChatId = $chat->InsertarChat($chat_id, $mensaje, $usuario_id, $usuariodestino, $moduloid, $elemento_id, $cuenta_id, $compania_id, $tipo);
	

	$instancialista = new Lista();

	$obtenerCodigoLista = 2;
	$obtenerTipoLista = 158;
	$estatusenviado = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

	// Notificaciones
	if ($moduloid=="987"){ // Chat de torneo

		// Consulto los del torneo
		$arrresultado = $conexion->doSelect("usuario.usuario_id, 
		usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_notas as usuario_pushtoken, 
		usuario_notificaremail
		",
		"usuario
			inner join eventousuario on eventousuario.usuario_id = usuario.usuario_id
		",
		"eventousuario.eventocalendario_id = '$elemento_id' and usuario.usuario_activo = '1' and usuario.compania_id = '$compania_id' and usuario.usuario_id <> '$usuario_id' ");

		foreach($arrresultado as $i=>$valor){
			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_pushtoken = utf8_encode($valor["usuario_pushtoken"]);
			$usuario_iddestino = utf8_encode($valor["usuario_id"]);
			$usuario_notificaremail = utf8_encode($valor["usuario_notificaremail"]);

			if ($usuario_notificaremail=="0"){
				$usuario_email = "";
			}

			$correomasivo_id = 0;
			$tipo = "torneo";
			$correomasivocampana_titulo = "Nuevo mensaje de torneo de $usuario_nombreenvia $usuario_apellidoenvia";
			$correomasivocampana_descrippush = "";			
			$correomasivocampana_descripemail = "Nuevo mensaje de torneo de $usuario_nombreenvia $usuario_apellidoenvia";
			
			enviarNotificacionFunciones($correomasivo_id, $usuario_nombre, $usuario_apellido, $usuario_email, $usuario_iddestino, null, $fechaactual, $correomasivocampana_descrippush, $correomasivocampana_titulo, $usuario_pushtoken, $cuenta_id, $compania_id, $chat_id, $tipo, $correomasivocampana_descripemail);	

		}		

	}
	else if ($usuariodestino!="0"){
	
		// Enviar notificacion al usuariodestino
		$arrresultado = $conexion->doSelect("usuario.usuario_id, 
		usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_notas as usuario_pushtoken,
		usuario_notificaremail
		",
		"usuario",
		"usuario.usuario_activo = '1' and usuario.compania_id = '$compania_id' and usuario.usuario_id = '$usuariodestino' ");

		foreach($arrresultado as $i=>$valor){
			$usuario_iddestino = utf8_encode($valor["usuario_id"]);
			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_pushtoken = utf8_encode($valor["usuario_pushtoken"]);
			$usuario_notificaremail = utf8_encode($valor["usuario_notificaremail"]);

			if ($usuario_notificaremail=="0"){
				$usuario_email = "";
			}

			$correomasivo_id = 0;
			$tipo = "nuevomensaje";
			$correomasivocampana_titulo = "Nuevo mensaje de $usuario_nombreenvia $usuario_apellidoenvia";
			$correomasivocampana_descrippush = "Ha recibido un nuevo mensaje";			
			$correomasivocampana_descripemail = "Nuevo mensaje de $usuario_nombreenvia $usuario_apellidoenvia";
			
			enviarNotificacionFunciones($correomasivo_id, $usuario_nombre, $usuario_apellido, $usuario_email, $usuario_iddestino, null, $fechaactual, $correomasivocampana_descrippush, $correomasivocampana_titulo, $usuario_pushtoken, $cuenta_id, $compania_id, $elemento_id, $tipo, $correomasivocampana_descripemail);	

		}

	}

	$arrresultado2 = $conexion->doSelect("usuario.usuario_id, usuario_notas, perfil.perfil_idorig, 
	usuario_nombre, usuario_apellido
	",
	"usuario
		inner join perfil on perfil.perfil_id = usuario.perfil_id 
	", 
	"usuario.compania_id = '$compania_id' and perfil.perfil_idorig = '3' and usuario_activo = '1' ");

	if (count($arrresultado2)>0){
		foreach($arrresultado2 as $i=>$valor){
			$usuario_id = ($valor["usuario_id"]);
			$usuario_pushtoken = ($valor["usuario_notas"]);
			$usuario_nombre = ($valor["usuario_nombre"]);
			$usuario_apellido = ($valor["usuario_apellido"]);
		
			// Notificacion PUSH
			if ($usuario_pushtoken!=""){
		
				$correomasivo_id = 0;						

				$tipo = "nuevomensaje";
				$correomasivocampana_titulo = "Nuevo mensaje de $usuario_nombreenvia $usuario_apellidoenvia";
				$correomasivocampana_descrippush = "";			
				$correomasivocampana_descripemail = "Nuevo mensaje de $usuario_nombreenvia $usuario_apellidoenvia";
												
				enviarNotificacionFunciones($correomasivo_id, $usuario_nombre, $usuario_apellido, null, $usuario_id, null, $fechaactual, $correomasivocampana_descrippush, $correomasivocampana_titulo, $usuario_pushtoken, $cuenta_id, $compania_id, $elemento_id, $tipo, $correomasivocampana_descripemail, $usuario_id);					
		
			}
		}
	}

		
	if($total==""){

		$valores = array(
			"code" => 0,
			"data" => [],
			"usuariodestino" => $usuariodestino,
			"chat_id" => $chat_id,
			"elemento_id" => $elemento_id,
			

			
			
			"message" => "Mensaje registrado correctamente"
		);

	}else{


		$valores = array(
			"code" => 100,
			"message" => "Sin registros no se registro el mensaje",
			"usuariodestino" => $usuariodestino,
			"chat_id" => $chat_id,
			"elemento_id" => $elemento_id,
			"data" => null,
		);

	}

}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>