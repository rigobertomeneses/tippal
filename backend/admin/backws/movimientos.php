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

if ($metodo=="POST"){// Consultar Movimientos

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['limit'])) ? $limit=$valoresPost['limit'] :$limit='';
	(isset($valoresPost['tipoMovimiento'])) ? $tipoMovimiento=$valoresPost['tipoMovimiento'] :$tipoMovimiento='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}
	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: taxiviajes",
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
			"message" => "Usuario / Token no activo. url: taxiviajes",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	if ($limit!=""){
		$limitsql = " LIMIT $limit ";
	}

	// Build WHERE conditions based on movement type
	if ($tipoMovimiento == "enviados"){
		// Movimientos que yo envié (soy el usuario_id y tipo es 21=Enviado)
		$whereUsuario = "movimiento.usuario_id = '$usuario_id' and tipomovimiento.lista_cod = '21'";
		$wheretipoMovimiento = "";
	} else if ($tipoMovimiento == "recibidos"){
		// Movimientos que yo recibí (soy el usuario_iddestino y tipo es 22=Recibido)
		$whereUsuario = "movimiento.usuario_iddestino = '$usuario_id' and tipomovimiento.lista_cod = '22'";
		$wheretipoMovimiento = "";
	} else {
		// Si no se especifica tipo, mostrar todos los movimientos donde participo
		$whereUsuario = "(movimiento.usuario_id = '$usuario_id' OR movimiento.usuario_iddestino = '$usuario_id')";
		$wheretipoMovimiento = "";
	}

	

	$formatofechaSQL = formatofechaSQL($compania_id);

	$arrresultado = $conexion->doSelect("
		movimiento.mov_id, movimiento.mov_descrip, movimiento.l_tipomov_id, 
		movimiento.usuario_id, movimiento.usuario_iddestino, movimiento.mov_monto, 
		movimiento.elemento_id, movimiento.l_formapago_id, 
		movimiento.mov_fechareg, movimiento.mov_activo, movimiento.mov_eliminado, 
		movimiento.cuenta_id, movimiento.compania_id,
		DATE_FORMAT(movimiento.mov_fecha,'$formatofechaSQL %H:%i:%s') as mov_fecha,

		DATE_FORMAT(movimiento.mov_fecha,'$formatofechaSQL') as mov_fecha2,

		moneda.lista_nombredos as moneda_siglas,
		formapago.lista_nombre as formapago_nombre,
		tipomovimiento.lista_nombre as tipomovimiento_nombre,
		tipomovimiento.lista_img as tipomovimiento_img,
		usuario.usuario_nombre,
		usuario.usuario_apellido,
		usuario.usuario_email,
		usuario.usuario_alias,
		usuario.usuario_telf,
		usuario_destino.usuario_nombre as usuario_destino_nombre,
		usuario_destino.usuario_apellido as usuario_destino_apellido,
		usuario_destino.usuario_email as usuario_destino_email,
		usuario_destino.usuario_alias as usuario_destino_alias,
		usuario_destino.usuario_telf as usuario_destino_telf,
		estatus.lista_nombre as estatus_nombre,
		estatus.lista_cod as estatus_cod,
		estatus.lista_color as estatus_color

		",
		"movimiento
			inner join lista moneda on moneda.lista_id = movimiento.l_moneda_id
			inner join lista tipomovimiento on tipomovimiento.lista_id = movimiento.l_tipomov_id
			left join usuario on usuario.usuario_id = movimiento.usuario_id
			left join usuario as usuario_destino on usuario_destino.usuario_id = movimiento.usuario_iddestino
			left join lista formapago on formapago.lista_id = movimiento.l_formapago_id
			left join lista estatus on estatus.lista_id = movimiento.l_estatus_id
		",
		"movimiento.mov_activo = '1' and $whereUsuario $wheretipoMovimiento ", null, "movimiento.mov_id desc $limitsql");
			
	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$estatus_nombre = utf8_encode($valor["estatus_nombre"]);
			$estatus_cod = utf8_encode($valor["estatus_cod"]);
			$estatus_color = utf8_encode($valor["estatus_color"]);
			
			$mov_id = utf8_encode($valor["mov_id"]);
			$mov_descrip = utf8_encode($valor["mov_descrip"]);
			$l_tipomov_id = utf8_encode($valor["l_tipomov_id"]);
			$usuario_id = utf8_encode($valor["usuario_id"]);
			$mov_monto = utf8_encode($valor["mov_monto"]);
			$elemento_id = utf8_encode($valor["elemento_id"]);
			$l_formapago_id = utf8_encode($valor["l_formapago_id"]);
			$mov_fechareg = utf8_encode($valor["mov_fechareg"]);
			$mov_activo = utf8_encode($valor["mov_activo"]);
			$mov_fecha = utf8_encode($valor["mov_fecha"]);
			$mov_fechasola = utf8_encode($valor["mov_fecha2"]);
			$moneda_siglas = utf8_encode($valor["moneda_siglas"]);
			$formapago_nombre = utf8_encode($valor["formapago_nombre"]);
			$tipomovimiento_nombre = utf8_encode($valor["tipomovimiento_nombre"]);
			$tipomovimiento_img = utf8_encode($valor["tipomovimiento_img"]);
			
			// Usuario origen (puede ser NULL si usuario_id = 0 para pagadores anónimos)
			$usuario_nombre = isset($valor["usuario_nombre"]) ? utf8_encode($valor["usuario_nombre"]) : '';
			$usuario_apellido = isset($valor["usuario_apellido"]) ? utf8_encode($valor["usuario_apellido"]) : '';
			$usuario_email = isset($valor["usuario_email"]) ? utf8_encode($valor["usuario_email"]) : '';
			$usuario_alias = isset($valor["usuario_alias"]) ? utf8_encode($valor["usuario_alias"]) : '';
			$usuario_telf = isset($valor["usuario_telf"]) ? utf8_encode($valor["usuario_telf"]) : '';
			$usuario = $usuario_nombre." ".$usuario_apellido;

			// Construir el nombre para mostrar del usuario origen
			// Si usuario_id = 0, mostrar "Anónimo" o descripción del pago
			$usuario_origen_display = null;

			if ($usuario_id == '0' || $usuario_id == 0) {
				// Para pagadores anónimos (Tap to Pay recibidos)
				$usuario_origen_display = 'Cliente';
			} else if ($usuario_nombre != '' || $usuario_apellido != '') {
				$usuario_origen_display = trim($usuario_nombre . " " . $usuario_apellido);
			} else if ($usuario_alias != '') {
				$usuario_origen_display = $usuario_alias;
			} else if ($usuario_email != '') {
				$usuario_origen_display = $usuario_email;
			} else if ($usuario_telf != '') {
				$usuario_origen_display = $usuario_telf;
			}
			
			// Usuario destinatario
			$usuario_iddestino = isset($valor["usuario_iddestino"]) ? utf8_encode($valor["usuario_iddestino"]) : null;
			$usuario_destino_nombre = isset($valor["usuario_destino_nombre"]) ? utf8_encode($valor["usuario_destino_nombre"]) : '';
			$usuario_destino_apellido = isset($valor["usuario_destino_apellido"]) ? utf8_encode($valor["usuario_destino_apellido"]) : '';
			$usuario_destino_email = isset($valor["usuario_destino_email"]) ? utf8_encode($valor["usuario_destino_email"]) : '';
			$usuario_destino_alias = isset($valor["usuario_destino_alias"]) ? utf8_encode($valor["usuario_destino_alias"]) : '';
			$usuario_destino_telf = isset($valor["usuario_destino_telf"]) ? utf8_encode($valor["usuario_destino_telf"]) : '';
			
			// Debug logging
			if ($usuario_iddestino == '7427' || $mov_monto == '30.00') {
				error_log("DEBUG movimientos.php - Movement ID: $mov_id, Amount: $mov_monto");
				error_log("DEBUG - usuario_iddestino: " . var_export($usuario_iddestino, true));
				error_log("DEBUG - usuario_destino_nombre: " . var_export($usuario_destino_nombre, true));
				error_log("DEBUG - usuario_destino_apellido: " . var_export($usuario_destino_apellido, true));
				error_log("DEBUG - usuario_destino_email: " . var_export($usuario_destino_email, true));
				error_log("DEBUG - usuario_destino_alias: " . var_export($usuario_destino_alias, true));
				error_log("DEBUG - usuario_destino_telf: " . var_export($usuario_destino_telf, true));
			}
			
			// Construir el nombre para mostrar del destinatario
			// Prioridad: Nombre completo > Alias > Email > Teléfono
			$usuario_destino = null;
			$usuario_destino_display = null;
			
			if ($usuario_destino_nombre != '' || $usuario_destino_apellido != '') {
				$usuario_destino = trim($usuario_destino_nombre . " " . $usuario_destino_apellido);
				$usuario_destino_display = $usuario_destino;
			} else if ($usuario_destino_alias != '') {
				$usuario_destino_display = $usuario_destino_alias;
			} else if ($usuario_destino_email != '') {
				$usuario_destino_display = $usuario_destino_email;
			} else if ($usuario_destino_telf != '') {
				$usuario_destino_display = $usuario_destino_telf;
			}

			$tipomonto = 0;
			if ($mov_monto==0){
				$tipomonto = 0;
			}else if ($mov_monto>0){
				$tipomonto = 1;
			}else if ($mov_monto<0){
				$tipomonto = 2;
			}

			$mov_monto = number_format_personalizado($mov_monto, $moneda_siglas, null, $compania_id);
			

			$imagen = ObtenerUrlArch($compania_id)."/$tipomovimiento_img";
			
			$data = array(
				"id" => $mov_id,
				"imagen" => $imagen,
				"monto" => $mov_monto,
				"estatusnombre" => $estatus_nombre,
				"estatuscod" => $estatus_cod,
				"estatuscolor" => $estatus_color,
				"moneda_siglas" => $moneda_siglas,
				"elemento" => $elemento_id,
				"fecha" => $mov_fecha,
				"fechasola" => $mov_fechasola,
				"usuario_origen" => $usuario,
				"usuario_origen_id" => $usuario_id,
				"usuario_origen_email" => $usuario_email,
				"usuario_origen_alias" => $usuario_alias,
				"usuario_origen_telf" => $usuario_telf,
				"usuario_origen_display" => $usuario_origen_display,
				"usuario_destino" => $usuario_destino,
				"usuario_destino_id" => $usuario_iddestino,
				"usuario_destino_email" => $usuario_destino_email,
				"usuario_destino_alias" => $usuario_destino_alias,
				"usuario_destino_telf" => $usuario_destino_telf,
				"usuario_destino_display" => $usuario_destino_display,
				"formapago" => $formapago_nombre,
				"tipomovimiento" => $tipomovimiento_nombre,
				"tipomonto" => $tipomonto							
			);
	
			array_push($datatotal, $data);

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