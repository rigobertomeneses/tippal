<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');


ini_set ("display_errors","0");

include_once '../../lib/mysqlclass.php';
include_once '../../lib/phpmailer/libemail.php';
include_once '../../lib/funciones.php';

$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo=="POST"){// Preguntas frecuentes

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}

	$conexion = new ConexionBd();

	$formatofechaSQL = formatofechaSQL($compania_id);

	$arrresultado = $conexion->doSelect("
		pregunta.preg_id, pregunta.preg_nombre, pregunta.preg_respuesta, pregunta.preg_img, 
		pregunta.preg_videourl, pregunta.preg_videocodigo, pregunta.preg_url, 
		pregunta.l_tiposeccion_id, pregunta.cuenta_id, pregunta.compania_id, 
		pregunta.preg_activo, pregunta.preg_eliminado, 
		pregunta.preg_orden, pregunta.usuario_idreg,
		tiposeccion.lista_nombre as tiposeccion_nombre,
		DATE_FORMAT(pregunta.preg_fechareg,'$formatofechaSQL %H:%i:%s') as preg_fechareg,				
		cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
		cuenta.usuario_apellido as cuenta_apellido, compania_nombre
		",
		"pregunta						
			inner join usuario cuenta on cuenta.usuario_id = pregunta.cuenta_id
		    inner join compania on compania.compania_id = pregunta.compania_id		    
		    left join lista tiposeccion on tiposeccion.lista_id = pregunta.l_tiposeccion_id	
		",
		"preg_eliminado = '0' and pregunta.compania_id = '$compania_id' ", null, "preg_orden asc");
	
	$total = count($arrresultado);			

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){


			$tiposeccion_nombre = utf8_encode($valor["tiposeccion_nombre"]);
			$preg_id = utf8_encode($valor["preg_id"]);
			$preg_nombre = utf8_encode($valor["preg_nombre"]);
			$preg_respuesta = utf8_encode($valor["preg_respuesta"]);
			$preg_img = utf8_encode($valor["preg_img"]);
			$preg_videourl = utf8_encode($valor["preg_videourl"]);
			$preg_videocodigo = utf8_encode($valor["preg_videocodigo"]);
			$preg_url = utf8_encode($valor["preg_url"]);
			$l_tiposeccion_id = utf8_encode($valor["l_tiposeccion_id"]);
			$preg_activo = utf8_encode($valor["preg_activo"]);
			$preg_fechareg = utf8_encode($valor["preg_fechareg"]);
			$preg_orden = utf8_encode($valor["preg_orden"]);		
	
			$t_cuenta_id = utf8_encode($valor["cuenta_id"]);
			$t_compania_id = utf8_encode($valor["compania_id"]);

			$preg_img = ObtenerUrlArch($compania_id)."/$preg_img";
	
			$data = array(
				"preg_id" => $preg_id,
				"preg_nombre" => $preg_nombre,		
				"preg_respuesta" => $preg_respuesta,		
				"preg_img" => $preg_img,		
				"preg_videourl" => $preg_videourl,		
				"preg_videocodigo" => $preg_videocodigo
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