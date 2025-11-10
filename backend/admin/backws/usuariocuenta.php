<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');


ini_set ("display_errors","0");

include_once '../lib/funciones.php';
include_once '../lib/mysqlclass.php';
include_once '../lib/phpmailer/libemail.php';

$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo=="POST"){// Consultar Cuentas del usuario

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}
	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: usuariocuenta",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}


	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$arrresultado2 = $conexion->doSelect("usuario_id","usuario","usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuariocuenta",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}
	
	$formatofechaSQL = formatofechaSQL($compania_id);
	
	$arrresultado = $conexion->doSelect("usuario.usuario_id, usuario.usuario_codigo, usuario.usuario_email, usuario.usuario_clave, usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_telf, usuario.usuario_fechareg, usuario.usuario_activo, usuario.usuario_eliminado, usuario.usuario_documento, usuario.usuario_img, usuario.perfil_id, usuario.usuario_direccion, 
	    DATE_FORMAT(usuario.usuario_fechanac,'$formatofechaSQL') as usuario_fechanac,
	    usuario.l_tipodocumento_id, usuario.cuenta_id, usuario.compania_id, usuario.perfil_id, 
	    usuario.usuario_whatsapp, usuario.l_estatus_id,

	    usuarioretiro.usuarioretiro_id, usuarioretiro.usuarioretiro_descripcion, usuarioretiro.usuarioretiro_email, 
	    usuarioretiro.usuarioretiro_fechareg, usuarioretiro.usuarioretiro_activo, 
	    usuarioretiro.usuarioretiro_eliminado, usuarioretiro.l_formapago_id, usuarioretiro.usuarioretiro_banco, 
	    usuarioretiro.usuarioretiro_titular, usuarioretiro.usuarioretiro_tipocuenta, usuarioretiro.usuarioretiro_documento, 
	    usuarioretiro.usuarioretiro_nrocuenta,

	    lista_nombre as formapago_nombre, lista_img as formapago_img,

	    cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
    	cuenta.usuario_apellido as cuenta_apellido, compania_nombre

	    ",
		"usuario	
			inner join usuarioretiro on usuarioretiro.usuario_id = usuario.usuario_id
			inner join lista formapago on formapago.lista_id = usuarioretiro.l_formapago_id
			inner join usuario cuenta on cuenta.usuario_id = usuario.cuenta_id
			inner join compania on compania.compania_id = usuario.compania_id

		",
		"usuario.usuario_eliminado = '0' and usuarioretiro_eliminado = '0' AND usuario.usuario_id = '$usuario_id' ", 
		null, "usuarioretiro.usuarioretiro_id desc");

	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$usuarioretiro_id = utf8_encode($valor["usuarioretiro_id"]);
			$usuarioretiro_descripcion = utf8_encode($valor["usuarioretiro_descripcion"]);
			$usuarioretiro_email = utf8_encode($valor["usuarioretiro_email"]);
			$usuarioretiro_fechareg = utf8_encode($valor["usuarioretiro_fechareg"]);
			$usuarioretiro_activo = utf8_encode($valor["usuarioretiro_activo"]);		
			$l_formapago_id = utf8_encode($valor["l_formapago_id"]);
			$usuarioretiro_banco = utf8_encode($valor["usuarioretiro_banco"]);
			$usuarioretiro_titular = utf8_encode($valor["usuarioretiro_titular"]);
			$usuarioretiro_tipocuenta = utf8_encode($valor["usuarioretiro_tipocuenta"]);
			$usuarioretiro_documento = utf8_encode($valor["usuarioretiro_documento"]);
			$usuarioretiro_nrocuenta = utf8_encode($valor["usuarioretiro_nrocuenta"]);		

			$formapago_nombre = utf8_encode($valor["formapago_nombre"]);	
			$formapago_img = utf8_encode($valor["formapago_img"]);	

			$imagen = ObtenerUrlArch($compania_id)."/$formapago_img";
			

			$l_estatus_id = utf8_encode($valor["l_estatus_id"]);
			$usuario_whatsapp = utf8_encode($valor["usuario_whatsapp"]);
			$cuenta_id = utf8_encode($valor["cuenta_id"]);
			$t_compania_id = utf8_encode($valor["compania_id"]);
			$perfil_id = utf8_encode($valor["perfil_id"]);
			$usuario_id = utf8_encode($valor["usuario_id"]);
			$usuario_codigo = utf8_encode($valor["usuario_codigo"]);
			$usuario_email = utf8_encode($valor["usuario_email"]);
			$usuario_clave = utf8_encode($valor["usuario_clave"]);
			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_telf = utf8_encode($valor["usuario_telf"]);
			$usuario_fechareg = utf8_encode($valor["usuario_fechareg"]);
			$usuario_activo = utf8_encode($valor["usuario_activo"]);
			$usuario_documento = utf8_encode($valor["usuario_documento"]);	
			$t_perfil_id = utf8_encode($valor["perfil_id"]);
			$usuario_direccion = utf8_encode($valor["usuario_direccion"]);
			$usuario_localidad = utf8_encode($valor["usuario_localidad"]);
			$usuario_actividad = utf8_encode($valor["usuario_actividad"]);
			$t_nacionalidad_id = utf8_encode($valor["nacionalidad_id"]);
			$e_tipocliente_id = utf8_encode($valor["e_tipocliente_id"]);
			$t_sexo_id = utf8_encode($valor["sexo_id"]);
			$e_estadocivil_id = utf8_encode($valor["e_estadocivil_id"]);	
			$usuario_conyugenombre = utf8_encode($valor["usuario_conyugenombre"]);
			$usuario_conyugetelf = utf8_encode($valor["usuario_conyugetelf"]);
			$usuario_pariente = utf8_encode($valor["usuario_pariente"]);
			$usuario_parientetelf = utf8_encode($valor["usuario_parientetelf"]);
			$e_parentesco_id = utf8_encode($valor["e_parentesco_id"]);
			$usuario_telfdos = utf8_encode($valor["usuario_telfdos"]);
			$usuario_notas = utf8_encode($valor["usuario_notas"]);
			$usuario_porcentajecolocador = utf8_encode($valor["usuario_porcentajecolocador"]);
			$usuario_balance = utf8_encode($valor["usuario_balance"]);
			$usuario_comentarios = utf8_encode($valor["usuario_comentarios"]);
			$usuario_tasapref = utf8_encode($valor["usuario_tasapref"]);
			$l_tipodocumento_id = utf8_encode($valor["l_tipodocumento_id"]);
			$usuario_fechanac = utf8_encode($valor["usuario_fechanac"]);

			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_img = utf8_encode($valor["usuario_img"]);
			$compania_nombre = utf8_encode($valor["compania_nombre"]);

			$usuario = $usuario_nombre." ".$usuario_apellido." ";

			$cuenta_nombre = utf8_encode($valor["cuenta_nombre"]);
			$cuenta_apellido = utf8_encode($valor["cuenta_apellido"]);
			$cuenta_codigo = utf8_encode($valor["cuenta_codigo"]);
			$cuenta = $cuenta_nombre." ".$cuenta_apellido." ";

			if ($usuarioretiro_activo=="0"){
				$activo = "<i onclick='cambiarestatusopcionretiro(\"".$usuarioretiro_id."\",1)' title='Deshabilitar' class='fa fa-minus btn-deshabilitar'></i>";
			}else{
				$activo = "<i onclick='cambiarestatusopcionretiro(\"".$usuarioretiro_id."\",0)' title='Habilitar' class='fa fa-check btn-habilitar'></i>";
			}
			
			$accioneliminar = "<i onclick='eliminaropcionretiro(\"".$usuarioretiro_id."\",0)' title='Eliminar?' class='fa fa-trash btn-eliminar'></i>";

			
			$modificar = "<a href='modificaropcionretiro?id=$usuarioretiro_id'><i title='Ver' class='fa fa-edit btn-modificar'></i></a>";

			
			if (P_Mod!="1"){$modificar = ""; $activo = "";}
			if (P_Eli!="1"){$accioneliminar = ""; $activo = "";}

			$mostrarcolumnacuenta = "<td>$cuenta</td>";
			$mostrarcolumnacompania = "<td>$compania_nombre</td>";

			if ($_COOKIE[perfil]=="1"){ 			
				
			}
			else if ($_COOKIE[perfil]=="2"){ 			
				$mostrarcolumnacuenta = "";
			}
			else { 			
				$mostrarcolumnacuenta = "";
				$mostrarcolumnacompania = "";	
			}
			

			$textohtml .= "
						<tr>
							$mostrarcolumnacuenta
							$mostrarcolumnacompania				          
							<td>$usuario</td>
							<td style='text-align: center'>$formapago_nombre</td>
							<td style='text-align: center'>$usuarioretiro_email</td>
							<td style='text-align: center'>$modificar &nbsp $activo &nbsp $accioneliminar</td>
						</tr>
					";

			$data = array(
				"id" => $usuarioretiro_id,
				"formapago" => $formapago_nombre,	
				"imagen" => $imagen,		
				"email" => $usuarioretiro_email,
				"nrocuenta" => $usuarioretiro_nrocuenta		
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