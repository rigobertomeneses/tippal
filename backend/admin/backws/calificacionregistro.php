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

if ($metodo=="POST"){// Registrar Calificación

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['trans'])) ? $trans=$valoresPost['trans'] :$trans='';
	(isset($valoresPost['transnotif'])) ? $transnotif=$valoresPost['transnotif'] :$transnotif='';
	(isset($valoresPost['elementoid'])) ? $elementoid=$valoresPost['elementoid'] :$elementoid='';
	(isset($valoresPost['tipo'])) ? $tipo=$valoresPost['tipo'] :$tipo='viaje';	
	(isset($valoresPost['valorcalificacion'])) ? $valorcalificacion=$valoresPost['valorcalificacion'] :$valorcalificacion='';
	(isset($valoresPost['comentario'])) ? $comentario=$valoresPost['comentario'] :$comentario='';

	$comentario = utf8_decode($comentario);

	if ($compania_id==""){
		$compania_id = 0;
	}

	if ($transnotif!=""){
		$elementoid = $transnotif;
	}

	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: calificacionregistro",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$instancialista = new Lista();
	
	$arrresultado2 = $conexion->doSelect("usuario.usuario_id, usuario.compania_id, compania.cuenta_id",
	"usuario
		inner join compania on compania.compania_id = usuario.compania_id
	",
	"usuario_activo = '1' and usuario_codverif = '$token' and usuario.compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);	
		$compania_id = utf8_encode($valor2["compania_id"]);	
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: calificacionregistro",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	if ($tipo=="viaje"){
		
		$arrresultado = $conexion->doSelect("lista.lista_id","lista",
			"lista.tipolista_id = '258' and lista.lista_activo = '1' and lista_cod in (2,5,6,7,8,9)");
		$whereestatus = "";
		foreach($arrresultado as $i=>$valor){
			$lista_id = utf8_encode($valor["lista_id"]);
			if ($whereestatus==""){
				$whereestatus .=$lista_id;
			}else{
				$whereestatus .=",".$lista_id;
			}			
		}

		$arrresultado = $conexion->doSelect("
		cliente.usuario_id as pasajero,  conductor.usuario_id as conductor,
		transacciontaxi.trans_id,
		transacciontaxinotificacion.transtaxinotif_id
			
			",
			"transaccion
				inner join transacciontaxi on transacciontaxi.trans_id = transaccion.trans_id
				inner join transacciontaxicalculo on transacciontaxicalculo.transtaxi_id = transacciontaxi.transtaxi_id
				inner join lista estatus on estatus.lista_id = transaccion.l_estatus_id
				inner join usuario on usuario.usuario_id = transaccion.cliente_id
				
				inner join lista tipotarifa on tipotarifa.lista_id = transacciontaxicalculo.l_tipotarifa_id

				inner join usuario cliente on cliente.usuario_id = transaccion.cliente_id

				inner join transacciontaxinotificacion on transacciontaxinotificacion.transtaxicalculo_id = transacciontaxicalculo.transtaxicalculo_id and transacciontaxinotificacion.transtaxinotif_activo = '1' and transacciontaxinotificacion.l_estatus_id in ($whereestatus) 
				left join usuariovehiculo on usuariovehiculo.usuariovehiculo_id = transacciontaxinotificacion.usuariovehiculo_id
				left join vehiculo on vehiculo.vehiculo_id = usuariovehiculo.vehiculo_id
				left join usuario conductor on conductor.usuario_id = transacciontaxinotificacion.usuario_id

				left join usuariogeolocalizacion on usuariogeolocalizacion.usuario_id = transacciontaxinotificacion.usuario_id and usuariogeo_activo = '1'

				left join lista moneda on moneda.lista_id = transaccion.l_moneda_id

				",
			"transaccion.trans_activo = '1' and transacciontaxinotificacion.transtaxinotif_id = '$elementoid' ", null, "transaccion.trans_id desc");

		foreach($arrresultado as $i=>$valor){
			$pasajero = utf8_encode($valor["pasajero"]);
			$conductor = utf8_encode($valor["conductor"]);
			$trans_id = utf8_encode($valor["trans_id"]);
			$transtaxinotif_id = utf8_encode($valor["transtaxinotif_id"]);
		}

		$usuario_idcalificar = 0;

		if ($pasajero==$usuario_id){
			$usuario_idcalificar = $conductor;
		}else if ($conductor==$usuario_id){
			$usuario_idcalificar = $pasajero;
		}else{
			$valores = array(
				"code" => 104,
				"pasajero" => $pasajero,
				"usuario_id" => $usuario_id,
				"conductor" => $conductor,
				"usuario_idcalificar" => $usuario_idcalificar,
				"code" => 104,
				"message" => "No se pudo calificar, no tiene permisos para calificar",
				"data" => [],
			);

			$resultado = json_encode($valores);
			echo $resultado;
			exit();

		}

		
		/*
		
		$obtenerCodigoLista = 5; // Calificada
		$obtenerTipoLista = 255; // Estatus Solicitud General Taxi
		$estatustrans = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

		$resultado = $conexion->doUpdate("transaccion", "
			l_estatus_id ='$estatustrans'
		",
		"trans_id ='$trans_id'");


		$obtenerCodigoLista = 5; // Calificada
		$obtenerTipoLista = 258; // Estatus Solicitud por Notificacion Viaje por Conductor
		$estatustransnotif = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

		$resultado = $conexion->doUpdate("transacciontaxinotificacion", "
			l_estatus_id ='$estatustransnotif'
		",
		"transtaxinotif_id ='$transtaxinotif_id'");

		*/
		
	}else if ($tipo=="proyecto"){

		$arrresultado = $conexion->doSelect("propuesta_proyecto.usuario_id as trabajador_id, 
		proyecto.usuario_id as empleador_id
		",
		"propuesta_proyecto
			inner join proyecto on propuesta_proyecto.proy_id = proyecto.proy_id
		",
		"propuestaproy_id = '$elementoid' ");
		foreach($arrresultado as $i=>$valor){
			$trabajador_id = utf8_encode($valor["trabajador_id"]);
			$empleador_id = utf8_encode($valor["empleador_id"]);
		}
		
		$usuario_idcalificar = 0;

		if ($trabajador_id==$usuario_id){
			$usuario_idcalificar = $empleador_id;
		}else if ($empleador_id==$usuario_id){
			$usuario_idcalificar = $trabajador_id;
		}else{
			$valores = array(
				"code" => 104,
				"message" => "No se pudo calificar, no tiene permisos para calificar",
				"data" => [],
			);

			$resultado = json_encode($valores);
			echo $resultado;
			exit();
		}

		
	}

	

	

	$obtenerCodigoLista = 2; // Aprobado
	$obtenerTipoLista = 57; // Estatus de Rating de Usuarios
	$estatus = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

	$resultado = $conexion->doInsert("
	usuariorating
	(usuario_idorigen, usuario_iddestino, usuariorating_valor, usuariorating_comentario,
	 usuariorating_fechareg, usuariorating_activo, usuariorating_eliminado, l_estatus_id, 
	 cuenta_id, compania_id)
	",
	"'$usuario_id', '$usuario_idcalificar', '$valorcalificacion', '$comentario', 
	'$fechaactual', '1', '0', '$estatus', 
	'$cuenta_id', '$compania_id'");

	$arrresultado = $conexion->doSelect("max(usuariorating_id) as usuariorating_id","usuariorating");	    	
	foreach($arrresultado as $i=>$valor){  
		$usuariorating_id = utf8_encode($valor["usuariorating_id"]);
	}	


	$resultado = $conexion->doInsert("
	usuarioratingservicio
	(usuariorating_id, solicitudserv_id, usuarioratingserv_fechareg, usuario_idreg)
	",
	"'$usuariorating_id', '$elementoid', '$fechaactual', '$usuario_id'");

	
	// Promedio la calificacion del que estoy calificando
	$arrresultado = $conexion->doSelect("avg(usuariorating_valor) as usuariodestinoavg",
	"usuariorating", 
	"usuariorating_activo = '1' and usuario_iddestino = '$usuario_idcalificar' and l_estatus_id = '$estatus'");	    	
	foreach($arrresultado as $i=>$valor){  
		$usuariodestinoavg = utf8_encode($valor["usuariodestinoavg"]);
	}	
	

	// Calificar
	$resultado = $conexion->doUpdate("usuario", "
	usuario_rating ='$usuariodestinoavg'					
	",
	"usuario_id='$usuario_idcalificar'");

	$registrado = 1;

	if($registrado==1){

		$dataReturn = array(
			"registrado" => $registrado		
		);

		$valores = array(
			"code" => 0,
			"data" => $dataReturn,
			"message" => "Se registro la calificacion correctamente"
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