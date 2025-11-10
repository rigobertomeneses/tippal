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

if ($metodo=="POST"){// Consultar Opciones de Retiro del Usuario

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['unicoresultado'])) ? $unicoresultado=$valoresPost['unicoresultado'] :$unicoresultado='';
	
	if ($compania_id==""){
		$compania_id = 0;
	}
	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: usuarioretiropcion",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$arrresultado2 = $conexion->doSelect("usuario_id","usuario","usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuarioretiropcion",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

	$arrresultado = $conexion->doSelect("usuario.usuario_id, usuario.usuario_codigo, usuario.usuario_email, usuario.usuario_clave, usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_telf, usuario.usuario_fechareg, usuario.usuario_activo, usuario.usuario_eliminado, usuario.usuario_documento, usuario.usuario_img, usuario.perfil_id, usuario.usuario_direccion, 
	DATE_FORMAT(usuario.usuario_fechanac,'$formatofechaSQL') as usuario_fechanac,
	usuario.l_tipodocumento_id, usuario.cuenta_id, usuario.compania_id, usuario.perfil_id, 
	usuario.usuario_whatsapp, usuario.l_estatus_id,

	usuarioretiro.usuarioretiro_id, usuarioretiro.usuarioretiro_descripcion, usuarioretiro.usuarioretiro_email, 
	usuarioretiro.usuarioretiro_fechareg, usuarioretiro.usuarioretiro_activo, 
	usuarioretiro.usuarioretiro_eliminado, usuarioretiro.l_formapago_id, usuarioretiro.usuarioretiro_banco, 
	usuarioretiro.usuarioretiro_titular, usuarioretiro.usuarioretiro_tipocuenta, usuarioretiro.usuarioretiro_documento, 
	usuarioretiro.usuarioretiro_nrocuenta,

	formapago.lista_nombre as formapago_nombre,
	formapago.lista_img as formapago_img,

	cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
	cuenta.usuario_apellido as cuenta_apellido, compania_nombre,

	banco.lista_id as banco_id, banco.lista_nombre as banco_nombre,
	tipocuenta.lista_id as tipocuenta_id, tipocuenta.lista_nombre as tipocuenta_nombre

	",
	"usuario	
		inner join usuarioretiro on usuarioretiro.usuario_id = usuario.usuario_id
		inner join lista formapago on formapago.lista_id = usuarioretiro.l_formapago_id
		inner join usuario cuenta on cuenta.usuario_id = usuario.cuenta_id
		inner join compania on compania.compania_id = usuario.compania_id
		left join lista banco on banco.lista_id = usuarioretiro.l_banco_id
		left join lista tipocuenta on tipocuenta.lista_id = usuarioretiro.l_tipocuenta_id

	",
	"usuario.usuario_eliminado = '0' and usuarioretiro_eliminado = '0' and usuarioretiro.usuario_id = '$usuario_id'  ", 
	null, "usuarioretiro.usuarioretiro_id desc");

	//  and usuario.usuario_id = '$usuario_id' 
			
	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$banco_id = utf8_encode($valor["banco_id"]);
			$banco_nombre = utf8_encode($valor["banco_nombre"]);
			$tipocuenta_id = utf8_encode($valor["tipocuenta_id"]);
			$tipocuenta_nombre = utf8_encode($valor["tipocuenta_nombre"]);
			
			$usuarioretiro_id = utf8_encode($valor["usuarioretiro_id"]);
			$usuarioretiro_descripcion = utf8_encode($valor["usuarioretiro_descripcion"]);
			$usuarioretiro_email = utf8_encode($valor["usuarioretiro_email"]);
			$usuarioretiro_fechareg = utf8_encode($valor["usuarioretiro_fechareg"]);
			$usuarioretiro_activo = utf8_encode($valor["usuarioretiro_activo"]);		
			$l_formapago_id = utf8_encode($valor["l_formapago_id"]);
			$usuarioretiro_banco = utf8_encode($valor["usuarioretiro_banco"]);
			$usuarioretiro_titular = utf8_encode($valor["usuarioretiro_titular"]);
			$usuarioretiro_tipocuenta = utf8_encode($valor["usuarioretiro_tipocuenta"]);
			$usuarioretiro_documento = utf8_encode($valor["usuarioretiro_documento"]);
			$usuarioretiro_nrocuenta = utf8_encode($valor["usuarioretiro_nrocuenta"]);		

			$formapago_nombre = utf8_encode($valor["formapago_nombre"]);
			$formapago_img = utf8_encode($valor["formapago_img"]);		

			$imagen = ObtenerUrlArch($compania_id)."/$formapago_img";
			

			if ($banco_nombre!=""){
				$usuarioretiro_banco = $banco_nombre;
			}

			if ($tipocuenta_nombre!=""){
				$usuarioretiro_tipocuenta = $tipocuenta_nombre;
			}		
			
			$data = array(
				"id" => $usuarioretiro_id,
				"descripcion" => $usuarioretiro_descripcion,
				"email" => $usuarioretiro_email,
				"banco" => $usuarioretiro_banco,
				"titular" => $usuarioretiro_titular,
				"tipocuenta" => $usuarioretiro_tipocuenta,
				"documento" => $usuarioretiro_documento,
				"nrocuenta" => $usuarioretiro_nrocuenta,
				"formapago" => $formapago_nombre,
				"formapagoimagen" => $imagen,
				"l_formapago_id" => $l_formapago_id,
				"formapago_id" => $l_formapago_id,
				"banco_id" => $banco_id,
				"tipocuenta_id" => $tipocuenta_id
			);
	
			array_push($datatotal, $data);

		}

		if ($unicoresultado==true){
			$datatotal = $data;
		}

		$dataReturn = array(
			"items" => $datatotal,
			"total" => $total				
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