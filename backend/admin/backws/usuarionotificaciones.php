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

if ($metodo=="POST"){// Consultar Notificaciones del Usuario

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
			"message" => "Token no encontrado. url: usuarionotificaciones",
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
			"message" => "Usuario / Token no activo. url: usuarionotificaciones",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

	$arrresultado = $conexion->doSelect("
		correomasivodetalle.correomasivodet_id, correomasivodetalle.correomasivo_id, 
		correomasivodetalle.correomasivodet_usuarionombre, 
		correomasivodetalle.correomasivodet_usuarioemail, 
		correomasivodetalle.usuario_id, 
		correomasivodetalle.l_estatus_id, 
		correomasivodetalle.correomasivodet_activo, correomasivodetalle.correomasivodet_eliminado, 
		DATE_FORMAT(correomasivodetalle.correomasivodet_fechareg,'$formatofechaSQL %H:%i:%s') as correomasivodet_fechareg,		
		correomasivodetalle.correomasivodet_usuariopushresponse, 
		correomasivodetalle.correomasivodet_usuariopush, 
		correomasivodetalle.correomasivodet_titulo, 
		correomasivodetalle.correomasivodet_descrip,		
		usuario.usuario_id, usuario_nombre, usuario_apellido, usuario_email, usuario_whatsapp,
		DATE_FORMAT(usuario_fechareg,'$formatofechaSQL %H:%i:%s') as usuario_fechareg,
		estatus.lista_nombre as estatus_nombre,
		estatus.lista_cod as estatus_cod,
		correomasivocampana.correomasivocampana_id,
		correomasivocampana.correomasivocampana_titulo,
		correomasivocampana.correomasivocampana_descripcorta,
		correomasivodetalle.elemento_id,
		correomasivodetalle.usuario_iddestino,
		correomasivo_codigo
		",
		"correomasivo						
			inner join correomasivodetalle on correomasivodetalle.correomasivo_id = correomasivo.correomasivo_id		
			inner join lista estatus on estatus.lista_id = correomasivodetalle.l_estatus_id		
		    inner join usuario on usuario.usuario_id = correomasivodetalle.usuario_id
			left join correomasivocampana on correomasivocampana.correomasivocampana_id = correomasivo.correomasivocampana_id
			
		",
		"usuario_activo = '1'  and usuario.compania_id = '$compania_id' 
		and (correomasivodetalle.usuario_iddestino = '$usuario_id') ", null, 
		"correomasivodetalle.correomasivodet_id desc LIMIT 50");
			
	// 
	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$elemento_id = utf8_encode($valor["elemento_id"]);
			$usuario_iddestino = utf8_encode($valor["usuario_iddestino"]);

			$correomasivocampana_id = utf8_encode($valor["correomasivocampana_id"]);
			$correomasivocampana_titulo = utf8_encode($valor["correomasivocampana_titulo"]);
			$correomasivocampana_descripcorta = utf8_encode($valor["correomasivocampana_descripcorta"]);
			
			$correomasivodet_id = utf8_encode($valor["correomasivodet_id"]);
			$correomasivo_id = utf8_encode($valor["correomasivo_id"]);
			$correomasivo_codigo = utf8_encode($valor["correomasivo_codigo"]);
			
			$correomasivodet_usuarionombre = utf8_encode($valor["correomasivodet_usuarionombre"]);
			$correomasivodet_usuarioemail = utf8_encode($valor["correomasivodet_usuarioemail"]);
			$usuario_id = utf8_encode($valor["usuario_id"]);
			$l_estatus_id = utf8_encode($valor["l_estatus_id"]);
			$correomasivodet_activo = utf8_encode($valor["correomasivodet_activo"]);
			$correomasivodet_fechareg = utf8_encode($valor["correomasivodet_fechareg"]);
			$correomasivodet_titulo = utf8_encode($valor["correomasivodet_titulo"]);
			$correomasivodet_descrip = utf8_encode($valor["correomasivodet_descrip"]);
			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_email = utf8_encode($valor["usuario_email"]);
			$usuario_whatsapp = utf8_encode($valor["usuario_whatsapp"]);
			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_fechareg = utf8_encode($valor["usuario_fechareg"]);
			$estatus_nombre = utf8_encode($valor["estatus_nombre"]);
			$estatus_cod = utf8_encode($valor["estatus_cod"]);	
			
			$titulo = $correomasivodet_titulo;
			$descripcion = $correomasivodet_descrip;

			if ($correomasivocampana_id!=""){
				$titulo = $correomasivocampana_titulo;
				$descripcion = $correomasivocampana_descripcorta;
			}

			$data = array(				
				"id" => $correomasivodet_id,
				"transid" => $elemento_id,
				//"codigo" => $correomasivo_codigo,
				"elementoid" => $elemento_id,
				"usuarioid" => $usuario_iddestino,
				"titulo" => $titulo,
				"descripcion" => $descripcion,
				"fecha" => $correomasivodet_fechareg,
				"estatuscod" => $estatus_cod,
				"estatus" => $estatus_nombre
			);
	
			array_push($datatotal, $data);

		}

		$resultado = $conexion->doUpdate("correomasivodetalle", 
		"correomasivodet_leido = '1'", 
		"correomasivodetalle.usuario_iddestino = '$usuario_id' and 
		(correomasivodetalle.correomasivodet_leido is null or correomasivodetalle.correomasivodet_leido = 0 ) ");


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