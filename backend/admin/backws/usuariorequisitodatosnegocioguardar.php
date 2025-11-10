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

if ($metodo=="POST"){// Guardar usuariorequisitodatosnegocioguardar

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['requisitoid'])) ? $requisitoid=$valoresPost['requisitoid'] :$requisitoid='';
	(isset($valoresPost['nombre'])) ? $nombre=$valoresPost['nombre'] :$nombre='';
	(isset($valoresPost['empresa'])) ? $empresa=$valoresPost['empresa'] :$empresa='';
	(isset($valoresPost['direccion'])) ? $direccion=$valoresPost['direccion'] :$direccion='';
	(isset($valoresPost['documento'])) ? $documento=$valoresPost['documento'] :$documento='';
	(isset($valoresPost['telf'])) ? $telf=$valoresPost['telf'] :$telf='';
	
	if ($compania_id==""){
		$compania_id = 0;
	}

	$nombre = utf8_decode($nombre);
	$empresa = utf8_decode($empresa);
	$direccion = utf8_decode($direccion);
	$documento = utf8_decode($documento);
	$telf = utf8_decode($telf);	
	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: usuariorequisitodatosnegocioguardar",
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
			"message" => "Usuario / Token no activo. url: usuariorequisitodatosnegocioguardar",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}


	$resultado = $conexion->doUpdate("usuario", "
		usuario_representantelegal = '$nombre',
		usuario_empresa = '$empresa',
		usuario_direccion = '$direccion',
		usuario_documento = '$documento',
		usuario_telf = '$telf'
	", "usuario_id='$usuario_id' ");	

	// Completar requisito
	$instancialista = new Lista();

	$obtenerIdLista = 4;
	$obtenerTipoLista = 50;
	$estatuspendiente = $instancialista->ObtenerIdLista($obtenerIdLista, $obtenerTipoLista);

	$resultado = $conexion->doUpdate("requisito", 
	"						
		l_estatus_id = '$estatuspendiente'
	", 
	"requisito_id='$requisitoid'");


	if($resultado==true){		

		$valores = array(
			"code" => 0,
			"data" => [],
			"message" => "Registro Correcto"
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