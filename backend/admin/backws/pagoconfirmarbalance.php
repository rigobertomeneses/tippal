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

if ($metodo=="POST"){// Confirma el conductor

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['monto'])) ? $monto =$valoresPost['monto'] :$monto ='';
	(isset($valoresPost['id'])) ? $getusuariodestino_id=$valoresPost['id'] :$getusuariodestino_id='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}


	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: pagoconfirmarbalance",
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
			"message" => "Usuario / Token no activo. url: pagoconfirmarbalance",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$monto = str_replace(".", "", $monto);
	$monto = str_replace(",", ".", $monto);

	$moneda = ObtenerMonedaPrincipalIdTaxi($cuenta_id, $compania_id);

	// se quita el monto al origen :
	$arrresultado2 = $conexion->doSelect("usuariobalance_total, usuariobalance_bloqueado, usuariobalance_disponible, usuariobalance_pendiente, usuariobalance.usuariobalance_id",
	"usuariobalance",
	"usuariobalance_eliminado = '0' and usuariobalance.usuario_id = '$usuario_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuariobalance_id = utf8_encode($valor2["usuariobalance_id"]);					
		$usuariobalance_total = utf8_encode($valor2["usuariobalance_total"]);					
		$usuariobalance_disponible = utf8_encode($valor2["usuariobalance_disponible"]);
		$usuariobalance_bloqueado = utf8_encode($valor2["usuariobalance_bloqueado"]);					
	}
		
	$usuariobalance_total = $usuariobalance_total - $monto;
	$usuariobalance_disponible = $usuariobalance_disponible - $monto;	

	$resultado = $conexion->doUpdate("usuariobalance", "			    
		usuariobalance_total = '$usuariobalance_total',
		usuariobalance_disponible = '$usuariobalance_disponible'				    
	", "usuario_id='$usuario_id' and usuariobalance_eliminado = '0'"); 



	// se suma el monto al destino:
	$arrresultado2 = $conexion->doSelect("usuariobalance_total, usuariobalance_bloqueado, usuariobalance_disponible, usuariobalance_pendiente, usuariobalance.usuariobalance_id",
	"usuariobalance",
	"usuariobalance_eliminado = '0' and usuariobalance.usuario_id = '$getusuariodestino_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuariobalance_id = utf8_encode($valor2["usuariobalance_id"]);					
		$usuariobalancedestino_total = utf8_encode($valor2["usuariobalance_total"]);					
		$usuariobalancedestino_disponible = utf8_encode($valor2["usuariobalance_disponible"]);
	}
		
	$usuariobalancedestino_total = $usuariobalancedestino_total + $monto;
	$usuariobalancedestino_disponible = $usuariobalancedestino_disponible + $monto;

	$resultado = $conexion->doUpdate("usuariobalance", "			    
		usuariobalance_total = '$usuariobalancedestino_total',
		usuariobalance_disponible = '$usuariobalancedestino_disponible'				    
	", "usuario_id='$getusuariodestino_id' and usuariobalance_eliminado = '0'"); 
	

	$instancialista = new Lista();

	// Guardo en Movimiento del destino
	$obtenerCodigoLista = 2; // Pago realizado
	$obtenerTipoLista = 269; // Tipo de Movimiento
	$tipomovimiento = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);


	$lista_cod = 999;

	$arrresultado2 = $conexion->doSelect("formapago.lista_id as formapago_id",
	"lista formapago 
		inner join lista formapagorel on formapagorel.lista_id = formapago.lista_idrel
	",
	"formapagorel.lista_cod = '$lista_cod' and formapago.compania_id= '$compania_id'  ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$formapago_id = utf8_encode($valor2["formapago_id"]);					
	}

	$elemento_id = 0;

	$resultado = $conexion->doInsert("
		movimiento
		(mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha, 
		elemento_id, l_moneda_id, 
		l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id)
		",
		"'', '$tipomovimiento', '$usuario_id', '-$monto','$fechaactual', 
		'$usuariobalance_id','$moneda',
		'$formapago_id', '$fechaactual', '1', '0','$cuenta_id','$compania_id'");


		
	$resultado = $conexion->doInsert("
		movimiento
		(mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha, 
		elemento_id, l_moneda_id, 
		l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id)
		",
		"'', '$tipomovimiento', '$getusuariodestino_id', '$monto','$fechaactual', 
		'$usuariobalance_id','$moneda',
		'$formapago_id', '$fechaactual', '1', '0','$cuenta_id','$compania_id'");



	$total = count($arrresultado2);		

	if($total>0){

		$dataReturn = array(
			"finalizado" => 1				
		);

		$valores = array(
			"code" => 0,
			"data" => $dataReturn,
			"message" => "Pago Correcto"
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