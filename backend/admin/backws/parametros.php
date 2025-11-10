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
$metodo = "POST";

if ($metodo=="POST"){// Consultar Parametros

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	file_put_contents("files/".basename(__FILE__, '.php')."-".uniqid().".txt", $valoresPost);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['version'])) ? $version=$valoresPost['version'] :$version='';
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
	
	$arrresultado = $conexion->doSelect("pais.pais_codmapa, pais.pais_nombre, pais.pais_id",
	"
	tipolista
	inner join listaconfig on tipolista.tipolista_id = listaconfig.tipolista_id and listaconfig_activo = '1' 
	inner join pais on pais.pais_id = listaconfig.listaconfig_valoruno
	",
	"tipolista_activo = '1' and tipolista.tipolista_config = '1' and tipolista.tipolista_id = '256' and listaconfig.compania_id = '$compania_id' ");

	foreach($arrresultado as $i=>$valor){
		$pais_id = utf8_encode($valor["pais_id"]);
		$codigopais = utf8_encode($valor["pais_codmapa"]);
		$paisnombre = utf8_encode($valor["pais_nombre"]);
	}

	// Moneda Principal
	$arrresultado = $conexion->doSelect("lista.lista_nombre, lista.lista_nombredos as moneda_siglas",
	"
	lista
		inner join listaconfig on listaconfig.listaconfig_valoruno = lista.lista_id and listaconfig_activo = '1' 
	",
	"listaconfig.tipolista_id = '257' and listaconfig.compania_id = '$compania_id' ");

	foreach($arrresultado as $i=>$valor){
		$monedaprincipal = utf8_encode($valor["lista_nombre"]);		
		$moneda_siglas = utf8_encode($valor["moneda_siglas"]);		
	}

	// Tipos de Servicios Habilitados
	$tipolista_id = 267;
	$arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_img, lista_nombre, lista_cod, listacuentarel_id",
		"lista 
			inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.compania_id = '$compania_id' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'
		",
		"lista_activo = '1'  and lista.tipolista_id = '$tipolista_id' ", null, "lista_orden asc");

	$tiposservicios = array();
	foreach($arrresultado as $i=>$valor){
  
		$lista_id = utf8_encode($valor["lista_id"]);
		$lista_cod = utf8_encode($valor["lista_cod"]);  
		$lista_nombre = utf8_encode($valor["lista_nombre"]); 
		$lista_img = utf8_encode($valor["lista_img"]); 
		
		$color1 = "";
		$color2 = "";
		$texto = "";
		$textobanner = "";
		$textobannerdos = "";
		$icono = "";
		$descripcion = "";
		$paddingTop = 20;
		$placeholderorigen = "¿Donde te buscamos?";
		$placeholderdestino = "¿Hacía donde vas?";
		$observacionorigen = "Puntos de referencia del origen, persona a buscar";
		$observaciondestino = "Puntos de referencia del destino";

		$urlimagen = ObtenerUrlArch($compania_id, "3");
		
		$imagen = $urlimagen."/".$lista_img;
		$imagenbanner = $urlimagen."/app_1.jpg";
		
		if ($lista_cod=="1"){
			$color1 = "#C7EEB3";$color2 = "#489123"; 
			$icono="car";
			$descripcion = "Indícanos el origen y destino de tu viaje.";
			$placeholderorigen = "¿Donde te buscamos?";
			$placeholderdestino = "¿Hacía donde vas?";
			$observacionorigen = "Puntos de referencia del origen, persona a buscar";
			$observaciondestino = "Puntos de referencia del destino";
			$textobanner = "Viaje en auto";
			$textobannerdos = "Llega más rápido a tu destino";
			$imagenbanner = $urlimagen."/app_1.jpg";

			if ($compania_id=="381"){
				$textobanner = "";
				$textobannerdos = "";
				$imagenbanner = "https://www.asvicaj.com/assets/img/bannerasvi1.jpg";
			}

		}
		if ($lista_cod=="2"){
			$color1 = "#FFEEBC";$color2 = "#C2930A"; //$texto = "Moto";
			$icono="motorcycle";
			$descripcion = "Indícanos el origen y destino de tu viaje en moto. "; // En el próximo vas a poder detallarnos más en detalle sobre el origen y destino
			$placeholderorigen = "¿Donde te buscamos?";
			$placeholderdestino = "¿Hacía donde vas?";
			$observacionorigen = "Puntos de referencia del origen";
			$observaciondestino = "Puntos de referencia del destino";
			$textobanner = "Viaja en moto";
			$textobannerdos = "Para los que desean viajar sin tráfico";
			$imagenbanner = $urlimagen."/app_2.jpg";		
			
			if ($compania_id=="381"){
				$textobanner = "";
				$textobannerdos = "";
				$imagenbanner = "https://www.asvicaj.com/assets/img/bannerasvi2.jpg";
			}
		}
		if ($lista_cod=="3"){
			$color1 = "#E7E7E7";$color2 = "#3B3B3B"; //$texto = "Fletes";
			
			$icono="truck";
			$descripcion = "Indícanos el origen y destino desde donde desear hacer la mudanza o traslado.";
			$placeholderorigen = "¿Donde te buscamos?";
			$placeholderdestino = "¿Hacía donde vas?";
			$observacionorigen = "Informanos sobre que vas a trasladar, peso, más información sobre el origen";
			$observaciondestino = "Puntos de referencia del destino";
			$textobanner = "Pide tu flete";
			if ($compania_id=="449"){
				$textobanner = "Pedí tu flete";
			}
			
			$textobannerdos = "Realiza tus mudanzas";
			$imagenbanner = $urlimagen."/app_3.jpg";			

		}
		if ($lista_cod=="4"){
			$color1 = "#FFDCDC";$color2 = "#AD0E0E"; //$texto = "Grúa";
			$icono="truck-pickup";
			$descripcion = "Indícanos el origen y destino desde donde debe dirigirse la grúa";
			$placeholderorigen = "¿Donde te buscamos?";
			$placeholderdestino = "¿Hacía donde vas?";
			$observacionorigen = "Datos del vehículo";
			$observaciondestino = "Datos adicionales";
			
			$textobanner = "Pide tu grúa";
			if ($compania_id=="449"){
				$textobanner = "Pedí tu grúa";
			}
			$textobannerdos = "Para grúa";
			$imagenbanner = $urlimagen."/app_4.jpg";			
		}
		if ($lista_cod=="5"){
			$color1 = "#C8F2ED";$color2 = "#055D5D"; //$texto = "Enviar";
			$icono="box";
			$descripcion = "Indícanos el origen y destino hacía donde vas a enviar y recibir tu paquete.";
			$placeholderorigen = "¿Donde buscamos el paquete?";
			$placeholderdestino = "¿Hacía donde tienes que llevar el paquete?";
			$observacionorigen = "Informanos sobre que vas a trasladar";
			$observaciondestino = "Puntos de referencia del destino";
			$textobanner = "Envía o Recibe";
			$textobannerdos = "Envía tus paquetes con seguridad";
			$imagenbanner = $urlimagen."/app_5.jpg";	
			
			
		}

		if ($lista_cod=="8"){
			$color1 = "#C8F2ED";$color2 = "#055D5D"; //$texto = "Enviar";
			$icono="box";
			$descripcion = "Denuncia lo ocurrido con el botón de pánico";
			$placeholderorigen = "";
			$placeholderdestino = "¿En donde te encuentras?";
			$observacionorigen = "";
			$observaciondestino = "Puntos de referencia del destino";
			$textobanner = "";
			$textobannerdos = "";
			$imagenbanner = $urlimagen."/app_5.jpg";				
		}

		$texto = $lista_nombre;
		
		$personalizarprecio = 0;

		if ($compania_id=="387"){
			$personalizarprecio = 0;
		}

		if ($compania_id=="388"){ // Latom
			$personalizarprecio = 1;
		}
		
		if ($compania_id=="404"){
			$personalizarprecio = 0;
		}
		

		
		
		$arrayUnico = [
			'id'=>$lista_id,   
		    'codigo'=>$lista_cod,   
		    'nombre'=>$lista_nombre,
			'texto'=>$texto,
			'textobanner'=>$textobanner,
			'textobannerdos'=>$textobannerdos,
			'color1'=>$color1,
			'color2'=>$color2,
			'icono'=>$icono,
			'paddingTop'=>$paddingTop,
			'descripcion'=>$descripcion,
			'imagen'=>$imagen,
			'imagenbanner'=>$imagenbanner,
			'placeholderorigen'=>$placeholderorigen,
			'placeholderdestino'=>$placeholderdestino,
			'observacionorigen'=>$observacionorigen,
			'observaciondestino'=>$observaciondestino,
			
		  ];

		array_push($tiposservicios, $arrayUnico);
	}

	if ($compania_id=="394"){
		$urlcanalyoutube = "https://www.youtube.com/@Crimepay";
	}

	// Habilitar Demo = 371
	// Habilitar Registro de Usuario = 372
	// Habilitar Crear Torneo = 373

	$habilitarregistro = 1;
	$habilitardemo = 1;
	$habilitarlogs = 1;

	$habilitarloginemail = 1;
	$habilitarlogintelf = 1;
	
	$habilitarcreartorneo = 0;
	$habilitarinvitado = 1;
	$habilitartraduccion = 0;
	$appEnDesarrollo = "";
	$appEnDesarrolloContinuar = 1;
	$habilitarinfodesarrolladopor = 0;
	$forzarActualizacion = 1;
	$ultimaVersion = 0; // No

	$divproblemaspsicologicos = 0;
	$divincidencias = 0;

	$miliSegundosTiempoRealMapa = 20000;
	$miliSegundosTiempoRealNotificaciones = 10000;

	/*

	$wherecompania = " and compania_id = '$compania_id' ";

	if ($tipoapp=="mitvcare"){
		$compania_id = "3866";
	}

	if ($compania_id=="386"){
		$wherecompania = " and compania_id in ('386','3866') ";
	}

	*/
	
	if ($compania_id=="459"  || $compania_id=="466"){
		//$wherecompaniaversion = " or app_version = '2.1.8' or app_version = '2.1.9' ";
	}

	if ($compania_id=="373"){
		$wherecompaniaversion = " or app_version = '2.3.1' or app_version = '2.2.6' or app_version = '2.2.7' or app_version = '2.2.8'  or app_version = '2.2.9'  or app_version = '2.3.0'";
	}

/* 	if ($compania_id=="470"){
		$wherecompaniaversion = " or app_version = '2.2.5' or app_version = '2.2.6' or app_version = '2.2.7'";
	} */
	
	$arrresultado = $conexion->doSelect("app.app_id",
	"app","app_activo = '1' and compania_id = '$compania_id' and (app_version = '$version' $wherecompaniaversion )  ");

	foreach($arrresultado as $i=>$valor){
		$app_id = utf8_encode($valor["app_id"]);
		$ultimaVersion = 1; // Si
	}	



	$arrresultado = $conexion->doSelect("listaconfig_valoruno, tipolista_id",
	"listaconfig",
	"listaconfig.tipolista_id in ('371','372','373')  and listaconfig_activo = '1'  and listaconfig.compania_id = '$compania_id' ");
	foreach($arrresultado as $i=>$valor){
		$listaconfig_valoruno = utf8_encode($valor["listaconfig_valoruno"]);		
		$tipolista_id = utf8_encode($valor["tipolista_id"]);		

		if ($tipolista_id=="371"){
			if ($listaconfig_valoruno=="1"){$habilitardemo = 1;}
			if ($listaconfig_valoruno=="0"){$habilitardemo = 0;}
		}
		else if ($tipolista_id=="372"){
			if ($listaconfig_valoruno=="1"){$habilitarregistro = 1;}
			if ($listaconfig_valoruno=="0"){$habilitarregistro = 0;}
		}
		else if ($tipolista_id=="373"){
			if ($listaconfig_valoruno=="1"){$habilitarcreartorneo = 1;}
			if ($listaconfig_valoruno=="0"){$habilitarcreartorneo = 0;}
		}
	}

	if ($compania_id=="373"){ // vt taxi	
		$habilitardemo = 1;	
		$habilitarinfodesarrolladopor = 1;

		if ($compania_id=="373"){
			
		}
	}

	if ($compania_id=="382"){
		$habilitarregistro = 0;
		$habilitardemo = 0;
	}

	if ($compania_id=="383"){ // vt hotel
		$habilitarregistro = 0;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="387"){	// AgroComercio	
		$habilitardemo = 1;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="388"){	// Latom	
		$habilitardemo = 1; // Habilitado para demo 
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="395"){	// Juegana	
		$habilitardemo = 0;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="406"){	// Desim Latam	
		$habilitardemo = 0;
		$habilitartraduccion = 0;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="408"){	// KASS	 
		$habilitardemo = 0;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="380"){	// Mototaxibolivia
		$habilitardemo = 1;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="441"){	// Verdulería VT	
		$habilitardemo = 0;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="445"){	// SUPER ALTOS
		$habilitardemo = 1;
		$habilitarinfodesarrolladopor = 1;
	}


	if ($compania_id=="449"){	// ARGENVIOS
		$habilitardemo = 0;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="452"){ // Med FyndH ConectH
		$habilitardemo = 1;
		$habilitartraduccion = 0;
		//$appEnDesarrollo = "La app se encuentra en estado falta de pago. Contacte con su administrador de la app para regularizar la situacion y evitar desactivacion";
		//$appEnDesarrollo = "La app esta deshabilitada por proyecto detenido. Contacte a su administrador para reactivarla";
		$habilitarinfodesarrolladopor = 0;
		//$appEnDesarrolloContinuar = 0;	
	}

	if ($compania_id=="456"){	// La Prida
		$habilitarregistro = 0;
		$habilitardemo = 1;
		$habilitarinvitado = 0;
		$habilitartraduccion = 0;
		$habilitarinfodesarrolladopor = 1;
	}

	if ($compania_id=="457"){ // Sateli Taxi
		$habilitarlogs = 0;
		$habilitardemo = 0;
		$habilitarinvitado = 0;
		$habilitartraduccion = 0;
		$appEnDesarrollo = "La app se encuentra en estado falta de pago. Contacte con su administrador de la app para regularizar la situacion y evitar desactivacion";
		$habilitarinfodesarrolladopor = 0;
		//$appEnDesarrolloContinuar = 1;	

		//$forzarActualizacion = 1;
		//$appEnDesarrolloContinuar = 0;

		$appEnDesarrollo = "";
		$appEnDesarrolloContinuar = 1;

	}

	if ($compania_id=="458"){ // CORSEPSA
		$habilitardemo = 1;
		$habilitartraduccion = 0;
		$habilitarinfodesarrolladopor = 0;

		$appEnDesarrollo = "La app esta deshabilitada por proyecto detenido. Contacte a su administrador para reactivarla";
		$forzarActualizacion = 1;
		$appEnDesarrolloContinuar = 0;

		$appEnDesarrollo = "";
		$appEnDesarrolloContinuar = 1;
	}

	if ($compania_id=="459"){ // Reina Roja
		$habilitardemo = 1;
		$habilitartraduccion = 0;		
		$forzarActualizacion = 0;
		$habilitarinfodesarrolladopor = 0;
		$habilitarlogs = 0;

		$divproblemaspsicologicos = 1;
		$divincidencias = 1;

		$appEnDesarrollo = "";
		$appEnDesarrolloContinuar = 1;	
	}

	if ($compania_id=="460"){ // 99 Place
		$habilitardemo = 1;
		$habilitartraduccion = 0;
		$appEnDesarrollo = "La app se encuentra en estado falta de pago. Contacte con su administrador de la app para regularizar la situacion y evitar desactivacion";
		$forzarActualizacion = 0;
		$habilitarinfodesarrolladopor = 0;
		$appEnDesarrolloContinuar = 1;	

		$appEnDesarrollo = "La app esta deshabilitada por proyecto detenido. Contacte a su administrador para reactivarla";
		$forzarActualizacion = 1;
		$appEnDesarrolloContinuar = 0;

		$appEnDesarrollo = "";
		$appEnDesarrolloContinuar = 1;
	}


	if ($compania_id=="462"){ // Amigo Market
		$habilitardemo = 1;
		$habilitartraduccion = 0;
		$appEnDesarrollo = "La app se encuentra en estado falta de pago. Contacte con su administrador de la app para regularizar la situacion y evitar desactivacion";
		$forzarActualizacion = 0;
		$habilitarinfodesarrolladopor = 0;
		$appEnDesarrolloContinuar = 1;	

		$appEnDesarrollo = "La app esta deshabilitada por proyecto detenido. Contacte a su administrador para reactivarla";
		$forzarActualizacion = 1;
		$appEnDesarrolloContinuar = 0;

		$appEnDesarrollo = "";
		$appEnDesarrolloContinuar = 1;
		
	}

	if ($compania_id=="463"){ // Abunda Pay
		$habilitardemo = 1;
		$habilitarinvitado = 0;	
		$habilitartraduccion = 0;
	}

	if ($compania_id=="465"){ // PymeGo
		$habilitardemo = 1;
		$habilitarinvitado = 0;	
		$habilitartraduccion = 0;
		$habilitarinfodesarrolladopor = 0;	
		
		$miliSegundosTiempoRealMapa = 0;
		$miliSegundosTiempoRealNotificaciones = 0;
	}

	if ($compania_id=="466"){ // VT Panico
		$habilitardemo = 1;
		$habilitartraduccion = 0;		
		$forzarActualizacion = 1;
		$habilitarinfodesarrolladopor = 0;
		$habilitarlogs = 0;

		$divproblemaspsicologicos = 1;
		$divincidencias = 1;
	}

	if ($compania_id=="467"){ // TipPal
		$habilitardemo = 1;
		$habilitarregistro = 0;				
		$habilitarinfodesarrolladopor = 1;		
		
		$habilitarloginemail = 1;
		$habilitarlogintelf = 0;
	}

	if ($compania_id=="468"){ // CanjeAr
		$habilitardemo = 1;
		$habilitarregistro = 1;				
		$habilitartraduccion = 1;	
		$habilitarinfodesarrolladopor = 1;				
	}	

	if ($compania_id=="470"){ // Control de Glucosa
		$appEnDesarrolloContinuar = 0;	
	}

	
	
	

	if ($codigopais==""){
		$codigopais = "AR";
	}

	if($codigopais!=""){

		$latitudregion = -34.6030689248688;
		$longitudregion = -58.38255850095892;		

		if ($codigopais=="AR"){ $latitudregion = -34.6030689248688;$longitudregion = -58.38255850095892;}
		else if ($codigopais=="BO"){ $latitudregion = -34.61236945292781;$longitudregion = -58.47559778475411;}
		else if ($codigopais=="BR"){ $latitudregion = -22.824868571821167;$longitudregion = -43.41211935296898;}
		else if ($codigopais=="CL"){ $latitudregion = -33.44910669899993;$longitudregion = -70.6664334519158;}
		else if ($codigopais=="CO"){ $latitudregion = 4.692086580450585;$longitudregion = -74.07443378961436;}
		else if ($codigopais=="EC"){ $latitudregion = -0.17344359053395966;$longitudregion = -78.47323855273541;}
		else if ($codigopais=="PE"){ $latitudregion = -12.044944663577402;$longitudregion = -77.04236144165928;}
		else if ($codigopais=="PY"){ $latitudregion = -25.267462298733914;$longitudregion = -57.58373087122516;}
		else if ($codigopais=="UY"){ $latitudregion = -34.90132189248443;$longitudregion = -56.18507014066638;}
		else if ($codigopais=="VE"){ $latitudregion = 10.48039480530996;$longitudregion = -66.9045170897939;}
		else if ($codigopais=="CR"){ $latitudregion = 9.928646006476356;$longitudregion = -84.09358939464613;}
		else if ($codigopais=="ES"){ $latitudregion = 38.91143736139435;$longitudregion = -3.702898329111635;}
		else if ($codigopais=="US"){ $latitudregion = 38.90510291327388;$longitudregion = -77.04246390067391;}
		else if ($codigopais=="DM"){ $latitudregion = 18.469278696724615;$longitudregion = -69.94963147388474;}	
		else if ($codigopais=="MX"){ $latitudregion = 19.43803484046095;$longitudregion = -99.13639456245896;}

		$paisimagen = "https://www.gestiongo.com/admin/dist/img/pais/".$pais_id.".png";


		if ($compania_id=="395"){

			$arrresultado = $conexion->doSelect("count(*) as total",
			"evento	
				inner join eventocalendario on eventocalendario.evento_id = evento.evento_id
				inner join juego on juego.juego_id = evento.juego_id								
			",
			"evento_activo = '1' and evento.compania_id = '$compania_id' and eventocalendario.l_estatus_id = '1988'"); 

			foreach($arrresultado as $i=>$valor){
				$habilitarcreartorneo = utf8_encode($valor["total"]);				
			}
		}

		$data = array(
			"tiposervicio" => $tiposservicios,
			"pais" => $codigopais,
			"paisnombre" => $paisnombre,
			"paisimagen" => $paisimagen,
			"latitudregion" => $latitudregion,
			"longitudregion" => $longitudregion,
			"segundostiemporesl" => $paisnombre,	
			"moneda" => $monedaprincipal,	
			"monedasiglas" => $moneda_siglas,	
			"miliSegundosTiempoRealMapa" => $miliSegundosTiempoRealMapa,
			"miliSegundosTiempoRealNotificaciones" => $miliSegundosTiempoRealNotificaciones,
			"tiempoRealMapa" => 1,
			"referidos" => 1,
			"buscadorGoogle" => 1,
			'personalizarprecio'=>$personalizarprecio,
			'urlcanalyoutube'=>$urlcanalyoutube,
			'habilitarregistro'=>$habilitarregistro,
			'habilitardemo'=>$habilitardemo,
			'habilitarloginemail'=>$habilitarloginemail,
			'habilitarlogintelf'=>$habilitarlogintelf,
			'habilitarlogs'=>$habilitarlogs,
			'habilitarinvitado'=>$habilitarinvitado,
			'habilitartraduccion'=>$habilitartraduccion,			
			'habilitarcreartorneo'=>$habilitarcreartorneo,
			'habilitarinfodesarrolladopor'=>$habilitarinfodesarrolladopor,			
			'appEnDesarrollo'=>$appEnDesarrollo,
			'appEnDesarrolloContinuar'=>$appEnDesarrolloContinuar,
			'ultimaVersion'=>$ultimaVersion,
			'forzarActualizacion'=>$forzarActualizacion,
			'divproblemaspsicologicos'=>$divproblemaspsicologicos,
			'divincidencias'=>$divincidencias,
		);

		$valores = array(
			"code" => 0,
			"data" => $data,
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