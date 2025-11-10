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

$valoresPost = json_decode(file_get_contents('php://input'), true);

(isset($valoresPost['id'])) ? $get_id=$valoresPost['id'] :$get_id='';
(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
(isset($valoresPost['usuario'])) ? $usuario=$valoresPost['usuario'] :$usuario='';
(isset($valoresPost['array'])) ? $array=$valoresPost['array'] :$array ='';
if ($compania_id==""){
	$compania_id = 0;
}

if ($token==""){
	$valores = array(
		"code" => 104,
		"message" => "Token no encontrado. url: direcciones",
		"data" => [],
	);

	$resultado = json_encode($valores);
	echo $resultado;
	exit();
}
$conexion = new ConexionBd();

$arrresultado2 = $conexion->doSelect("usuario_id, perfil_idorig",
"usuario
	inner join perfil on perfil.perfil_id = usuario.perfil_id	
",
"usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id' ");
foreach($arrresultado2 as $n=>$valor2){	      
	$usuario_id = utf8_encode($valor2["usuario_id"]);
	$perfil_idorig = utf8_encode($valor2["perfil_idorig"]);					
}

if ($usuario_id==""){
	$valores = array(
		"code" => 103,
		"message" => "Usuario / Token no activo. url: direcciones",
		"data" => [],
	);

	$resultado = json_encode($valores);
	echo $resultado;
	exit();
}

if ($get_id!=""){
	$where .= " and usuariodireccion.usuariodireccion_id = '$get_id'";
}
else if ($usuario!=""){
	$where .= " and usuariodireccion.usuario_id = '$usuario'";
}
else{
	$where .= " and usuariodireccion.usuario_id = '$usuario_id'";
}

$arrresultado = $conexion->doSelect("
usuariodireccion.usuariodireccion_id, usuariodireccion.usuario_id, usuariodireccion.l_pais_id, 
usuariodireccion.usuariodireccion_direccion, usuariodireccion.usuariodireccion_estado, 
usuariodireccion.usuariodireccion_ciudad, usuariodireccion.usuariodireccion_codpostal, 
usuariodireccion.usuariodireccion_observacion, usuariodireccion.usuariodireccion_fechareg, 
usuariodireccion.usuariodireccion_activo, usuariodireccion.usuariodireccion_eliminado,
usuario.usuario_nombre, usuario.usuario_apellido, pais_nombre,
X(usuariodireccion.usuariodireccion_point) as latitud,
Y(usuariodireccion.usuariodireccion_point) as longitud,
usuariodireccion.usuariodireccion_dirmapa, 
usuariodireccion.usuariodireccion_contactonombre, 
usuariodireccion.usuariodireccion_contactotelf
",
"usuariodireccion    
	inner join usuario on usuariodireccion.usuario_id = usuario.usuario_id
	left join pais on pais.pais_id = usuariodireccion.l_pais_id	
",
"usuariodireccion_activo = '1' and usuario.compania_id = '$compania_id' $where", null, "usuariodireccion.usuariodireccion_id desc");

$total = count($arrresultado);		

if($total>0){

	$datatotal = array();

	foreach($arrresultado as $i=>$valor){

		$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
		$usuario_apellido = utf8_encode($valor["usuario_apellido"]);

		$usuariocompleto = $usuario_nombre." ".$usuario_apellido." ";

		$usuariodireccion_id = utf8_encode($valor["usuariodireccion_id"]);
		$l_pais_id = utf8_encode($valor["l_pais_id"]);

		$pais_nombre = utf8_encode($valor["pais_nombre"]);
		$usuariodireccion_direccion = utf8_encode($valor["usuariodireccion_direccion"]);
		$usuariodireccion_estado = utf8_encode($valor["usuariodireccion_estado"]);
		$usuariodireccion_ciudad = utf8_encode($valor["usuariodireccion_ciudad"]);
		$usuariodireccion_codpostal = utf8_encode($valor["usuariodireccion_codpostal"]);
		$usuariodireccion_observacion = utf8_encode($valor["usuariodireccion_observacion"]);
		$usuariodireccion_fechareg = utf8_encode($valor["usuariodireccion_fechareg"]);	
		$latitud = utf8_encode($valor["latitud"]);	
		$longitud = utf8_encode($valor["longitud"]);	
		$usuariodireccion_dirmapa = utf8_encode($valor["usuariodireccion_dirmapa"]);	
		$usuariodireccion_contactonombre = utf8_encode($valor["usuariodireccion_contactonombre"]);	
		$usuariodireccion_contactotelf = utf8_encode($valor["usuariodireccion_contactotelf"]);	

		$data = array(			
			"id" => $usuariodireccion_id,
			"usuario" => $usuariocompleto,
			"pais_id" => $l_pais_id,		
			"pais_nombre" => $pais_nombre,		
			"usuariodireccion_direccion" => $usuariodireccion_direccion,		
			"usuariodireccion_estado" => $usuariodireccion_estado,		
			"usuariodireccion_ciudad" => $usuariodireccion_ciudad,		
			"usuariodireccion_codpostal" => $usuariodireccion_codpostal,		
			"usuariodireccion_observacion" => $usuariodireccion_observacion,
			"latitud" => $latitud,
			"longitud" => $longitud,
			"usuariodireccion_dirmapa" => $usuariodireccion_dirmapa,
			"usuariodireccion_contactonombre" => $usuariodireccion_contactonombre,
			"usuariodireccion_contactotelf" => $usuariodireccion_contactotelf
		);

		array_push($datatotal, $data);	
	}
	
	if ($usuario!="" && $array!="1"){
		$datatotal = array(			
			"id" => $usuariodireccion_id,
			"usuario" => $usuario,
			"pais_id" => $l_pais_id,		
			"pais_nombre" => $pais_nombre,		
			"usuariodireccion_direccion" => $usuariodireccion_direccion,		
			"usuariodireccion_estado" => $usuariodireccion_estado,		
			"usuariodireccion_ciudad" => $usuariodireccion_ciudad,		
			"usuariodireccion_codpostal" => $usuariodireccion_codpostal,		
			"usuariodireccion_observacion" => $usuariodireccion_observacion,
			"latitud" => $latitud,
			"longitud" => $longitud,
			"usuariodireccion_dirmapa" => $usuariodireccion_dirmapa,
			"usuariodireccion_contactonombre" => $usuariodireccion_contactonombre,
			"usuariodireccion_contactotelf" => $usuariodireccion_contactotelf
		);

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



$resultado = json_encode($valores);

echo $resultado;

exit();

?>