<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set ("display_errors","0");

include_once '../../lib/mysqlclass.php';
include_once '../../lib/funciones.php';
include_once '../../lib/phpmailer/libemail.php';
include_once '../../models/lista.php';


$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo=="POST"){// Eliminar Cuenta

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
			"message" => "Token no encontrado. url: eliminarcuenta",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	// 
	$arrresultado2 = $conexion->doSelect("usuario_id","usuario","usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: eliminarcuenta",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	//

	$instancialista = new Lista();

	$obtenerCodigoLista = 5; // Eliminado Usuario Voluntaria
	$obtenerTipoLista = 53; // Estatus de Usuarios
	$estatus = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

	$resultado = $conexion->doUpdate("usuario", "
		usuario_activo ='0',
		l_estatus_id = '$estatus'
	",
	"usuario_id='$usuario_id'");

	$resultado = 1;

	if($resultado==1){

		$dataReturn = array(
			"resultado" => $resultado		
		);

		$valores = array(
			"code" => 0,
			"data" => $dataReturn,
			"message" => "Cuenta eliminada correctamente"
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