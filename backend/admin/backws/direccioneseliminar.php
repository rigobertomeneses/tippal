<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

ini_set ("display_errors","0");

include_once '../lib/mysqlclass.php';
include_once '../lib/funciones.php';
include_once '../models/lista.php';

$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];
$metodo = "POST";

if ($metodo=="POST"){// eliminar direcciones

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['id'])) ? $get_id=$valoresPost['id'] :$get_id='';
	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 381;
	}

	
 	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: direccioneseliminar",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}


	$arrresultado2 = $conexion->doSelect("usuario.usuario_id, usuario.cuenta_id, usuario.perfil_id,
	perfil.perfil_idorig
	",
	"usuario
		inner join perfil on perfil.perfil_id = usuario.perfil_id	
	",
	"usuario_activo = '1' and usuario_codverif = '$token' and usuario.compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);			
		$perfil_id = utf8_encode($valor2["perfil_id"]);	
		$perfil_idorig = utf8_encode($valor2["perfil_idorig"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: direccioneseliminar",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	if ($get_id!=""){

		// Busco primero si tiene productos activos
		$arrresultado2 = $conexion->doSelect("prod_id","producto","prod_eliminado = '0' and usuario_idreg = '$usuario_id' and compania_id = '$compania_id'");
		if (count($arrresultado2)>0){
			$valores = array(
				"code" => 101,
				"message" => "No se puede eliminar la dirección",
				"data" => null,
			);

			$resultado = json_encode($valores);

			echo $resultado;

			exit();
		}

		if ($perfil_idorig=="2" || $perfil_idorig=="3"){


			$resultado = $conexion->doUpdate("usuariodireccion", "
			usuariodireccion_activo ='0',
			usuariodireccion_eliminado ='1'
			",
			"usuariodireccion_id = '$get_id'  ");

		}else{

			$resultado = $conexion->doUpdate("usuariodireccion", "
			usuariodireccion_activo ='0',
			usuariodireccion_eliminado ='1'
			",
			"usuario_id='$usuario_id' and usuariodireccion_id = '$get_id'  ");
		}


		

	}	

	if($resultado){
		
		$valores = array(
			"code" => 0,
			"data" => [],
			"message" => "Eliminado correctamente"
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