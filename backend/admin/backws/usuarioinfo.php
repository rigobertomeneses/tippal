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
	(isset($valoresPost['id'])) ? $getusuariodestino_id=$valoresPost['id'] :$getusuariodestino_id='';
	(isset($valoresPost['alias'])) ? $getalias=$valoresPost['alias'] :$getalias='';
	(isset($valoresPost['tipo'])) ? $gettipo=$valoresPost['tipo'] :$gettipo='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}

	
	/*
		if ($token==""){
			$valores = array(
				"code" => 104,
				"message" => "Token no encontrado. url: usuarioinfo",
				"data" => [],
			);

			$resultado = json_encode($valores);
			echo $resultado;
			exit();
		}
	*/

	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();
	
	$arrresultado2 = $conexion->doSelect("usuario_id","usuario","usuario_activo = '1' and usuario_codverif = '$token' and compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
	}

	if ($getusuariodestino_id==""){
		$getusuariodestino_id = $usuario_id;
	}
/*
	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: usuarioinfo",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}
	*/

	$whereusuario = " AND usuario.usuario_id = '$getusuariodestino_id' ";

	if ($getalias!=""){
		// Check if the alias is numeric to search by usuario_id as well
		if (is_numeric($getalias)) {
			$whereusuario = " AND (usuario.usuario_alias = '$getalias' or usuario.usuario_email = '$getalias' or usuario.usuario_id = '$getalias' ) ";
		} else {
			$whereusuario = " AND (usuario.usuario_alias = '$getalias' or usuario.usuario_email = '$getalias' ) ";
		}
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

	$arrresultado = $conexion->doSelect("usuario.usuario_id, usuario.usuario_codigo,
		usuario.usuario_email, usuario.usuario_clave, usuario.usuario_nombre, usuario.usuario_apellido, usuario.usuario_alias, usuario.usuario_cvu, usuario.usuario_aliasbanco,
		usuario.usuario_img, usuario.usuario_rating, usuario.sexo_id,
		usuario.usuario_username, usuario.usuario_bio, usuario.usuario_ubicacion,
		usuario.usuario_portada, usuario.usuario_coverimg, usuario.usuario_verificado, usuario.usuario_premium,
		usuario.usuario_sitio_web, usuario.usuario_fecha_nacimiento, perfil.perfil_idorig,
		estatus.lista_cod as estatuscod,
		estatus.lista_nombre as estatusnombre,
		estatusconductor.lista_cod as estatusconductorcod,
		DATE_FORMAT(usuario.usuario_fechareg,'$formatofechaSQL') as usuario_fechareg,
		usuario.usuario_whatsapp, usuario.usuario_telf,
		pagoverdatos_id,
		usuario.usuario_representantelegal, usuario.usuario_empresa,
		usuario.usuario_direccion, usuario.usuario_documento,
		pais.pais_nombre,
		pais.pais_id,
		zonahoraria.lista_nombre as zonahoraria_nombre,
		pais.l_zonahoraria_id, pais.pais_id,
		moneda.lista_nombredos as moneda_siglas,
		moneda.lista_nombre as moneda_nombre,
		moneda.lista_id as moneda_id,
		usuario.cuenta_id,
		usuarioinfoservicio.usuarioinfoserv_resumen as resumen,
		usuarioinfoservicio.usuarioinfoserv_descrip as descrip,
		categoria.lista_id as categ_id, categoria.lista_nombre as categ_nombre,
		ue.total_publicaciones, ue.total_seguidores, ue.total_siguiendo,
		ue.total_likes, ue.total_comentarios, ue.total_vistas,
		ue.total_suscriptores, ue.total_ganancias,
		ub.usuariobalance_disponible, ub.usuariobalance_pendiente
	    ",
		"usuario
			inner join perfil on perfil.perfil_id = usuario.perfil_id
			inner join usuario cuenta on cuenta.usuario_id = usuario.cuenta_id
			inner join compania on compania.compania_id = usuario.compania_id

			left join usuariocategoria on usuariocategoria.usuario_id = usuario.usuario_id and usuariocateg_activo = '1'
			left join lista categoria on categoria.lista_id = usuariocategoria.l_categ_id

			left join usuarioinfoservicio on usuarioinfoservicio.usuario_id = usuario.usuario_id

			left join pais on pais.pais_id = usuario.pais_id
			left join lista zonahoraria on zonahoraria.lista_id = pais.l_zonahoraria_id
			left join lista moneda on moneda.lista_id = pais.l_moneda_id

			left join lista estatus on estatus.lista_id = usuario.l_estatus_id
			left join lista estatusconductor on estatusconductor.lista_id = usuario.l_estatus_iddisponible
			left join pagoverdatos on pagoverdatos.usuario_idver = usuario.usuario_id
			and pagoverdatos.usuario_id = '$usuario_id' and pagoverdatos.pagoverdatos_activo = '1'

			left join usuarioestadisticas ue on ue.usuario_id = usuario.usuario_id and ue.compania_id = '$compania_id'
			left join usuariobalance ub on ub.usuario_id = usuario.usuario_id and ub.usuariobalance_activo = '1' and ub.compania_id = '$compania_id'

		",
		"usuario.usuario_activo = '1' $whereusuario ");

	// left join usuarioinfoservicio

	$total = count($arrresultado);		

	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$usuario_cvu = utf8_encode($valor["usuario_cvu"]);
			$usuario_aliasbanco = utf8_encode($valor["usuario_aliasbanco"]);

			$categ_id = utf8_encode($valor["categ_id"]);
			$categ_nombredet = utf8_encode($valor["categ_nombre"]);

			if ($categ_nombre==""){
				$categ_nombre = $categ_nombredet;
			}else{
				$categ_nombre = $categ_nombre." / ".$categ_nombredet;
			}

			$cuenta_id = utf8_encode($valor["cuenta_id"]);

			$moneda_id = utf8_encode($valor["moneda_id"]);
			$moneda_nombre = utf8_encode($valor["moneda_nombre"]);
			$moneda_siglas = utf8_encode($valor["moneda_siglas"]);

			$usuario_rating = utf8_encode($valor["usuario_rating"]);

			$resumen = utf8_encode($valor["resumen"]);
			$descrip = utf8_encode($valor["descrip"]);

			$pais_id = utf8_encode($valor["pais_id"]);
			$pais_nombre = utf8_encode($valor["pais_nombre"]);
			$l_zonahoraria_id = utf8_encode($valor["l_zonahoraria_id"]);
			$zonahoraria_nombre = utf8_encode($valor["zonahoraria_nombre"]);

			$usuario_id = utf8_encode($valor["usuario_id"]);
			$usuario_codigo = utf8_encode($valor["usuario_codigo"]);
			$usuario_email = utf8_encode($valor["usuario_email"]);
			$usuario_clave = utf8_encode($valor["usuario_clave"]);
			$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
			$usuario_apellido = utf8_encode($valor["usuario_apellido"]);
			$usuario_whatsapp = utf8_encode($valor["usuario_whatsapp"]);
			$usuario_alias = utf8_encode($valor["usuario_alias"]);
			$sexo_id = utf8_encode($valor["sexo_id"]);

			$usuario_telf = utf8_encode($valor["usuario_telf"]);
			$usuario_representantelegal = utf8_encode($valor["usuario_representantelegal"]);
			$usuario_empresa = utf8_encode($valor["usuario_empresa"]);
			$usuario_direccion = utf8_encode($valor["usuario_direccion"]);
			$usuario_documento = utf8_encode($valor["usuario_documento"]);
			
			$usuario_img = utf8_encode($valor["usuario_img"]);
			$estatuscod = utf8_encode($valor["estatuscod"]);
			$estatusnombre = utf8_encode($valor["estatusnombre"]);
			$estatusconductorcod = utf8_encode($valor["estatusconductorcod"]);
			$usuario_fechareg = utf8_encode($valor["usuario_fechareg"]);
			$pagoverdatos_id = utf8_encode($valor["pagoverdatos_id"]);

			// Campos de La Kress
			$usuario_username = utf8_encode($valor["usuario_username"]);
			$usuario_bio = utf8_encode($valor["usuario_bio"]);
			$usuario_ubicacion = utf8_encode($valor["usuario_ubicacion"]);
			$usuario_portada = utf8_encode($valor["usuario_portada"]);
			$usuario_coverimg = utf8_encode($valor["usuario_coverimg"]);
			$usuario_verificado = intval($valor["usuario_verificado"]);
			$usuario_premium = intval($valor["usuario_premium"]);
			$usuario_sitio_web = utf8_encode($valor["usuario_sitio_web"]);
			$usuario_fecha_nacimiento = utf8_encode($valor["usuario_fecha_nacimiento"]);
			$perfil_idorig = utf8_encode($valor["perfil_idorig"]);

			// Estadísticas de La Kress
			$total_publicaciones = intval($valor["total_publicaciones"]);
			$total_seguidores = intval($valor["total_seguidores"]);
			$total_siguiendo = intval($valor["total_siguiendo"]);
			$total_likes = intval($valor["total_likes"]);
			$total_comentarios = intval($valor["total_comentarios"]);
			$total_vistas = intval($valor["total_vistas"]);
			$total_suscriptores = intval($valor["total_suscriptores"]);
			$total_ganancias = floatval($valor["total_ganancias"]);

			// Balance
			$balance_disponible = floatval($valor["usuariobalance_disponible"]);
			$balance_pendiente = floatval($valor["usuariobalance_pendiente"]);
						
			$imagen = ObtenerUrlArch($compania_id)."/$usuario_img";

			// Construir URL completa para la portada
			$portada_url = "";
			if (!empty($usuario_portada) && $usuario_portada != '1.png') {
				$portada_url = ObtenerUrlArch($compania_id)."/$usuario_portada";
			}

			$usuario = $usuario_nombre." ".$usuario_apellido;

			if ($pagoverdatos_id=="" && $compania_id=="387"){
				$usuario_email = "";
				$usuario_whatsapp = "";
				$usuario_telf = "";
			}

			$usuarioweb = "https://www.vtdesarrollo.com/?agente=$getalias";

		}

		if ($gettipo=="proyecto"){

			// Se consulta la cantidad de proyectos que tiene activo
			$arrresultado = $conexion->doSelect("count(*) as total",
			"proyecto
				inner join lista estatus on estatus.lista_id = proyecto.estatus_id
			",
			"proyecto.proy_activo = '1' and proyecto.usuario_id = '$getusuariodestino_id' and estatus.lista_cod in (1) ");
			foreach($arrresultado as $i=>$valor){
				$empleador_proyectospublicados = utf8_encode($valor["total"]);
			}

			// Se consulta la cantidad de proyectos que tiene finalizado
			$arrresultado = $conexion->doSelect("count(*) as total",
			"proyecto
				inner join lista estatus on estatus.lista_id = proyecto.estatus_id
			",
			"proyecto.proy_activo = '1' and proyecto.usuario_id = '$getusuariodestino_id' and estatus.lista_cod in (4,5) ");
			foreach($arrresultado as $i=>$valor){
				$empleador_proyectosterminados = utf8_encode($valor["total"]);
			}


			// Se consulta la cantidad de proyectos que ha realizado como trabajador
			$arrresultado = $conexion->doSelect("count(*) as total",
			"propuesta_proyecto
				inner join proyecto on proyecto.proy_id = propuesta_proyecto.proy_id
				inner join lista estatus on estatus.lista_id = propuesta_proyecto.estatus_id
			",
			"propuesta_proyecto.propuestaproy_activo = '1' and propuesta_proyecto.usuario_id = '$getusuariodestino_id' and estatus.lista_cod in (7,8) ");
			foreach($arrresultado as $i=>$valor){
				$trabajador_proyectosterminados = utf8_encode($valor["total"]);
			}

			// Se consulta la cantidad de proyectos que tiene como trabajador en curso
			$arrresultado = $conexion->doSelect("count(*) as total",
			"propuesta_proyecto
				inner join proyecto on proyecto.proy_id = propuesta_proyecto.proy_id
				inner join lista estatus on estatus.lista_id = propuesta_proyecto.estatus_id
			",
			"propuesta_proyecto.propuestaproy_activo = '1' and propuesta_proyecto.usuario_id = '$getusuariodestino_id' and estatus.lista_cod in (6) ");
			foreach($arrresultado as $i=>$valor){
				$trabajador_proyectosencurso = utf8_encode($valor["total"]);
			}


			// Se consulta promedio como empleador
			$arrresultado = $conexion->doSelect("avg(usuariorating_valor) as total",
			"usuariorating
				inner join usuarioratingservicio on usuarioratingservicio.usuariorating_id = usuariorating.usuariorating_id 
				inner join proyecto on proyecto.proy_id = usuarioratingservicio.solicitudserv_id	
			", 
			"usuariorating_activo = '1' and proyecto.usuario_id = '$getusuariodestino_id' and usuariorating.usuario_iddestino = '$getusuariodestino_id'");	    	
			foreach($arrresultado as $i=>$valor){  
				$empleador_promediocalificacion = utf8_encode($valor["total"]);
			}	

			if ($empleador_promediocalificacion==""){
				$empleador_promediocalificacion = 0;
			}

			// Se consulta total de comentarios como empleador
			$arrresultado = $conexion->doSelect("count(*) as total",
			"usuariorating
				inner join usuarioratingservicio on usuarioratingservicio.usuariorating_id = usuariorating.usuariorating_id 
				inner join proyecto on proyecto.proy_id = usuarioratingservicio.solicitudserv_id	
			", 
			"usuariorating_activo = '1' and proyecto.usuario_id = '$getusuariodestino_id' and usuariorating.usuario_iddestino = '$getusuariodestino_id'");	    	
			foreach($arrresultado as $i=>$valor){  
				$empleador_totalcalificaciones = utf8_encode($valor["total"]);
			}	



			// Se consulta promedio como trabajador
			$arrresultado = $conexion->doSelect("avg(usuariorating_valor) as total",
			"usuariorating
				inner join usuarioratingservicio on usuarioratingservicio.usuariorating_id = usuariorating.usuariorating_id 
				inner join proyecto on proyecto.proy_id = usuarioratingservicio.solicitudserv_id	
				inner join propuesta_proyecto on propuesta_proyecto.proy_id = proyecto.proy_id
			", 
			"usuariorating_activo = '1' and propuesta_proyecto.usuario_id = '$getusuariodestino_id' and usuariorating.usuario_iddestino = '$getusuariodestino_id'");	    	
			foreach($arrresultado as $i=>$valor){  
				$trabajador_promediocalificacion = utf8_encode($valor["total"]);
			}	

			if ($trabajador_promediocalificacion==""){
				$trabajador_promediocalificacion = 0;
			}

			// Se consulta total como trabajador
			$arrresultado = $conexion->doSelect("count(*) as total",
			"usuariorating
				inner join usuarioratingservicio on usuarioratingservicio.usuariorating_id = usuariorating.usuariorating_id 
				inner join proyecto on proyecto.proy_id = usuarioratingservicio.solicitudserv_id	
				inner join propuesta_proyecto on propuesta_proyecto.proy_id = proyecto.proy_id
			", 
			"usuariorating_activo = '1' and propuesta_proyecto.usuario_id = '$getusuariodestino_id' and usuariorating.usuario_iddestino = '$getusuariodestino_id'");	    	
			foreach($arrresultado as $i=>$valor){  
				$trabajador_totalcalificaciones = utf8_encode($valor["total"]);
			}	

			if ($usuario_id=="5271"){
				$empleador_proyectospublicados = 1;
			}

			$dataproyectos = array(
				"empleador_proyectospublicados" => $empleador_proyectospublicados,
				"empleador_proyectosterminados" => $empleador_proyectosterminados,
				"empleador_promediocalificacion" => $empleador_promediocalificacion,
				"empleador_totalcalificaciones" => $empleador_totalcalificaciones,

				"trabajador_proyectosterminados" => $trabajador_proyectosterminados,
				"trabajador_proyectosencurso" => $trabajador_proyectosencurso,
				"trabajador_promediocalificacion" => $trabajador_promediocalificacion,
				"trabajador_totalcalificaciones" => $trabajador_totalcalificaciones
				
			);

		}

		$l_moneda_id = ObtenerMonedaPrincipalId($cuenta_id, $compania_id);
		$arrresultado = $conexion->doSelect("lista_id, lista_nombre, lista_nombredos",
		"lista", 
		"lista_id = '$l_moneda_id'");	    	
		foreach($arrresultado as $i=>$valor){  
			$moneda_id = utf8_encode($valor["lista_id"]);
			$moneda_nombre = utf8_encode($valor["lista_nombre"]);
			$moneda_siglas = utf8_encode($valor["lista_nombredos"]);
		}	

		$pais_img = "https://www.desimlatam.com/assets/images/flags/$pais_id.png";	
		
		
		// Direcciones
		$arrresultado = $conexion->doSelect("
		X(usuariodireccion.usuariodireccion_point) as latitud,
		Y(usuariodireccion.usuariodireccion_point) as longitud,
		usuariodireccion.usuariodireccion_dirmapa, 
		usuariodireccion.usuariodireccion_contactonombre, 
		usuariodireccion.usuariodireccion_contactotelf,
		usuariodireccion.usuariodireccion_observacion",
		"usuariodireccion",
		"usuariodireccion_activo = '1' and usuario_id = '$usuario_id'");

		$dataDirecciones = array();

		foreach($arrresultado as $i=>$valor){
			$latitud = floatval($valor["latitud"]);	
			$longitud = floatval($valor["longitud"]);	
			$usuariodireccion_dirmapa = utf8_encode($valor["usuariodireccion_dirmapa"]);	
			$usuariodireccion_contactonombre = utf8_encode($valor["usuariodireccion_contactonombre"]);	
			$usuariodireccion_contactotelf = utf8_encode($valor["usuariodireccion_contactotelf"]);	
			$usuariodireccion_observacion = utf8_encode($valor["usuariodireccion_observacion"]);

			$valoresDe = array(
				"latitud" => $latitud,
				"longitud" => $longitud,
				"usuariodireccion_dirmapa" => $usuariodireccion_dirmapa,
				"usuariodireccion_contactonombre" => $usuariodireccion_contactonombre,
				"usuariodireccion_contactotelf" => $usuariodireccion_contactotelf,
				"usuariodireccion_observacion" => $usuariodireccion_observacion
			);

			array_push($dataDirecciones, $valoresDe);
		} 

		
		$direcciones = $dataDirecciones;

		if ($usuario_telf==""){
			$usuario_whatsapp = $usuario_telf;
		}

		$data = array(
			"id" => $usuario_id,
			"usuario_id" => $usuario_id,

			"latitud" => $latitud,
			"longitud" => $longitud,
			"usuariodireccion_dirmapa" => $usuariodireccion_dirmapa,
			"usuariodireccion_contactonombre" => $usuariodireccion_contactonombre,
			"usuariodireccion_contactotelf" => $usuariodireccion_contactotelf,
			"usuariodireccion_observacion" => $usuariodireccion_observacion,
			"usuario_rating" => $usuario_rating,

			"usuario_cvu" => $usuario_cvu,
			"usuario_aliasbanco" => $usuario_aliasbanco,

			"usuario" => $usuario,
			"nombre" => $usuario_nombre,
			"apellido" => $usuario_apellido,
			"email" => $usuario_email,
			"alias" => $usuario_alias,
			"sexo_id" => $sexo_id,
			"webusuario" => $usuarioweb,
			"telf" => $usuario_telf,
			"whatsapp" => $usuario_whatsapp,
			"representantelegal" => $usuario_representantelegal,
			"empresa" => $usuario_empresa,
			"direccion" => $usuario_direccion,
			"documento" => $usuario_documento,
			"estatuscod" => $estatuscod,
			"estatusnombre" => $estatusnombre,
			"registrado" => $usuario_fechareg,
			"estatusconductorcod" => $estatusconductorcod,
			"imagen" => $imagen,
			"pais_id" => $pais_id,
			"pais_nombre" => $pais_nombre,
			"pais_img" => $pais_img,
			"l_zonahoraria_id" => $l_zonahoraria_id,
			"zonahoraria_nombre" => $zonahoraria_nombre,
			"moneda_id" => $moneda_id,
			"moneda_nombre" => $moneda_nombre,
			"moneda_siglas" => $moneda_siglas,
			"resumen" => $resumen,
			"descrip" => $descrip,
			"proyectos" => $dataproyectos,
			"categ_id" => $categ_id,
			"categ_nombre" => $categ_nombre,
			"direcciones" => $direcciones,

			// Campos de La Kress
			"username" => $usuario_username,
			"bio" => $usuario_bio,
			"ubicacion" => $usuario_ubicacion,
			"portada" => $portada_url,
			"portada_archivo" => $usuario_portada,
			"coverimg" => $usuario_coverimg,
			"verificado" => $usuario_verificado,
			"premium" => $usuario_premium,
			"sitio_web" => $usuario_sitio_web,
			"fecha_nacimiento" => $usuario_fecha_nacimiento,
			"perfil_idorig" => $perfil_idorig,

			// Estadísticas de La Kress
			"estadisticas" => array(
				"publicaciones" => $total_publicaciones,
				"seguidores" => $total_seguidores,
				"siguiendo" => $total_siguiendo,
				"likes" => $total_likes,
				"comentarios" => $total_comentarios,
				"vistas" => $total_vistas,
				"suscriptores" => $total_suscriptores,
				"ganancias" => $total_ganancias
			),

			// Balance
			"balance" => array(
				"disponible" => $balance_disponible,
				"pendiente" => $balance_pendiente,
				"moneda" => $moneda_siglas
			)
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