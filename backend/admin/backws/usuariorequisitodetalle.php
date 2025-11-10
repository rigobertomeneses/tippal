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
	(isset($valoresPost['requisito'])) ? $requisito=$valoresPost['requisito'] :$requisito='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}
	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: usuariorequisitodetalle",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$arrresultado2 = $conexion->doSelect("usuario_id, cuenta_id","usuario","usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);					
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuariorequisitodetalle",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$devolverresultado = 1;
	//$arrresultado = ListadoRequisitoUsuario($cuenta_id, $compania_id, null, null, null, null, null, 1, $usuario_id);	

	$arrresultado = ListadoRequisitoUsuario($cuenta_id, $compania_id, null, null, null, null, $requisito, 1, $usuario_id);
			
	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$requisito_id = utf8_encode($valor["requisito_id"]);
			$l_requisitolista_id = utf8_encode($valor["l_requisitolista_id"]);
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
			
			
			$estatusrequisito_cod = utf8_encode($valor["estatus_cod"]);
			$estatusrequisito_nombre = utf8_encode($valor["estatus_nombre"]);
			$tipoarchivo_nombre = utf8_encode($valor["tipoarchivo_nombre"]);
			$tipoarchivo_img = utf8_encode($valor["tipoarchivo_img"]);	
			
			$imagen = ObtenerUrlArch($compania_id)."/$requisitolista_img";
			
		}

		$arrresultado = $conexion->doSelect("
			requisito.requisito_id, requisito.l_requisitolista_id, requisito_descrip, requisito_arch, 
			requisito_archnombre, requisito_cantarchivos, requisito.cuenta_id, requisito.compania_id,
			requisito_activo, requisito_eliminado, requisito_fechareg, 
			requisito.usuario_idreg, requisito.l_estatus_id, requisito.usuario_id,
			usuario.usuario_id, usuario.usuario_codigo, usuario.usuario_email, usuario.usuario_nombre, usuario.usuario_apellido, 
			usuario.usuario_telf, usuario.usuario_activo, usuario.usuario_eliminado, 
			usuario.usuario_documento, usuario.usuario_img, usuario.perfil_id, 

			cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
			cuenta.usuario_apellido as cuenta_apellido, compania_nombre, compania_urlweb,
			listarequisito.lista_id as requisitolista_id,
			listarequisito.lista_nombre as requisitolista_nombre,
			estatus.lista_nombre as estatus_nombre,
			tipoarchivo.lista_nombre as tipoarchivo_nombre,
			tipoarchivo.lista_img as tipoarchivo_img,

			requisitoarchivo.requisitoarch_id, requisitoarchivo.requisitoarch_arch, requisitoarch_nombre,
			requisitoarchivo.l_tipoarchivo_id, requisitoarchivo.requisitoarch_activo,
			DATE_FORMAT(requisitoarchivo.requisitoarch_fechareg,'$formatofechaSQL %H:%i:%s') as requisitoarch_fechareg

			",
			"lista listarequisito
				inner join requisito on requisito.l_requisitolista_id = listarequisito.lista_id
				left join requisitoarchivo on requisitoarchivo.requisito_id = requisito.requisito_id and requisitoarch_eliminado = '0'
				left join compania on compania.compania_id = requisito.compania_id

				left join lista tipoarchivo on tipoarchivo.lista_id = requisito.l_tipoarchivo_id
				left join lista estatus on estatus.lista_id = requisito.l_estatus_id
				left join usuario on usuario.usuario_id = requisito.usuario_id
				left join usuario cuenta on cuenta.usuario_id = requisito.cuenta_id
				

			",
			"listarequisito.lista_activo = '1' and requisito.requisito_id = '$requisito_id' ", null, "listarequisito.lista_orden asc");
		foreach($arrresultado as $i=>$valor){

			$requisitoarch_id = utf8_encode($valor["requisitoarch_id"]);
			$requisitoarch_arch = utf8_encode($valor["requisitoarch_arch"]);
			$requisitoarch_nombre = utf8_encode($valor["requisitoarch_nombre"]);
			$l_tipoarchivo_id = utf8_encode($valor["l_tipoarchivo_id"]);    

			$requisito_id = utf8_encode($valor["requisito_id"]);

			$l_requisitolista_id = utf8_encode($valor["l_requisitolista_id"]);
			$requisito_descrip = utf8_encode($valor["requisito_descrip"]);
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

			$compania_urlweb = utf8_encode($valor["compania_urlweb"]);
			
			
			$requisitolista_id = utf8_encode($valor["requisitolista_id"]);
			$requisitolista_nombre = utf8_encode($valor["requisitolista_nombre"]);
			$estatus_nombre = utf8_encode($valor["estatus_nombre"]);
			$tipoarchivo_nombre = utf8_encode($valor["tipoarchivo_nombre"]);
			$tipoarchivo_img = utf8_encode($valor["tipoarchivo_img"]);
			$requisitoarch_activo = utf8_encode($valor["requisitoarch_activo"]);
			$requisitoarch_fechareg = utf8_encode($valor["requisitoarch_fechareg"]);



			$urlarchivo = $compania_urlweb."$requisitoarch_arch";
			

			$displaydocumentos = " ";

			$labeladjunto = "Archivo Adjunto:";

			if ($l_tipoarchivo_id=="58"){

				$labeladjunto = "$tipoarchivo_nombre Adjunto:";
				$agregarchivoadjunto = "
					<a href='$urlarchivo' target='_blank'>
						<img src='dist/img/tipoarchivo/".$l_tipoarchivo_id.".jpg' style='height: 60px' />
						<br>
						$requisitoarch_nombre
					</a>
				";
			}else if ($l_tipoarchivo_id=="59"){

				$labeladjunto = "$tipoarchivo_nombre Adjunto:";
				$agregarchivoadjunto = "
					<a href='$urlarchivo' target='_blank'>
						<img src='dist/img/tipoarchivo/".$l_tipoarchivo_id.".jpg' style='height: 60px' />
						<br>
						$requisitoarch_nombre
					</a>
				";
			}else if ($l_tipoarchivo_id=="60"){

				$labeladjunto = "$tipoarchivo_nombre Adjunto:";
				$agregarchivoadjunto = "
					<a href='$urlarchivo' target='_blank'>
						<img src='dist/img/tipoarchivo/".$l_tipoarchivo_id.".jpg' style='height: 60px' />
						<br>
						$requisitoarch_nombre 
					</a>
				";
			}else if ($l_tipoarchivo_id=="61"){
				$labeladjunto = "$tipoarchivo_nombre Adjunto:";
				$agregarchivoadjunto = "
					<a class='fancybox' href='$urlarchivo' data-fancybox-group='gallery'  target='_blank'>
						<img class='img-responsive' src='$urlarchivo' style='max-height: 100px; max-width:200px; border-radius: 10px' />
						<br>
						$requisitoarch_nombre
					</a>
				";
			}else if ($l_tipoarchivo_id=="62"){

				$labeladjunto = "$tipoarchivo_nombre Adjunto:";
				$agregarchivoadjunto = "
					<a href='$urlarchivo' target='_blank'>
						<img src='dist/img/tipoarchivo/".$l_tipoarchivo_id.".jpg' style='height: 60px' />
						<br>
						$requisitoarch_nombre
					</a>
				";
			}
			else{
				$agregarchivoadjunto = "
					<a href='$urlarchivo' target='_blank'>
						$requisitoarch_nombre
					</a>
				";
			}

			if ($requisitoarch_activo=="0"){
			$activo = "<i onclick='cambiarestatusrequisitoarchivo(\"".$requisitoarch_id."\",1)' title='Deshabilitar' class='fa fa-minus btn-deshabilitar'></i>";
			}else{
			$activo = "<i onclick='cambiarestatusrequisitoarchivo(\"".$requisitoarch_id."\",0)' title='Habilitar' class='fa fa-check btn-habilitar'></i>";
			}
			
			$accioneliminar = "<i onclick='eliminarrequisitoarchivo(\"".$requisitoarch_id."\",0)' title='Eliminar?' class='fa fa-trash btn-eliminar'></i>";

			if (P_Mod!="1"){$modificar = ""; $activo = "";}
			if (P_Eli!="1"){$accioneliminar = ""; $activo = "";}


			$htmldetallehtml .= "
					<tr>			          					
						<td style='text-align: center'><center>$agregarchivoadjunto</center> </td>															
						<td style='text-align: center'>$requisitoarch_fechareg</td>						
						<td style='text-align: center'>$activo &nbsp $accioneliminar</td>
					</tr>
				";

			$imagen = ObtenerUrlArch($compania_id)."/$requisitolista_img";
			$requisitoarch_arch_imagenurl = ObtenerUrlArch($compania_id)."/$requisitoarch_arch";

			if ($requisitoarch_id!=""){
				$cargados = array(
					"id" => $requisitoarch_id,
					"nombre" => $requisitoarch_nombre,
					"fecha" => $requisitoarch_fechareg,
					"archivo" => $requisitoarch_arch,
					"imagen" => $requisitoarch_arch_imagenurl
				);
	
				array_push($datatotal, $cargados);
			}

			

		}


		$dataReturn = array(
			"id" => $requisito_id,
			"nombre" => $requisitolista_nombre,
			"descripcion" => $requisitolista_descrip,
			"estatuscod" => $estatusrequisito_cod,
			"estatusnombre" => $estatusrequisito_nombre,
			"imagen" => $imagen,
			"cargados" => $datatotal
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