<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');


ini_set ("display_errors","0");

include_once '../lib/funciones.php';
include_once '../lib/mysqlclass.php';
include_once '../lib/phpmailer/libemail.php';
include_once '../models/lista.php';

$libemail = new LibEmail();  



include('../vendor/autoload.php');

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

$writer = new PngWriter(); 

$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

function formatoFechaHoraBd3($sinhora=null, $diasatras=null, $mesesrecorrer =null, $colocarletra=null) {

  $conexion = new ConexionBd();  
  
  date_default_timezone_set('America/Argentina/Buenos_Aires');  
  
  $fecha_actual = date("Y-m-d H:i:s");

  $tiempoMod = time();
  if($sinhora=="1"){
    if($mesesrecorrer!=""){          
      $fecha_actual = date("Y-m-d",strtotime($fecha_actual." $mesesrecorrer months"));
      if ($colocarletra!=""){$fecha_actual = str_replace(" ", "$colocarletra", $fecha_actual);}
      return $fecha_actual;
    }else if($diasatras!=""){      
      $fecha_actual = date("Y-m-d",strtotime($fecha_actual." - $diasatras days")); 
      if ($colocarletra!=""){$fecha_actual = str_replace(" ", "$colocarletra", $fecha_actual);}
      return $fecha_actual;
    }else{
      $fecha_actual = date("Y-m-d",$tiempoMod);  
      if ($colocarletra!=""){$fecha_actual = str_replace(" ", "$colocarletra", $fecha_actual);}
      return $fecha_actual;
    }
  }else if($diasatras!=""){
    if ($diasatras=="1"){
      $diasatras = 30;
    }
    $fecha_actual = date("Y-m-d H:i:s",strtotime($fecha_actual." - $diasatras days")); 
    if ($colocarletra!=""){$fecha_actual = str_replace(" ", "$colocarletra", $fecha_actual);}
    return $fecha_actual;
  }else if($mesesrecorrer!=""){    
    $fecha_actual = date("Y-m-d H:i:s",strtotime($fecha_actual." $mesesrecorrer months"));
    if ($colocarletra!=""){$fecha_actual = str_replace(" ", "$colocarletra", $fecha_actual);}
    return $fecha_actual;
  }else{
    $fecha_actual = date("Y-m-d H:i:s",$tiempoMod);
    if ($colocarletra!=""){$fecha_actual = str_replace(" ", "$colocarletra", $fecha_actual);}
    return $fecha_actual;
  }
}


$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo=="POST"){// Consultar Cliente

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);
	
	(isset($valoresPost['tipologin'])) ? $tipologin=$valoresPost['tipologin'] :$tipologin='';
	(isset($valoresPost['email'])) ? $email=$valoresPost['email'] :$email='';
	(isset($valoresPost['whatsapp'])) ? $whatsapp=$valoresPost['whatsapp'] :$whatsapp='';
	(isset($valoresPost['clave'])) ? $clave=$valoresPost['clave'] :$clave='';
	(isset($valoresPost['tipoapp'])) ? $tipoapp=$valoresPost['tipoapp'] :$tipoapp='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['tipousuario'])) ? $tipousuarioinicia=$valoresPost['tipousuario'] :$tipousuarioinicia='';
	(isset($valoresPost['usuario_defecto'])) ? $usuario_defecto=$valoresPost['usuario_defecto'] :$usuario_defecto='';

	
	if ($compania_id==""){
		$compania_id = 0;
	}

	if ($compania_id=="401"){
		$valores = array(
			"code" => 101,
			"message" => "No se encuentra la compañia",
			"data" => [],
		);

		$resultado = json_encode($valores);

		echo $resultado;

		exit();
	}

	$email = trim(utf8_decode($email));
	$clave = trim(utf8_decode($clave));

	if ($compania_id=="408"){
		$whatsapp = str_replace("+54", "", $whatsapp);
	}

	$whatsapp = str_replace("+", "", $whatsapp);
	$whatsapp = trim($whatsapp);

	if ($tipologin==""){
		$tipoingreso = "email";
	}else{
		$tipoingreso = $tipologin;
	}
	/*
	//$tipoingreso = "email";
	if ($whatsapp!=""){
		$tipoingreso = "whatsapp";
	}
	*/

/* 	
	echo "email:$email";
	echo "<br>";
	echo "whatsapp:$whatsapp";
	echo "<br>";
	echo "clave:$clave";
	echo "<br>";
	echo "compania_id:$compania_id";
	echo "<br>";
	exit();
 */
	
	if ($email=="1" && $clave=="1" && $compania_id!="452"){
		$email = "pasajero";
		$clave = "123";
		$tipoingreso = "email";
	}

	if ($email=="11" && $clave=="11" && $compania_id!="452"){
		$email = "conductor";
		$clave = "123";
		$tipoingreso = "email";
	}

	if ($email=="demousuario" && $clave=="demousuario"){
		$tipoingreso = "email";
	}

	if ($tipoapp=="mitvcare"){
		$whereperfil = " and (perfil.perfil_idorig in (2,3,7)) ";
	}

	// Validación: Si es usuario por defecto, no requerir clave
	$esUsuarioDefecto = ($usuario_defecto == "1" || $usuario_defecto == 1);

	if( ($email =="" && $whatsapp=="") || ($clave =="" && !$esUsuarioDefecto)){

		$valores = array(
			"code" => 101,
			"message" => "Error: Valores requeridos de Email y Clave"
		);

	}else{

		$fechaactual = formatoFechaHoraBd3();

		$conexion = new ConexionBd();

		

		$wheretipoingreso = " and usuario.usuario_email = '$email' ";
		if ($tipoingreso=="whatsapp"){
			$wheretipoingreso = " and (usuario.usuario_whatsapp = '$whatsapp' or usuario.usuario_whatsapp = '+$whatsapp'   ) ";
		}
		//$wheretipoingreso = " and (usuario.usuario_email = '$email' or usuario.usuario_whatsapp = '$whatsapp' ) ";

		$whereclave = " and (usuario.usuario_clave = BINARY '$clave' or usuario.usuario_clavereset = BINARY '$clave') ";

		// Si es usuario por defecto, no validar clave y agregar filtro usuario_defecto = 1
		if ($esUsuarioDefecto){
			$whereclave = " and usuario.usuario_defecto = '1' ";
		}

		if ($compania_id=="386" && $tipoapp!="mitvcare"){
			$whereclave = "";
		}

		$arrresultado = $conexion->doSelect("cuenta_id","compania","compania_id = '$compania_id'");
		foreach($arrresultado as $i=>$valor){
			$cuenta_id = utf8_encode($valor["cuenta_id"]);
		}

		$wherecompania = " and usuario.compania_id = '$compania_id' ";

		$formatofechaSQL = formatofechaSQL($compania_id);
		
		$arrresultado2 = $conexion->doSelect("usuario.usuario_alias, usuario.usuario_id, usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_codverif, usuario.usuario_emailverif, usuario.usuario_email, 
		DATE_FORMAT(usuario.usuario_fechareg,'$formatofechaSQL %H:%i:%s') as usuario_fechareg, 
		usuario.usuario_codpass,
		perfil.perfil_idorig, perfil.perfil_id, usuario_qr,
		compania_img, usuario.usuario_qrimg, usuario_whatsapp, usuario.cuenta_id,  
		usuario.compania_id, usuario_img,
		estatus.lista_cod as estatus_cod, 
		estatus.lista_nombre as estatus_nombre, 
        usuario_codreferido,
		usuario.usuario_clavereset, compania_nombre, usuario.usuario_idreferido,
		compania_email,
		ciudad.lista_nombre as ciudad_nombre, 
		usuario.l_tipousuarioserv_id,
		usuariobalance_id, usuariobalance_disponible,
		moneda.lista_nombredos as moneda_siglas,
		usuariobalance.l_moneda_id, usuario_defecto
		",
		"usuario
			inner join perfil on perfil.perfil_id = usuario.perfil_id
			inner join compania on compania.compania_id = usuario.compania_id
			left join lista estatus on estatus.lista_id = usuario.l_estatus_id
			left join lista ciudad on ciudad.lista_id = usuario.l_ciudad_id
			left join usuariobalance on usuariobalance.usuario_id = usuario.usuario_id
			and usuariobalance_activo = '1'
			left join lista moneda on moneda.lista_id = usuariobalance.l_moneda_id
		", 
		"usuario.usuario_activo = '1' and usuario.cuenta_id ='$cuenta_id' $wherecompania $wheretipoingreso $whereclave $whereperfil ");

		if (count($arrresultado2)>0){
			
			$inicioclavereset= 0;
			foreach($arrresultado2 as $i=>$valor){
				$usuariobalance_disponible = utf8_encode($valor["usuariobalance_disponible"]);
				$moneda_siglas = utf8_encode($valor["moneda_siglas"]);
				$demo = utf8_encode($valor["usuario_defecto"]);

				$usuario_codpass = utf8_encode($valor["usuario_codpass"]);

				if ($usuario_codpass!=""){
					$tienecodpass = true;
				}
	

				$tipousuario = utf8_encode($valor["l_tipousuarioserv_id"]);
				$ciudad_nombre = utf8_encode($valor["ciudad_nombre"]);
				$compania_email = utf8_encode($valor["compania_email"]);
				$usuario_idreferido = utf8_encode($valor["usuario_idreferido"]);
				$compania_id = utf8_encode($valor["compania_id"]);
				$compania_nombre = utf8_encode($valor["compania_nombre"]);
				$cuenta_id = utf8_encode($valor["cuenta_id"]);
				$usuario_id = utf8_encode($valor["usuario_id"]);
				$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
				$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
				$usuario_alias = utf8_encode($valor["usuario_alias"]);
				
				$usuario_codverif = utf8_encode($valor["usuario_codverif"]);
				$usuario_emailverif = utf8_encode($valor["usuario_emailverif"]);
				$usuario_fechareg = utf8_encode($valor["usuario_fechareg"]);
				$usuario_email = utf8_encode($valor["usuario_email"]);
				$perfil_idorig = utf8_encode($valor["perfil_idorig"]);
				$perfil_id = utf8_encode($valor["perfil_id"]);
				$usuario_qr = utf8_encode($valor["usuario_qr"]);
				$compania_img = utf8_encode($valor["compania_img"]);
				$compania_nombre = utf8_encode($valor["compania_nombre"]);
				$usuario_qrimg = utf8_encode($valor["usuario_qrimg"]);
				$usuario_whatsapp = utf8_encode($valor["usuario_whatsapp"]);
				$estatus_cod = utf8_encode($valor["estatus_cod"]);
				$estatus_nombre = utf8_encode($valor["estatus_nombre"]);
				$usuario_codreferido = utf8_encode($valor["usuario_codreferido"]);
				$usuario_clavereset = utf8_encode($valor["usuario_clavereset"]);
				$l_moneda_id = utf8_encode($valor["l_moneda_id"]);
				$usuario_img = utf8_encode($valor["usuario_img"]);

				if ($usuario_img==""){
					$usuario_img = "1.png";
				}
			
				$usuario_img = ObtenerUrlArch($compania_id)."/$usuario_img";
				
				// Generar URL completa para compania_img
				if ($compania_img != ""){
					$compania_img = ObtenerUrlArch($compania_id)."/$compania_img";
				}

				$usuariobalance_disponible = utf8_encode($valor["usuariobalance_disponible"]);
				$moneda_siglas = utf8_encode($valor["moneda_siglas"]);		

				$usuariobalance_disponible = number_format_personalizado($usuariobalance_disponible, $moneda_siglas, null, $compania_id);

				if ($usuario_clavereset==$clave){
					$inicioclavereset=1;
				}

				/*
				if ($perfil_idorig=="2"){
					$perfil_idorig = 4;
				}
				*/

				if ($perfil_idorig==""){
					$perfil_idorig = $perfil_id;
				}
			}

			$resultado = $conexion->doInsert("
				auditoria
				(usuario_id, modulo_id, auditoria_fechareg, auditoria_activo, auditoria_eliminado, compania_id)
				",
				"'$usuario_id', '0', '$fechaactual', '1','0', '$compania_id'");



			if ($usuario_idreferido>0){
                totalizarReferidos($usuario_idreferido, $compania_id);
            }

			verificarExisteUsuarioBalance($usuario_id, $compania_id);	

			if ($usuario_codreferido==""){
				$usuario_codreferido = generarId();
			}

			$compania_iddemo = "373";

			// Verifico que tenga vehiculo creado para el demo de conductor.
			$arrresultado2 = $conexion->doSelect("usuariovehiculo_id",
			"usuariovehiculo
				inner join usuario on usuario.usuario_id = usuariovehiculo.usuario_id", 
			"usuariovehiculo_activo = '1' and usuario.usuario_id = '$usuario_id' and usuario.compania_id = '$compania_iddemo'"); // Solo para el demo
			if (count($arrresultado2)==0 && ($compania_id==$compania_iddemo)){
				// Registro y Vehiculo y relaciono.
				$placa = generarId();
				$resultado = $conexion->doInsert("
				vehiculo
				(vehiculo_patente, vehiculo_activo, vehiculo_eliminado, vehiculo_fechareg, 
				cuenta_id, compania_id, vehiculo_marca, vehiculo_modelo)
				",
				"'$placa', '1', '0', '$fechaactual',
				'$cuenta_id', '$compania_id', 'Ford', 'Fiesta'");

				$arrresultado2 = $conexion->doSelect("max(vehiculo_id) as vehiculo_id",
				"vehiculo", "vehiculo_activo = '1'");
				if (count($arrresultado2)>0){
					foreach($arrresultado2 as $i=>$valor){
						$vehiculo_id = utf8_encode($valor["vehiculo_id"]);
					}

					$resultado = $conexion->doInsert("
					usuariovehiculo
					(vehiculo_id, usuario_id, usuariovehiculo_fechareg, usuariovehiculo_activo, usuariovehiculo_eliminado) 
					",
					"'$vehiculo_id', '$usuario_id', '$fechaactual', 1, 0");

				}
			}

			
			$verificado = 1;
			
			if ($usuario_emailverif!="" && $usuario_emailverif!="0" ){
			    $verificado = 0;
			}

			if ($compania_id=="395"){
				$verificado = 1;
			}

			$tipoenvio = 1;
			
			$message = "Inicio correcto";

			if ($usuario_codverif==""){
				$tokenlogin = uniqid();
				$usuario_codverif = $usuario_codverif;
			}else{
				$tokenlogin = $usuario_codverif;
			}
			
			if ($usuario_email=="loteria"){
				$tokenlogin = $usuario_codverif;
			}
				
			if ($usuario_email=="demo"){
				$tokenlogin = "demotoken";
			}

			if ($email=="pasajero"){
				$tokenlogin = "demotoken";
			}

			if ($email=="conductor"){
				$tokenlogin = "conductor";		
			}

			if ($whatsapp=="123" && $compania_id==374){
				$tokenlogin = "loteria";		
			}

			if ($email=="demousuario"){
				$tokenlogin = "demousuario";		
			}

			if ($email=="hotel" || $email=="Hotel"){
				$tokenlogin = "hotel";		
			}

			if ($usuario_email=="democliente"){
				$tokenlogin = "democliente";
			}

			if ($usuario_email=="demovendedor"){
				$tokenlogin = "demovendedor";
			}

			if ($usuario_email=="demoadmin"){
				$tokenlogin = "demoadmin";
			}

			if ($usuario_email=="demoempresa"){
				$tokenlogin = "demoempresa";
			}

			$instancialista = new Lista();
				/* 
					l_estatus_iddisponible = '$estatusdisponibilidad',

					$obtenerCodigoLista = 2; // No disponible para viajes
					$obtenerTipoLista = 268; // Estatus de Disponibilidad del Conductor
					$estatusdisponibilidad = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
				*/
			$resultado = $conexion->doUpdate("usuario", "
					usuario_codverif ='$tokenlogin',
					usuario_codreferido = '$usuario_codreferido'
				",
				"usuario_id='$usuario_id'");

			if($usuario_id!=""){

				if($usuario_qr==""){
					
					$qrCode = QrCode::create($usuario_id)
						->setEncoding(new Encoding('UTF-8'))
						->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
						->setSize(300)
						->setMargin(10)
						->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
						->setForegroundColor(new Color(0, 0, 0))
						->setBackgroundColor(new Color(255, 255, 255));					

					if ($compania_img!=""){
						//$logo = Logo::create("../arch/".$compania_img)->setResizeToWidth(50);	
					}
					
					$result = $writer->write($qrCode, $logo, $label);

					$usuario_qrimg = "qr_".$usuario_id.".png";

					$result->saveToFile("../arch/".$usuario_qrimg);

					$dataUri = $result->getDataUri();

					$resultado = $conexion->doUpdate("usuario", "
					usuario_qr ='$dataUri',
					usuario_qrimg = '$usuario_qrimg'							
					",
					"usuario_id='$usuario_id'");

				}

				if ($l_moneda_id=="" || $l_moneda_id=="0"){
					$moneda = ObtenerMonedaPrincipalId($cuenta_id, $compania_id);
				}else{
					$moneda = $l_moneda_id;
				}	

				$arrresultado = $conexion->doSelect("usuariobalance_disponible, usuariobalance_bloqueado, l_moneda_id","usuariobalance","usuariobalance_activo = '1' and usuario_id='$usuario_id'");
				if (count($arrresultado)==0){					
					$resultado = $conexion->doInsert("
					usuariobalance
					(usuario_id, usuariobalance_total, usuariobalance_bloqueado, usuariobalance_disponible, 
					usuariobalance_pendiente, usuariobalance_fechareg, usuariobalance_activo, 
					usuariobalance_eliminado, cuenta_id, compania_id, l_moneda_id, usuariobalance_cantidad,
					usuariobalance_sucursales, usuariobalance_cantidad2)
					",
					"'$usuario_id', '0', '0','0',
					'0','$fechaactual', 1,
					0, '$cuenta_id', '$compania_id', '$moneda', 0,
					0, 0
					");

				}

				$usuarioqrimagen = ObtenerUrlArch($compania_id)."/$usuario_qrimg";

				if ($compania_id=="380"){
					$usuarioqrimagen = ObtenerUrlArch($compania_id)."/$usuario_qrimg";
				}

				if ($compania_id=="451"){
					$perfil_id = "424";
					$perfil_idorig = "4";

					if ($tipousuarioinicia=="conductor" || $email=="conductor"){
						$perfil_id = "426";
						$perfil_idorig = "10";
					}

					$resultado = $conexion->doUpdate("usuario", "
					perfil_id ='$perfil_id'						
					",
					"usuario_id='$usuario_id'");

				}

				
				
				$data = array(
					"nombre" => $usuario_nombre,
					"apellido" => $usuario_apellido,
					"alias" => $usuario_alias,
					"tienecodpass" => $tienecodpass,

					"ciudad" => $ciudad_nombre,
					"email" => $usuario_email,
					"imagen" => $usuario_img,
					"idusuario" => $usuario_id,
					"id" => $usuario_id,
					"usuarioqr" => $usuarioqrimagen,
					"verificado" => $verificado,
					"perfil" => $perfil_idorig,
					"perfilact" => $perfil_id,
					"compania_id" => $compania_id,
					"cuenta_id" => $cuenta_id,
					"fecharegistro" => $usuario_fechareg,
					"whatsapp" => $usuario_whatsapp,
					"referidocodigo" => $usuario_codreferido,
					"estatuscod" => $estatus_cod,
					"estatusnombre" => $estatus_nombre,
					"reset" => $inicioclavereset,
					"tipousuario" => $tipousuario,
					"usuariobalance_disponible" => $usuariobalance_disponible,
					"moneda_siglas" => $moneda_siglas,
					"token" => $tokenlogin,
					"demo" => $demo,
					"compania_img" => $compania_img,
					"compania_nombre" => $compania_nombre,
				);

				

				if ($estatus_cod=="1"){ // Confirmar Email

					$texto = "
					<table border='0' width='100%' cellpadding='0' cellspacing='0' bgcolor='ffffff' class='bg_color'>

					<tr>
						<td align='center'>
							<table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590'>
								
								<tr>
									<td align='center' style='color: #343434; font-size: 24px; font-family: Quicksand, Calibri, sans-serif; font-weight:700;letter-spacing: 3px; line-height: 35px;' class='main-header'>
										<div style='line-height: 35px'>
											Hola $usuario_nombre $usuario_apellido, gracias por registrarte en nuestra App $compania_nombre, a continuacion tienes el codigo para confirmar el registro.
										</div>
									</td>
								</tr>


								<tr>
									<td height='10' style='font-size: 10px; line-height: 10px;'>&nbsp;</td>
								</tr>

								<tr>
									<td align='center'>
										<table border='0' width='40' align='center' cellpadding='0' cellspacing='0' bgcolor='eeeeee'>
											<tr>
												<td height='2' style='font-size: 2px; line-height: 2px;'>&nbsp;</td>
											</tr>
										</table>
									</td>
								</tr>

								<tr>
									<td height='20' style='font-size: 20px; line-height: 20px;'>&nbsp;</td>
								</tr>
									<tr>
									<td align='center' style='color: #343434; font-size: 28px; font-family: Quicksand, Calibri, sans-serif; font-weight:normal;letter-spacing: 3px; line-height: 35px;' class='main-header'>
										<div style='line-height: 26px;'>
											<strong> $usuario_emailverif </strong>
											<br>                            
										</div>
									</td>
									</tr>
									<tr>
									<td height='20' style='font-size: 20px; line-height: 20px;'>&nbsp;</td>
									</tr>
									<tr>
									<td align='center' style='color: #343434; font-size: 20px; font-family: Quicksand, Calibri, sans-serif; font-weight:normal;letter-spacing: 3px; line-height: 35px;' class='main-header'>
										<div style='line-height: 26px;'>
											Introduzca el mismo dentro de la Aplicacion
										</div>
									</td>
									</tr>

							
							</table>

						</td>
					</tr>
					<tr class='hide'>
						<td height='25' style='font-size: 25px; line-height: 25px;'>&nbsp;</td>
					</tr>
					<tr>
						<td height='20' style='font-size: 20px; line-height: 20px;'>&nbsp;</td>
					</tr>
				</table>
				";
				
					$asunto = "Codigo Verificacion: $usuario_emailverif";

					if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
						//$resultado = $libemail->enviarcorreo($email, $asunto, $texto, $compania_id);
					}
					/*
					if (filter_var($compania_email, FILTER_VALIDATE_EMAIL)) {
						$resultado = $libemail->enviarcorreo($compania_email, $asunto, $texto, $compania_id);
					}
					*/

					//$resultado = $libemail->enviarcorreo("meneses.rigoberto@gmail.com", $asunto, $texto, $compania_id);

					$message = "Debe confirmar el código enviado por email para poder iniciar sesión";

				}else if ($estatus_cod=="2"){

					$message = "Estamos verificando su información para poder habilitarlo en el sistema";
				}
			

				$valores = array(
					"code" => 0,
					"data" => $data,
					"message" => $message,
					"token" => $tokenlogin
				); 
			
				
			}else{
		

				$valores = array(
					"code" => 100,
					"message" => $message,
					"data" => $data,
				);

			}

		}else{

			$arrresultado2 = $conexion->doSelect("
			compania_img, compania_nombre, compania_email		
			",
			"compania", 
			"compania_id = '$compania_id'");

			foreach($arrresultado2 as $i=>$valor){
				$compania_img = utf8_encode($valor["compania_img"]);
				$compania_nombre = utf8_encode($valor["compania_nombre"]);
				$compania_email = utf8_encode($valor["compania_email"]);
			}
			
			$message = "Error iniciando sesionn";

			$tipoenvio = 2;


			$valores = array(
				"code" => 100,
				"message" => $message,
				"wherecompania" => $wherecompania,
				"wheretipoingreso" => $wheretipoingreso,
				"whereclave" => $whereclave,
				"whereperfil" => $whereperfil,
				"data" => $data,
			);

			
		}

		

	}
}


if ($tipoenvio == 1 || $tipoenvio == 2){// Login dentro de la App

	if ($tipoenvio == 1 ){	
		$asunto = "Inicio Correcto en $compania_nombre";
		$textoenviar = "Se ha iniciado sesion correctamente en la plataforma de $compania_nombre";
		
		if ($email==$usuario_email  && $email!=""){
			$formainicio = "
				<strong>Email:</strong> $email					
				<br>
			";
		}else if ($whatsapp==$usuario_whatsapp && $whatsapp!=""){
			$formainicio = "
				<strong>WhatsApp:</strong> $usuario_whatsapp					
				<br>
			";
		}
	}

	if ($tipoenvio == 2 ){	
		$asunto = "Inicio Fallido en $compania_nombre";
		$textoenviar = "Ha fallado al ingresar en la plataforma de $compania_nombre";

		if ($email!=""){
			$formainicio = "
				<strong>Email:</strong> $email					
				<br>
			";
		}
		if ($whatsapp!=""){
			$formainicio = "
				<strong>WhatsApp:</strong> $whatsapp					
				<br>
			";
		}

	}

	// Mensaje 
	$texto = "
		<table border='0' width='100%' cellpadding='0' cellspacing='0' bgcolor='ffffff' class='bg_color'>

		<tr>
			<td align='center'>
				<table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590'>
					
					<tr>
						<td align='center' style='color: #343434; font-size: 24px; font-family: Quicksand, Calibri, sans-serif; font-weight:700;letter-spacing: 3px; line-height: 35px;' class='main-header'>
							<div style='line-height: 35px'>
								$textoenviar
							</div>
						</td>
					</tr>


					<tr>
						<td height='10' style='font-size: 10px; line-height: 10px;'>&nbsp;</td>
					</tr>

					<tr>
						<td align='center'>
							<table border='0' width='40' align='center' cellpadding='0' cellspacing='0' bgcolor='eeeeee'>
								<tr>
									<td height='2' style='font-size: 2px; line-height: 2px;'>&nbsp;</td>
								</tr>
							</table>
						</td>
					</tr>

					<tr>
						<td height='20' style='font-size: 20px; line-height: 20px;'>&nbsp;</td>
					</tr>
						<tr>
							<td align='center' style='color: #343434; font-size: 24px; font-family: Quicksand, Calibri, sans-serif; font-weight:normal;letter-spacing: 3px; line-height: 35px;' class='main-header'>
							<div style='line-height: 26px;'>
								$formainicio
							</div>
							</td>
						</tr>
						<tr>
							<td height='20' style='font-size: 20px; line-height: 20px;'>&nbsp;</td>
						</tr>
						
				</table>

			</td>
		</tr>
		<tr class='hide'>
			<td height='25' style='font-size: 25px; line-height: 25px;'>&nbsp;</td>
		</tr>
		<tr>
			<td height='20' style='font-size: 20px; line-height: 20px;'>&nbsp;</td>
		</tr>
	</table>
	";
	
	/* 
	if (filter_var($compania_email, FILTER_VALIDATE_EMAIL)) {
		$resultado = $libemail->enviarcorreo($compania_email, $asunto, $texto, $compania_id);
	} 
	*/

	$correomasivo_id = 0;
	$correomasivocampana_titulo = "Inicio en $compania_nombre";
	$correomasivocampana_descrippush = "$usuario_nombre $usuario_apellido";
	$correomasivocampana_descripemail = "Inicio de sesion en $compania_nombre - $usuario_nombre $usuario_apellido";

	$arrresultado2 = $conexion->doSelect("usuario.usuario_id, usuario.usuario_notas, usuario_email, usuario_notificaremail",
	"usuario
		inner join perfil on perfil.perfil_id = usuario.perfil_id
	", 
	"usuario.compania_id ='$compania_id' and perfil.perfil_idorig = '3' ");
	if (count($arrresultado2)>0){				
		foreach($arrresultado2 as $i=>$valor){
			$usuario_pushtoken = utf8_encode($valor["usuario_notas"]);
			$usuario_email = utf8_encode($valor["usuario_email"]);
			$usuario_notificaremail = utf8_encode($valor["usuario_notificaremail"]);
		
			// Notificacion PUSH
			if ($usuario_pushtoken!=""){

				$tipo = "login";			

				if ($usuario_notificaremail=="0"){
					$usuario_email = "";
				}

				// Notifica cuando alguien se conecta
				//enviarNotificacionFunciones($correomasivo_id, $usuario_nombre, $usuario_apellido, $compania_email, $usuario_id, null, $fechaactual, $correomasivocampana_descrippush, $correomasivocampana_titulo, $usuario_pushtoken, $cuenta_id, $compania_id, $usuario_id, $tipo, $correomasivocampana_descripemail);		

			}	
		}			
	}	

	
	//$resultado = $libemail->enviarcorreo("meneses.rigoberto@gmail.com", $asunto, $texto, $compania_id);	
	
}


$resultado = json_encode($valores);

echo $resultado;

exit();

?>