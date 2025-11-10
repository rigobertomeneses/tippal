<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');


ini_set ("display_errors","0");

include_once '../lib/mysqlclass.php';
include_once '../lib/funciones.php';
include_once '../lib/phpmailer/libemail.php';


$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];
$metodo = "POST";

if ($metodo=="POST"){// Guardar dispositivoregistrar

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['nombre'])) ? $nombre=$valoresPost['nombre'] :$nombre='';
	(isset($valoresPost['marca'])) ? $marca=$valoresPost['marca'] :$marca='';
	(isset($valoresPost['modelo'])) ? $modelo=$valoresPost['modelo'] :$modelo='';
	(isset($valoresPost['codigo'])) ? $codigo=$valoresPost['codigo'] :$codigo='';
	
	$nombre = utf8_decode($nombre);
	$marca = utf8_decode($marca);
	$modelo = utf8_decode($modelo);
	$codigo = utf8_decode($codigo);		

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$arrresultado2 = $conexion->doSelect("compania_nombre, compania_img","compania","compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$compania_nombre = utf8_encode($valor2["compania_nombre"]);	
		$compania_img = utf8_encode($valor2["compania_img"]);	
	}

	$compania_img = ObtenerUrlArch($compania_id)."/$compania_img";

	$arrresultado2 = $conexion->doSelect("dispositivo_id, dispositivo_activo","dispositivo","dispositivo_cod = '$codigo' and dispositivo_eliminado = '0'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$dispositivo_id = utf8_encode($valor2["dispositivo_id"]);	
		$dispositivo_activo = utf8_encode($valor2["dispositivo_activo"]);					
	}

	if ($dispositivo_id==""){

		$resultado = $conexion->doInsert("
		dispositivo
		(dispositivo_nombre, dispositivo_marca, dispositivo_modelo, 
		dispositivo_cod, usuario_id, dispositivo_fechareg, 
		dispositivo_activo, dispositivo_eliminado, dispositivo_ultfecha)
		",
		"'$nombre', '$marca', '$modelo',
		'$codigo', '0', '$fechaactual',
		'1','0', '$fechaactual'
		");

	}else{

		$resultado = $conexion->doUpdate("dispositivo", "
			dispositivo_nombre ='$nombre',
			dispositivo_marca ='$marca',
			dispositivo_modelo ='$modelo',
			dispositivo_ultfecha ='$fechaactual'
			",
			"dispositivo_id='$dispositivo_id' ");

	}
	
	if ($dispositivo_activo=="1"){
		$dispositivomostrar_activo = "Activo";
	}else{
		$dispositivomostrar_activo = "Inactivo";
	}

	$data = array(
		"estatus" => $dispositivomostrar_activo,		
		"estatusid" => $dispositivo_activo,		
		"compania_nombre" => $compania_nombre,		
		"compania_img" => $compania_img
	);

	$valores = array(
		"code" => 0,
		"message" => "Guardado correctamente",
		"data" => $data
	);


}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>