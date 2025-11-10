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



if ($metodo=="POST"){// Consultar Direcciones

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}

	$conexion = new ConexionBd();
	
	$arrresultado = $conexion->doSelect("

	usuariodireccion.usuariodireccion_id, usuariodireccion.usuario_id, usuariodireccion.l_pais_id, 
	usuariodireccion.usuariodireccion_direccion, usuariodireccion.usuariodireccion_estado, 
	usuariodireccion.usuariodireccion_ciudad, usuariodireccion.usuariodireccion_codpostal, 
	usuariodireccion.usuariodireccion_observacion, usuariodireccion.usuariodireccion_fechareg, 
	usuariodireccion.usuariodireccion_activo, usuariodireccion.usuariodireccion_eliminado,
	usuario.usuario_nombre, usuario.usuario_apellido, pais_nombre
	",
	"usuariodireccion    
		inner join usuario on usuariodireccion.usuario_id = usuario.usuario_id
		inner join pais on pais.pais_id = usuariodireccion.l_pais_id	
	",
	"usuariodireccion_activo = '1' and usuario.compania_id = '$compania_id' ", null, "usuariodireccion.usuariodireccion_id desc LIMIT 5");
	
	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
	
			$usuario = $usuario_nombre." ".$usuario_apellido." ";
	
			$usuariodireccion_id = utf8_encode($valor["usuariodireccion_id"]);
			$l_pais_id = utf8_encode($valor["l_pais_id"]);
	
			$pais_nombre = utf8_encode($valor["pais_nombre"]);
			$usuariodireccion_direccion = utf8_encode($valor["usuariodireccion_direccion"]);
			$usuariodireccion_estado = utf8_encode($valor["usuariodireccion_estado"]);
			$usuariodireccion_ciudad = utf8_encode($valor["usuariodireccion_ciudad"]);
			$usuariodireccion_codpostal = utf8_encode($valor["usuariodireccion_codpostal"]);
			$usuariodireccion_observacion = utf8_encode($valor["usuariodireccion_observacion"]);
			$usuariodireccion_fechareg = utf8_encode($valor["usuariodireccion_fechareg"]);	

			$data = array(
				"usuario" => $usuario,
				"pais_id" => $pais_id,		
				"pais_nombre" => $pais_nombre,		
				"usuariodireccion_direccion" => $usuariodireccion_direccion,		
				"usuariodireccion_estado" => $usuariodireccion_estado,		
				"usuariodireccion_ciudad" => $usuariodireccion_ciudad,		
				"usuariodireccion_codpostal" => $usuariodireccion_codpostal,		
				"usuariodireccion_observacion" => $usuariodireccion_observacion,		
			);

			array_push($datatotal, $data);
		
		}
		
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