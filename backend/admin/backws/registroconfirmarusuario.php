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
include('../vendor/autoload.php');


$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo=="POST"){// registro registroconfirmarusuario

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
    (isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}

    $conexion = new ConexionBd();

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

    $arrresultado2 = $conexion->doSelect("usuario_id","usuario","usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
	}

	if($usuario_id==""){

		$valores = array(
			"code" => 101,
            "token" => $token,
            "compania_id" => $compania_id,
			"message" => "Error: El usuario no esta conectado"
		);


	}else{

		// PRIMERO: Obtener datos completos del usuario (incluido su perfil)
		$arrresultado2 = $conexion->doSelect("usuario_id, usuario_nombre, usuario_apellido, usuario_email, usuario_codverif, usuario_qrimg, perfil.perfil_id, perfil.perfil_idorig, usuario_whatsapp, usuario_emailverif, usuario_alias,
		estatus.lista_cod as estatus_cod,
		estatus.lista_nombre as estatus_nombre,
        usuario_codreferido, compania_nombre, compania_email, compania.compania_id,
		ciudad.lista_nombre as ciudad_nombre, usuario.l_tipousuarioserv_id,
        usuario.cuenta_id, usuario_img
        ",
        "usuario
            inner join perfil on perfil.perfil_id = usuario.perfil_id
            inner join lista estatus on estatus.lista_id = usuario.l_estatus_id
            inner join compania on compania.compania_id = usuario.compania_id
            left join lista ciudad on ciudad.lista_id = usuario.l_ciudad_id
        ", "usuario.usuario_id = '$usuario_id'");

		$perfil_idorig = 4; // Por defecto cliente
		if (count($arrresultado2)>0){
			foreach($arrresultado2 as $i=>$valor){
                $cuenta_id = utf8_encode($valor["cuenta_id"]);
                $tipousuario = utf8_encode($valor["l_tipousuarioserv_id"]);
				$compania_nombre = utf8_encode($valor["compania_nombre"]);
                $compania_email = utf8_encode($valor["compania_email"]);
                $usuario_id = utf8_encode($valor["usuario_id"]);
				$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
                $usuario_apellido = utf8_encode($valor["usuario_apellido"]);
				$usuario_email = utf8_encode($valor["usuario_email"]);
                $usuario_alias = utf8_encode($valor["usuario_alias"]);
                $usuario_img = utf8_encode($valor["usuario_img"]);
				$perfil_idorig = utf8_encode($valor["perfil_idorig"]); // Guardar el perfil original

				$usuario_img = ObtenerUrlArch($compania_id)."/$usuario_img";
                $usuario_qrimg = utf8_encode($valor["usuario_qrimg"]);
                $perfil_id = utf8_encode($valor["perfil_id"]);
                $usuario_fechareg = utf8_encode($valor["usuario_fechareg"]);
                $usuario_whatsapp = utf8_encode($valor["usuario_whatsapp"]);
                $usuario_emailverif = utf8_encode($valor["usuario_emailverif"]);
                $estatus_cod = utf8_encode($valor["estatus_cod"]);
				$estatus_nombre = utf8_encode($valor["estatus_nombre"]);
                $usuario_codreferido = utf8_encode($valor["usuario_codreferido"]);
                $ciudad_nombre = utf8_encode($valor["ciudad_nombre"]);
				$usuario_codverif = utf8_encode($valor["usuario_codverif"]);
				$token = $usuario_codverif;

                $usuarioqrimagen = ObtenerUrlArch($compania_id)."/$usuario_qrimg";
			}
		}

		// SEGUNDO: Cargar requisitos usando el perfil correcto del usuario
		$instancialista = new Lista();
		$totalrequisitos = cargarrequisitosEnFunciones($compania_id, $fechaactual, $usuario_id, $perfil_idorig);

		// TERCERO: Actualizar estatus del usuario a "Cargar Requisitos"
		$obtenerCodigoLista = 4; // Cargar Requisitos
		$obtenerTipoLista = 53; // Estatus de Usuarios
		$estatususuario = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

		$resultado = $conexion->doUpdate("usuario",
		"
		 	usuario_emailverif = '0',
			l_estatus_id ='$estatususuario'
		",
		"usuario_id ='$usuario_id'");

		// Actualizar estatus_cod y estatus_nombre con el nuevo estado
		$estatus_cod = "4";
		$estatus_nombre = "Cargar Requisitos";

		$data = array(
			"nombre" => $usuario_nombre,
			"apellido" => $usuario_apellido,
			"email" => $usuario_email,
			"imagen" => $usuario_img,
			"alias" => $usuario_alias,                    
			"ciudad" => $ciudad_nombre,	                    
			"idusuario" => $usuario_id,	
			"id" => $usuario_id,	
			"usuarioqr" => $usuarioqrimagen,	
			"verificado" => $verificado,
			"perfil" => $perfil_idorig,
			"perfilact" => $perfil_id,
			"fecharegistro" => $usuario_fechareg,
			"whatsapp" => $usuario_whatsapp,
			"referidocodigo" => $usuario_codreferido,
			"estatuscod" => $estatus_cod,
			"estatusnombre" => $estatus_nombre,
			"tipousuario" => $tipousuario,
			"token" => $token               
		);

		$valores = array(
			"code" => 0,
			"data" => $data,
			"message" => "Usuario confirmado"
		);	

	}

}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>