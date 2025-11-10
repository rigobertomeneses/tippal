<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept");
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

if ($metodo=="POST"){// Consultar Balance

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	(isset($valoresPost['accion'])) ? $accion=$valoresPost['accion'] :$accion='';
	if ($compania_id==""){
		$compania_id = 0;
	}
	
	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: balance",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}


	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	$arrresultado2 = $conexion->doSelect("usuario_id, cuenta_id",
	"usuario","usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);	
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);					
	}

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: balance",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$arrresultado2 = $conexion->doSelect("sum(movimiento.mov_monto) as totalmonto,
	moneda.lista_nombredos as moneda_siglas,
	moneda.lista_nombre as moneda_nombre
	",
	"usuario
		inner join movimiento on movimiento.usuario_id = usuario.usuario_id
		left join lista moneda on moneda.lista_id = movimiento.l_moneda_id
	
	", "usuario.usuario_id = '$usuario_id' and mov_activo = '1' and movimiento.compania_id = '$compania_id'", "moneda.lista_nombredos, moneda.lista_nombre");
	if (count($arrresultado2)>0){
		foreach($arrresultado2 as $i=>$valor){
			$totalmonto = ($valor["totalmonto"]);
			$monto_moneda_siglas = ($valor["moneda_siglas"]);
			$monto_moneda_nombre = ($valor["moneda_nombre"]);
		}
	}

	if ($moneda_siglas==""){
		$moneda_siglas = ObtenerMonedaPrincipal($cuenta_id, $compania_id);
	}

	if ($moneda_siglas==""){$moneda_siglas="$";}
	if ($totalmonto==""){$totalmonto=0;}

	$totalmonto_orig = $totalmonto;
	$totalmonto = number_format_personalizado($totalmonto, $moneda_siglas, null, $compania_id); 
	
	$formatofechaSQL = formatofechaSQL($compania_id);
	
	$arrresultado2 = $conexion->doSelect("usuario.usuario_id, usuario_nombre, usuario_codverif, usuario_emailverif, usuario_email, DATE_FORMAT(usuario_fechareg,'$formatofechaSQL %H:%i:%s') as usuario_fechareg,
	usuariobalance_id, usuariobalance_cantidad, usuariobalance_cantidad2,
	usuariobalance_cantidaddisponible, usuariobalance_cantidadbloqueado,
	usuariobalance_total,  usuariobalance_bloqueado, usuariobalance_disponible, usuariobalance_pendiente,
	moneda.lista_nombre as moneda_nombre, moneda.lista_nombredos as moneda_siglas
	
	",
	"usuario
		left join usuariobalance on usuariobalance.usuario_id = usuario.usuario_id and usuariobalance.compania_id = '$compania_id'
		left join lista moneda on moneda.lista_id = usuariobalance.l_moneda_id
	
	", "usuario.usuario_id = '$usuario_id'");
	if (count($arrresultado2)>0){
		foreach($arrresultado2 as $i=>$valor){
			$usuario_id = ($valor["usuario_id"]);
			$usuario_nombre = ($valor["usuario_nombre"]);
			$usuario_codverif = ($valor["usuario_codverif"]);
			$usuario_emailverif = ($valor["usuario_emailverif"]);
			$usuario_fechareg = ($valor["usuario_fechareg"]);
			$usuario_email = ($valor["usuario_email"]);

			$usuariobalance_cantidad = utf8_encode($valor["usuariobalance_cantidad"]);
			$usuariobalance_cantidad2 = utf8_encode($valor["usuariobalance_cantidad2"]);
			$usuariobalance_cantidaddisponible = utf8_encode($valor["usuariobalance_cantidaddisponible"]);
			$usuariobalance_cantidadbloqueado = utf8_encode($valor["usuariobalance_cantidadbloqueado"]);

			if ($usuariobalance_cantidad==""){$usuariobalance_cantidad=0;}
			if ($usuariobalance_cantidaddisponible==""){$usuariobalance_cantidaddisponible=0;}
			if ($usuariobalance_cantidadbloqueado==""){$usuariobalance_cantidadbloqueado=0;}

			$moneda_nombre = utf8_encode($valor["moneda_nombre"]);
			$moneda_siglas = utf8_encode($valor["moneda_siglas"]);

			if ($moneda_siglas==""){
				$moneda_siglas = ObtenerMonedaPrincipal($cuenta_id, $compania_id);
			}

			$usuariobalance_id = utf8_encode($valor["usuariobalance_id"]);
			$usuariobalance_total = utf8_encode($valor["usuariobalance_total"]);
			$usuariobalance_bloqueado = utf8_encode($valor["usuariobalance_bloqueado"]);
			$usuariobalance_disponible = utf8_encode($valor["usuariobalance_disponible"]);
			$usuariobalance_pendiente = utf8_encode($valor["usuariobalance_pendiente"]);

			if ($usuariobalance_total==""){$usuariobalance_total=0;}
			if ($usuariobalance_bloqueado==""){$usuariobalance_bloqueado=0;}
			if ($usuariobalance_disponible==""){$usuariobalance_disponible=0;}
			if ($usuariobalance_pendiente==""){$usuariobalance_pendiente=0;}
		
			if ($usuariobalance_sucursales==""){$usuariobalance_sucursales=0;}

			$usuariobalance_bloqueadoorig = $usuariobalance_bloqueado;
			$usuariobalance_disponibleorig = $usuariobalance_disponible;
			$usuariobalance_totalorig = $usuariobalance_total;
			$usuariobalance_pendienteorig = $usuariobalance_pendiente;

			if ($moneda_siglas==""){$moneda_siglas= "$";}

			$usuariobalance_total = number_format_personalizado($usuariobalance_total, $moneda_siglas, null, $compania_id);
			$usuariobalance_bloqueado = number_format_personalizado($usuariobalance_bloqueado, $moneda_siglas, null, $compania_id);
			$usuariobalance_disponible = number_format_personalizado($usuariobalance_disponible, $moneda_siglas, null, $compania_id);
			$usuariobalance_pendiente = number_format_personalizado($usuariobalance_pendiente, $moneda_siglas, null, $compania_id); 

		}

		$l_moneda_id= 0;

		// Consulto moneda del pais del usuario
		$arrresultado2 = $conexion->doSelect("
		pais.l_moneda_id
		",
		"usuario
			left join pais on pais.pais_id = usuario.pais_id
		
		", "usuario.usuario_id = '$usuario_id'");
		if (count($arrresultado2)>0){
			foreach($arrresultado2 as $i=>$valor){
				$l_moneda_id = ($valor["l_moneda_id"]);					
			}
		}


		// Consulto tasa de cambio y conversion
		$arrresultado2 = $conexion->doSelect("
		tasacambio_id, l_moneda_idorigen, l_moneda_iddestino, tasacambiofuente_id, 
		tasacambio_ventaporcagregado, tasacambio_ventamontoagregado, 
		tasacambio_ventavalororig, tasacambio_ventavalor, tasacambio_compraporcagregado,
		tasacambio_compramontoagregado, tasacambio_compravalororig, tasacambio_compravalor,
		tasacambio_activo, tasacambio_eliminado,
		DATE_FORMAT(tasacambio_fechareg,'$formatofechaSQL %H:%i:%s') as tasacambio_fechareg,
		monedaorigen.lista_nombredos as monedaorigen_nombredos,
		monedadestino.lista_nombredos as monedadestino_nombredos
		",
		"tasacambio
			left join lista monedaorigen on monedaorigen.lista_id = tasacambio.l_moneda_idorigen
			left join lista monedadestino on monedadestino.lista_id = tasacambio.l_moneda_iddestino
		
		", "tasacambio.compania_id = '$compania_id' and tasacambio.l_moneda_iddestino = '$l_moneda_id' and tasacambio_activo = '1'");
		if (count($arrresultado2)>0){
			foreach($arrresultado2 as $i=>$valor){
				$tasacambio_id = ($valor["tasacambio_id"]);
				$tasacambio_fechareg = ($valor["tasacambio_fechareg"]);
				$monedaorigen_nombredos = ($valor["monedaorigen_nombredos"]);
				$monedadestino_nombredos = ($valor["monedadestino_nombredos"]);
				$tasacambio_ventavalor = ($valor["tasacambio_ventavalor"]);				
			}

			$montoenmonedadestino = $usuariobalance_disponibleorig * $tasacambio_ventavalor;
			$montoenmonedadestinoorig = $montoenmonedadestino;
			$montoenmonedadestinosinmoneda = number_format_personalizado($montoenmonedadestino,"");
			$montoenmonedadestino = number_format_personalizado($montoenmonedadestino,$monedadestino_nombredos);

			$tasacambio_ventavalor = number_format_personalizado($tasacambio_ventavalor,$monedadestino_nombredos);
			
			$tasa = array(
				"tasacambio_id" => $tasacambio_id,
				"tasacambio_fechareg" => $tasacambio_fechareg,
				"monedaorigen" => $monedaorigen_nombredos,	
				"monedadestino" => $monedadestino_nombredos,	
				"tasacambio_ventavalor" => $tasacambio_ventavalor,
				"montoenmonedadestinosinmoneda" => $montoenmonedadestinosinmoneda,
				"montoenmonedadestinoorig" => $montoenmonedadestinoorig,
				"montoenmonedadestino" => $montoenmonedadestino
			);
		}

		$mostrartasacambio = 1;

		

		$data = array(
			"saldo_disponibleoriginal" => $usuariobalance_disponibleorig,
			"saldo_disponible" => $usuariobalance_disponible,
			"saldo_totaloriginal" => $usuariobalance_totalorig,	
			"saldo_total" => $usuariobalance_total,	
			"saldo_bloqueadooriginal" => $usuariobalance_bloqueadoorig,	
			"saldo_bloqueado" => $usuariobalance_bloqueado,	
			"saldo_pendienteoriginal" => $usuariobalance_pendienteorig,				
			"saldo_pendiente" => $usuariobalance_pendiente,
			"totalmonto_orig" => $totalmonto_orig,		
			"cantidadtotal" => $usuariobalance_cantidad,	
			"cantidaddisponible" => $usuariobalance_cantidaddisponible,
			"cantidadbloqueado" => $usuariobalance_cantidadbloqueado,
			"totalmonto" => $totalmonto,	
			"moneda_siglas" => $moneda_siglas,	
			"mostrartasacambio" => $mostrartasacambio,	
			
			"tasacambio" => $tasa,	
			
		);
	
		$valores = array(
			"code" => 0,
			"data" => $data				
		);
		
	}

	
}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>