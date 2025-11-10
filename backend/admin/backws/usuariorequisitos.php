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

if ($metodo=="POST"){// Consultar Requisitos del Usuario

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
			"message" => "Token no encontrado. url: usuariorequisitos",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$arrresultado2 = $conexion->doSelect("usuario.usuario_id, usuario.cuenta_id,
	estatus.lista_cod as estatus_cod,
	estatus.lista_nombre as estatus_nombre
	",
	"usuario
		left join lista estatus on estatus.lista_id = usuario.l_estatus_id
	",
	"usuario.usuario_activo = '1' and usuario.usuario_codverif = '$token' and usuario.compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);	
		$estatus_nombreusuario = utf8_encode($valor2["estatus_nombre"]);
		$estatus_codusuario = utf8_encode($valor2["estatus_cod"]);
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuariorequisitos",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$devolverresultado = 1;
	$arrresultado = ListadoRequisitoUsuario($cuenta_id, $compania_id, null, null, null, null, null, 1, $usuario_id);	
			
	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$requisito_id = utf8_encode($valor["requisito_id"]);
			$l_requisitolista_id = utf8_encode($valor["l_requisitolista_id"]);
			$requisitolista_cod = utf8_encode($valor["requisitolista_cod"]);

			
			$requisito_descrip = utf8_encode($valor["requisito_descrip"]);
			$l_tipoarchivo_id = utf8_encode($valor["l_tipoarchivo_id"]);
			$requisito_arch = utf8_encode($valor["requisito_arch"]);
			$requisito_archnombre = utf8_encode($valor["requisito_archnombre"]);
			$requisito_cantarchivos = utf8_encode($valor["requisito_cantarchivos"]);
			$t_cuenta_id = utf8_encode($valor["cuenta_id"]);
			$t_compania_id = utf8_encode($valor["compania_id"]);
			$requisito_activo = utf8_encode($valor["requisito_activo"]);
			$requisito_fechareg = utf8_encode($valor["requisito_fechareg"]);
			$l_estatus_id = utf8_encode($valor["l_estatus_id"]);
			$usuario_id = utf8_encode($valor["usuario_id"]);
			$usuario_codigo = utf8_encode($valor["usuario_codigo"]);
			$usuario_email = utf8_encode($valor["usuario_email"]);
			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
	  
			$usuario = $usuario_nombre." ".$usuario_apellido." ";
			
			$requisitolista_id = utf8_encode($valor["requisitolista_id"]);
			$requisitolista_nombre = utf8_encode($valor["requisitolista_nombre"]);
			$requisitolista_descrip = utf8_encode($valor["requisitolista_descrip"]);
			$requisitolista_img = utf8_encode($valor["requisitolista_img"]);
			
			
			$estatus_cod = utf8_encode($valor["estatus_cod"]);
			$estatus_nombre = utf8_encode($valor["estatus_nombre"]);
			$estatus_color = utf8_encode($valor["estatus_color"]);
			
			$tipoarchivo_nombre = utf8_encode($valor["tipoarchivo_nombre"]);
			$tipoarchivo_img = utf8_encode($valor["tipoarchivo_img"]);	
			
			$imagen = ObtenerUrlArch($compania_id)."/$requisitolista_img";
			
			$data = array(
				"id" => $requisito_id,				
				"codigo" => $requisitolista_cod,
				"nombre" => $requisitolista_nombre,
				"descripcion" => $requisitolista_descrip,
				"estatuscod" => $estatus_cod,
				"estatusnombre" => $estatus_nombre,
				"estatuscolor" => $estatus_color,
				"imagen" => $imagen
			);
	
			array_push($datatotal, $data);

		}

		$dataReturn = array(
			"items" => $datatotal,
			"total" => $total,
			"estatuscod" => $estatus_codusuario,
			"estatusnombre" => $estatus_nombreusuario,
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