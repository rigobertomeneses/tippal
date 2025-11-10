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

if ($metodo=="POST"){// Consultar usuario calificaciones

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['id'])) ? $getusuariodestino_id=$valoresPost['id'] :$getusuariodestino_id='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}

	
	/*
		if ($token==""){
			$valores = array(
				"code" => 104,
				"message" => "Token no encontrado. url: usuarioinfo",
				"data" => [],
			);

			$resultado = json_encode($valores);
			echo $resultado;
			exit();
		}
	*/

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();
	
	$arrresultado2 = $conexion->doSelect("usuario_id","usuario","usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
	}

	if ($getusuariodestino_id==""){
		$getusuariodestino_id = $usuario_id;
	}
/*
	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuarioinfo",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}
	*/

	//$getusuariodestino_id = 6076;


	$whereusuario = " AND usuario.usuario_id = '$getusuariodestino_id' ";

	if ($getalias!=""){
		$whereusuario = " AND usuario.usuario_alias = '$getalias' ";
	}

	$arrresultado = $conexion->doSelect("usuario_id, usuario_nombre, usuario_apellido
	",
	"usuario",
	"usuario.usuario_activo = '1' and usuario_id = '$getusuariodestino_id'");

	foreach($arrresultado as $i=>$valor){
		$usuario_id = utf8_encode($valor["usuario_id"]);
		$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
		$usuario_apellido = utf8_encode($valor["usuario_apellido"]);

		$usuario = $usuario_nombre." ".$usuario_apellido;
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 110,
			"message" => "Usuario no existe",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$formatofechaSQL = formatofechaSQL($compania_id);


	$arrresultado = $conexion->doSelect("
	distinct 
		usuariorating.usuario_idorigen, usuariorating.usuario_iddestino, 
		usuariorating.usuariorating_valor, usuariorating.usuariorating_comentario,
		usuariorating.usuariorating_activo, 
		usuariorating.usuariorating_eliminado, usuariorating.l_estatus_id, 
		usuariorating.cuenta_id, usuariorating.compania_id,
		DATE_FORMAT(usuariorating_fechareg,'$formatofechaSQL %H:%i:%s') as usuariorating_fechareg,
		proyecto.usuario_id as empleador_id,
		proyecto.proy_nombre,
		usuarioorigen.usuario_nombre as usuarioorigen_nombre,
		usuarioorigen.usuario_apellido as usuarioorigen_apellido

	    ",
		"usuario	
			inner join usuariorating on usuariorating.usuario_iddestino = usuario.usuario_id	
			inner join usuarioratingservicio on usuarioratingservicio.usuariorating_id = usuariorating.usuariorating_id
			inner join usuario usuarioorigen on usuarioorigen.usuario_id = usuariorating.usuario_idorigen
			left join proyecto on proyecto.proy_id = usuarioratingservicio.solicitudserv_id


		",
		"usuario.usuario_activo = '1' and usuariorating_activo = '1'  $whereusuario ");

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){
			$usuariorating_id = utf8_encode($valor["usuariorating_id"]);
			$usuariorating_valor = utf8_encode($valor["usuariorating_valor"]);
			$usuariorating_comentario = utf8_encode($valor["usuariorating_comentario"]);
			$usuariorating_fechareg = utf8_encode($valor["usuariorating_fechareg"]);
			$empleador_id = utf8_encode($valor["empleador_id"]);
			$proy_nombre = utf8_encode($valor["proy_nombre"]);

			$usuarioorigen_nombre = utf8_encode($valor["usuarioorigen_nombre"]);
			$usuarioorigen_apellido = utf8_encode($valor["usuarioorigen_apellido"]);

			$usuarioorigen = $usuarioorigen_nombre." ".$usuarioorigen_apellido;

			$tipo = "trabajador";
			if ($empleador_id==$usuario_id){
				$tipo = "empleador";
			}
			
			$datacaificacion = array(
				"id" => uniqid(),
				"usuarioorigen" => $usuarioorigen,
				"valor" => $usuariorating_valor,
				"comentario" => $usuariorating_comentario,
				"fecha" => $usuariorating_fechareg,
				"tipo" => $tipo,
				"proy_nombre" => $proy_nombre
			);

			array_push($datatotal, $datacaificacion);
		}

		$data = array(
			"usuario" => $usuario_id,
			"calificaciones" => $datatotal
		);

		$valores = array(
			"code" => 0,
			"data" => $data,
			"message" => "Consulta Correcta"
		);	
	
}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>