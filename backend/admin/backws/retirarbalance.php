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

if ($metodo=="POST"){// Registrar Pago

	$valoresPost = json_decode(file_get_contents('php://input'), true);

	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['monto'])) ? $monto=$valoresPost['monto'] :$monto='';
	(isset($valoresPost['usuarioretiroid'])) ? $usuarioretiroid=$valoresPost['usuarioretiroid'] :$usuarioretiroid='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
	if ($compania_id==""){
		$compania_id = 0;
	}

	if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. retirarbalance",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}
	
	$conexion = new ConexionBd();
	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$arrresultado2 = $conexion->doSelect("usuario_id, cuenta_id","usuario","usuario_activo = '1' and usuario_codverif = '$token'  and usuario.compania_id = '$compania_id' ");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);	
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);					
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. retirarbalance",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}

	$instancialista = new Lista();

	$obtenerCodigoLista = 1;
	$obtenerTipoLista = 55;
	$estatussidpago = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

	$usuariobalanceretiro_cod = 0;

	$arrresultado2 = $conexion->doSelect("max(CONVERT(usuariobalanceretiro_cod, UNSIGNED)) as usuariobalanceretiro_cod",
		"usuariobalanceretiro","cuenta_id = '$cuenta_id' and compania_id = '$compania_id'");
	if (count($arrresultado2)>0){				
		foreach($arrresultado2 as $n=>$valor2){	      
			$usuariobalanceretiro_cod = utf8_encode($valor2["usuariobalanceretiro_cod"]);					
		}
		if ($usuariobalanceretiro_cod==""){$usuariobalanceretiro_cod=0;}
	}

	$usuariobalanceretiro_cod = $usuariobalanceretiro_cod + 1;

	//echo "monto:$monto";
	$monto = str_replace(".", "", $monto);
	$monto = str_replace(",", ".", $monto);
	//exit();

	// 1.000,00


	// Guardo en Movimiento del destino
	$obtenerCodigoLista = 1; // Retiro Pendiente
	$obtenerTipoLista = 64; // Estatus de Retiros
	$estatusidretiro = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
 
	$moneda = ObtenerMonedaPrincipalIdTaxi($cuenta_id, $compania_id);
	$elemento_id = 0;
	$l_formapago_id = 0;
	$pago_procesado = 1;

	$arrresultado2 = $conexion->doSelect("usuarioretiro.l_formapago_id",
		"usuarioretiro","usuarioretiro_id  = '$usuarioretiroid' ");
	if (count($arrresultado2)>0){				
		foreach($arrresultado2 as $n=>$valor2){	      
			$l_formapago_id = utf8_encode($valor2["l_formapago_id"]);					
		}
	}

	/* $arrresultado2 = $conexion->doSelect("l_moneda_id",
		"usuariobalance","usuariobalance_activo = '1' and usuario_id  = '$usuario_id' ");
	if (count($arrresultado2)>0){				
		foreach($arrresultado2 as $n=>$valor2){	      
			$moneda = utf8_encode($valor2["l_moneda_id"]);					
		}
	}	

	if ($compania_id=="395"){

		// Consulto moneda del pais del usuario
		$arrresultado2 = $conexion->doSelect("pais.l_moneda_id
		",
		"usuario
			left join pais on pais.pais_id = usuario.pais_id
		", "usuario.usuario_id = '$usuario_id'");
		if (count($arrresultado2)>0){
			foreach($arrresultado2 as $i=>$valor){
				$moneda = ($valor["l_moneda_id"]);
			}
		}
	}

	// Si no se encontró moneda, obtener USD por defecto
	if ($moneda == "" || $moneda == "0" || $moneda == 0) {
		$arrresultado2 = $conexion->doSelect("lista_id", "lista", "tipolista_id = '44' AND lista_cod = '1' AND lista_activo = '1'");
		if (count($arrresultado2) > 0) {
			foreach($arrresultado2 as $i=>$valor) {
				$moneda = $valor["lista_id"];
			}
		}
		// Si aún no hay moneda, usar 0
		if ($moneda == "") {
			$moneda = 0;
		}
	}

	if ($moneda == "0"){
		$moneda = ObtenerMonedaPrincipalIdTaxi($cuenta_id, $compania_id);
	}

	if ($moneda == "") {
		$moneda = 0;
	}
 */


	$resultado = $conexion->doInsert("
	usuariobalanceretiro
		(usuario_id, usuarioretiro_id, usuariobalanceretiro_monto, usuariobalanceretiro_fechareg, 
		l_estatus_id, usuariobalanceretiro_activo, usuariobalanceretiro_eliminado, 
		usuariobalanceretiro_observ, usuariobalanceretiro_procesado, 
		cuenta_id, compania_id, usuariobalanceretiro_cod, l_moneda_id, l_formapago_id)
	",
		"'$usuario_id', '$usuarioretiroid', '$monto','$fechaactual',
		'$estatusidretiro', '1','0', 
		'', '0',
		'$cuenta_id','$compania_id','$usuariobalanceretiro_cod','$moneda','$l_formapago_id'
	");


	$arrresultado2 = $conexion->doSelect("max(usuariobalanceretiro_id) as usuariobalanceretiro_id","usuariobalanceretiro");
	if (count($arrresultado2)>0){
		foreach($arrresultado2 as $i=>$valor){
			$usuariobalanceretiro_id = ($valor["usuariobalanceretiro_id"]);
		}

		// Retiro del saldo disponible
		$arrresultado = $conexion->doSelect("usuariobalance_disponible, usuariobalance_bloqueado, l_moneda_id, usuariobalance_total","usuariobalance","usuariobalance_activo = '1' and usuario_id='$usuario_id'");
		if (count($arrresultado)>0){	
			foreach($arrresultado as $i=>$valor){	      
				$usuariobalance_disponible = utf8_encode($valor["usuariobalance_disponible"]);		
				$usuariobalance_bloqueado = utf8_encode($valor["usuariobalance_bloqueado"]);	
				$usuariobalance_total = utf8_encode($valor["usuariobalance_total"]);	

				if ($usuariobalance_disponible==""){$usuariobalance_disponible=0;}
				if ($usuariobalance_total==""){$usuariobalance_total=0;}
				if ($usuariobalance_bloqueado==""){$usuariobalance_bloqueado=0;}
			}


			if ($compania_id=="395"){
				
				// Consulto moneda del pais del usuario
				$arrresultado2 = $conexion->doSelect("pais.l_moneda_id
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
				$montoenmoneda = 0;

				if (count($arrresultado2)>0){
					foreach($arrresultado2 as $i=>$valor){
						$tasacambio_id = ($valor["tasacambio_id"]);
						$tasacambio_fechareg = ($valor["tasacambio_fechareg"]);
						$monedaorigen_nombredos = ($valor["monedaorigen_nombredos"]);
						$monedadestino_nombredos = ($valor["monedadestino_nombredos"]);
						$tasacambio_ventavalor = ($valor["tasacambio_ventavalor"]);				
					}

					$montoenmoneda = round(($monto / $tasacambio_ventavalor),2);
				}

				$usuariobalance_disponible = $usuariobalance_disponible - $montoenmoneda;
				$usuariobalance_total = $usuariobalance_total - $montoenmoneda;
				$usuariobalance_bloqueado = $usuariobalance_bloqueado - $montoenmoneda;

				$resultado = $conexion->doUpdate("usuariobalance", "
				usuariobalance_disponible ='$usuariobalance_disponible',
				usuariobalance_bloqueado ='$usuariobalance_bloqueado',
				usuariobalance_total ='$usuariobalance_total'
				",
				"usuario_id='$usuario_id'");



			}else{
				$usuariobalance_disponible = $usuariobalance_disponible - $monto;
				$usuariobalance_total = $usuariobalance_total - $monto;
				$usuariobalance_bloqueado = $usuariobalance_bloqueado - $monto;

				$resultado = $conexion->doUpdate("usuariobalance", "
				usuariobalance_disponible ='$usuariobalance_disponible',
				usuariobalance_bloqueado ='$usuariobalance_bloqueado',
				usuariobalance_total ='$usuariobalance_total'
				",
				"usuario_id='$usuario_id'");
				
			}

		}

		// Guardo en Movimiento del destino
		$obtenerCodigoLista = 4; // Retiro
		$obtenerTipoLista = 269; // Tipo de Movimiento
		$tipomovimiento = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

		
		$resultado = $conexion->doInsert("
			movimiento
			(mov_descrip, l_tipomov_id, usuario_id, mov_monto, mov_fecha, 
			elemento_id, l_moneda_id, 
			l_formapago_id, mov_fechareg, mov_activo, mov_eliminado, cuenta_id, compania_id, l_estatus_id)
			",
			"'', '$tipomovimiento', '$usuario_id', '-$monto','$fechaactual', 
			'$usuariobalanceretiro_id','$moneda',
			'$l_formapago_id', '$fechaactual', '1', '0','$cuenta_id','$compania_id','$estatusidretiro'");


		$valores = array(
			"code" => 0,
			"data" => null,
			"message" => "Retiro registrado correctamente"
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



function obtenerFormaPagoPagar2($modulo_id=null, $elemento_id=null, $t_cuenta_id=null, $t_compania_id=null, $getformapago_id=null){

    $conexion = new ConexionBd();

    $existe = 0;
    

    if ($_COOKIE[perfil]=="1"){ // Administrador del Sistema
          
      $whereformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' and listacuenta.compania_id = '$t_compania_id' ";
      $wherecuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' ";
      $wherecompaniaformapago = " and listacuenta.compania_id = '$t_compania_id' ";   
      $wherelistacuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' and listacuenta.compania_id = '$t_compania_id'  ";
      $wherelistaactivoformapago = " and lista.lista_activo = '1' ";

    } else if ($_COOKIE[perfil]=="2"){ // Administrador de Cuenta  
      
      $whereformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'   ";
      $wherecuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' ";
      //$wherecompaniaformapago = " and listacuenta.compania_id = '$t_compania_id' ";   
      $wherelistacuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' and listacuenta.compania_id = '$t_compania_id'  ";
      $wherelistaactivoformapago = " and lista.lista_activo = '1' ";

      if ($t_cuenta_id=="2179"){
        $whereformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'   ";
        $wherecompaniaformapago  ="";
        $wherelistacuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'  ";
      }

    } else if ($_COOKIE[perfil]=="3"){ // Administrador de Compañia  
      
      $whereformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'  and listacuenta.compania_id = '$t_compania_id' ";
      $wherecuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' ";
      $wherecompaniaformapago = " and listacuenta.compania_id = '$t_compania_id' ";   
      $wherelistacuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' and listacuenta.compania_id = '$t_compania_id'  ";
      $wherelistaactivoformapago = " and lista.lista_activo = '1' ";

      if ($t_cuenta_id=="2179"){
        $whereformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'   ";
        $wherecompaniaformapago  ="";
        $wherelistacuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'  ";
      }

    }  else {   

      $whereformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' and listacuenta.compania_id = '$t_compania_id' ";
      $wherecuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' ";
      $wherecompaniaformapago = " and listacuenta.compania_id = '$t_compania_id' ";   
      $wherelistacuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id' and listacuenta.compania_id = '$t_compania_id'  ";
      $wherelistaactivoformapago = " and lista.lista_activo = '1' ";

      if ($t_cuenta_id=="2179"){
        $whereformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'   ";
        $wherecompaniaformapago  ="";
        $wherelistacuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'  ";
      }


    } 

    if ($getformapago_id!=""){
      $whereformapago2 = " and lista.lista_id = '$getformapago_id' ";
    }

    if ($t_cuenta_id=="2179"){
      $whereformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'   ";
      $wherecompaniaformapago  ="";
      $wherelistacuentaformapago = " and listacuenta.cuenta_id = '$t_cuenta_id'  ";

      $whereformapago .= " and lista.lista_id = '2201'";
    }


    

    $arrresultado = $conexion->doSelect("

        lista.lista_id, lista.lista_nombre, lista.lista_img, lista.lista_orden, lista.lista_activo, lista.lista_ppal,     
        lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema, lista.lista_cod,
            
        cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
          cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre as compania_nombre,
          cuenta.usuario_codigo as cuentasistema_codigo, cuenta.usuario_nombre as cuentasistema_nombre,
          cuenta.usuario_apellido as cuentasistema_apellido, companiasistema.compania_nombre as companiasistema_nombre,

          listacuenta.cuenta_id, listacuenta.compania_id,
          listacuenta.listacuenta_id, listacuenta.listacuenta_activo, listacuenta.listacuenta_eliminado, 
          listacuenta.listacuenta_img, listacuenta.listacuenta_orden,
        listacuenta.listacuenta_nombre,
        lista.tipolista_id,

        listaformapago.listaformapago_id, listaformapago.l_formapago_id, listaformapago.listaformapago_titular,
        listaformapago.listaformapago_documento, listaformapago.listaformapago_email, 
        listaformapago.listaformapago_banco, listaformapago.listaformapago_tipocuenta, 
        listaformapago.listaformapago_nrocuenta, listaformapago.listaformapago_otros, 
        listaformapago.usuario_idreg,
        DATE_FORMAT(listaformapago_fechareg,'$formatofechaSQL %H:%i:%s') as listaformapago_fechareg

          ",
        "
        lista 

          inner join usuario cuentasistema on cuentasistema.usuario_id = lista.cuenta_id
            inner join compania companiasistema on companiasistema.compania_id = lista.compania_id              

          inner join listacuenta on listacuenta.lista_id = lista.lista_id
          $wherelistacuentaformapago

          inner join listaformapago on listaformapago.l_formapago_id = lista.lista_id
                and listaformapago.listacuenta_id = listacuenta.listacuenta_id
                
          left join usuario cuenta on cuenta.usuario_id = listacuenta.cuenta_id
            left join compania on compania.compania_id = listacuenta.compania_id              

            $wherecuentaformapago
            $wherecompaniaformapago

            inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.tipolista_id = '118' and listacuentarel_activo = '1'

        ",
        "lista.lista_eliminado = '0' and lista.tipolista_id = '21' $whereformapago2 and ((lista.lista_ppal = '1' $wherelistaactivoformapago) or (lista.lista_ppal = '0' ))  ", null, "lista.lista_orden asc");

	return $arrresultado;
	
}

?>