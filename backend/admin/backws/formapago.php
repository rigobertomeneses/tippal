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

if ($metodo=="POST"){// Consultar Forma de Pago

	$valoresPost = json_decode(file_get_contents('php://input'), true);
	(isset($valoresPost['token'])) ? $token=$valoresPost['token'] :$token='';
	(isset($valoresPost['tipo'])) ? $tipo=$valoresPost['tipo'] :$tipo='';
	(isset($valoresPost['unico'])) ? $unico=$valoresPost['unico'] :$unico='';
	(isset($valoresPost['id'])) ? $getformapago_id=$valoresPost['id'] :$getformapago_id='';
	(isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';

	$compania = $compania_id;
	if ($compania_id==""){
		$compania_id = 0;
	}

	/* if ($token==""){
		$valores = array(
			"code" => 104,
			"message" => "Token no encontrado. url: formapago",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	}
 */
	$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

	$conexion = new ConexionBd();

	// and usuario_codverif = '$token'

	$arrresultado2 = $conexion->doSelect("compania.compania_id, compania.cuenta_id",
	"compania",
	"compania_id = '$compania_id'");
	foreach($arrresultado2 as $n=>$valor2){	      
		$usuario_id = utf8_encode($valor2["usuario_id"]);					
		$cuenta_id = utf8_encode($valor2["cuenta_id"]);		
		//$compania_id = utf8_encode($valor2["compania_id"]);					
	}

	if ($compania_id=="373"){$cuenta_id=44;}

/* 
	if ($usuario_id==""){
		$valores = array(
			"code" => 103,
			"message" => "Usuario / Token no activo. url: formapago",
			"data" => [],
		);

		$resultado = json_encode($valores);
		echo $resultado;
		exit();
	} */
	

	$arrresultado = obtenerFormaPagoPagar_2($modulo_id, $elemento_id, $cuenta_id, $compania_id, $getformapago_id, $tipo);

	
	$total = count($arrresultado);		


	if($total>0){

		$datatotal = array();

		foreach($arrresultado as $i=>$valor){

			$existe = 1;

			
			$listaformapagooriginal_cod = utf8_encode($valor["listaformapagooriginal_cod"]);  
	
			$cuenta_idsistema = utf8_encode($valor["cuenta_idsistema"]);  
			$compania_idsistema = utf8_encode($valor["compania_idsistema"]);      
	
			$listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
			$listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
			$listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
			$listacuenta_eliminado = utf8_encode($valor["listacuenta_eliminado"]);  
	
			if ($listacuenta_eliminado=="1"){
			  continue;
			}
	
	
			$listacuenta_orden = utf8_encode($valor["listacuenta_orden"]); 
			$listacuenta_img = utf8_encode($valor["listacuenta_img"]); 
	
			$t_cuenta_id = utf8_encode($valor["cuenta_id"]);
			$t_compania_id = utf8_encode($valor["compania_id"]);    
			
			$lista_id = utf8_encode($valor["lista_id"]);
			$lista_nombre = utf8_encode($valor["lista_nombre"]);
			$lista_img = utf8_encode($valor["lista_img"]);  
			$lista_orden = utf8_encode($valor["lista_orden"]);  
			$lista_activo = utf8_encode($valor["lista_activo"]);      
			$lista_ppal = utf8_encode($valor["lista_ppal"]);      
			$lista_cod = utf8_encode($valor["lista_cod"]);     

		 
			$cuenta_nombre = utf8_encode($valor["cuenta_nombre"]);
			$cuenta_apellido = utf8_encode($valor["cuenta_apellido"]);
			$cuenta_codigo = utf8_encode($valor["cuenta_codigo"]);
			$cuenta = $cuenta_nombre." ".$cuenta_apellido." ";
			$compania_nombre = utf8_encode($valor["compania_nombre"]);
	
	
			$cuentasistema_nombre = utf8_encode($valor["cuentasistema_nombre"]);
			$cuentasistema_apellido = utf8_encode($valor["cuentasistema_apellido"]);
			$cuentasistema_codigo = utf8_encode($valor["cuentasistema_codigo"]);
			$cuentasistema = $cuentasistema_nombre." ".$cuentasistema_apellido." ";
			$companiasistema_nombre = utf8_encode($valor["companiasistema_nombre"]);
	
			$lista_activooriginal = $lista_activo;
	
			$listaformapago_id = utf8_encode($valor["listaformapago_id"]);
			$l_formapago_id = utf8_encode($valor["l_formapago_id"]);
			$listaformapago_titular = utf8_encode($valor["listaformapago_titular"]);
			$listaformapago_documento = utf8_encode($valor["listaformapago_documento"]);
			$listaformapago_email = utf8_encode($valor["listaformapago_email"]);
			$listaformapago_banco = utf8_encode($valor["listaformapago_banco"]);
			$listaformapago_tipocuenta = utf8_encode($valor["listaformapago_tipocuenta"]);
			$listaformapago_nrocuenta = utf8_encode($valor["listaformapago_nrocuenta"]);
			$listaformapago_otros = utf8_encode($valor["listaformapago_otros"]);
			$listaformapago_fechareg = utf8_encode($valor["listaformapago_fechareg"]);
	
			if ($listacuenta_id!=""){
			  $lista_nombre = $listacuenta_nombre;
			  $lista_orden = $listacuenta_orden;
			  $lista_img = $listacuenta_img;
			  $lista_activo = $listacuenta_activo;
			}
		  
			$imagen = ObtenerUrlArch($compania)."/$lista_img";
		   
			$divformapago  ="";
			
			//if ($formapago_descrip!=""){$divformapago .= " <strong>Dirección:</strong> $formapago_descrip <br>";}


			if ($listaformapago_email!=""){$listaformapago_email = "".$listaformapago_email;}
			if ($listaformapago_banco!=""){$listaformapago_banco = "".$listaformapago_banco;}			
			if ($listaformapago_titular!=""){$listaformapago_titular = "".$listaformapago_titular;}
			if ($listaformapago_tipocuenta!=""){$listaformapago_tipocuenta = "".$listaformapago_banco;}
			if ($listaformapago_nrocuenta!=""){$listaformapago_nrocuenta = "".$listaformapago_nrocuenta;}
			if ($listaformapago_documento!=""){$listaformapago_documento = "".$listaformapago_documento;}
			if ($listaformapago_otros!=""){$listaformapago_otros = "".$listaformapago_otros;}
				  
	
			$formapagodetalles = "
				<div id='div_formapago$formapago_id' style='font-size: 16px; padding-top: 10px'>          
					$divformapago
				</div>
			  ";

			$detalles = array(
				"email" => $listaformapago_email,
				"banco" => $listaformapago_banco,	
				"titular" => $listaformapago_titular,	
				"tipocuenta" => $listaformapago_tipocuenta,	
				"nrocuenta" => $listaformapago_nrocuenta,	
				"documento" => $listaformapago_documento,	
				"observaciones" => $listaformapago_otros	
			);
			  
			  
			$data = array(
				"id" => $lista_id,
				"nombre" => $lista_nombre,	
				"formapago" => $lista_nombre,	
				"formapagocod" => $listaformapagooriginal_cod,	
				"imagen" => $imagen,		
				"detalles" => $detalles		
			);

			array_push($datatotal, $data);

			if ($unico=="1"){
				break;
			}


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



function obtenerFormaPagoPagar_2($modulo_id=null, $elemento_id=null, $t_cuenta_id=null, $t_compania_id=null, $getformapago_id=null, $tipo=null){

    $conexion = new ConexionBd();

    $existe = 0;	

	$where = " and listacuenta.cuenta_id = '$t_cuenta_id' and listacuenta.compania_id = '$t_compania_id' ";
	$wherecuenta = " and listacuenta.cuenta_id = '$t_cuenta_id' ";
	$wherecompania = " and listacuenta.compania_id = '$t_compania_id' ";  	
	$wherelistacuenta = " and listacuenta.cuenta_id = '$t_cuenta_id' and listacuenta.compania_id = '$t_compania_id'   ";
	$wherelistaactivo = " and lista.lista_activo = '1' ";

    if ($tipo!=""){

		if ($tipo=="cargarpago"){
			$wheretipo = " and listacuentarel.tipolista_id = '271' ";
		}
		else if ($tipo=="depositar"){
			$wheretipo = " and listacuentarel.tipolista_id = '270' ";
		}		
		else if ($tipo=="plansuscripcion"){
			$wheretipo = " and listacuentarel.tipolista_id = '271' ";
		}		
		else if ($tipo=="1"){
			$wheretipo = " and listacuentarel.tipolista_id = '271' ";
		}else if ($tipo=="2"){
			$wheretipo = " and listacuentarel.tipolista_id = '272' ";
		}else if ($tipo=="3"){
			$wheretipo = " and listacuentarel.tipolista_id = '270' ";
		}else if ($tipo=="gastogasolina"){
			$wheretipo = " and listacuentarel.tipolista_id = '271' ";
		}else  {
			$wheretipo = " and listacuentarel.tipolista_id = '271' ";
		}
	}else  {
		$wheretipo = " and listacuentarel.tipolista_id = '270' ";
	}

	if ($getformapago_id!=""){
		$where .= " and lista.lista_id = '$getformapago_id' ";
	}

	$formatofechaSQL = formatofechaSQL($compania_id);

    $arrresultado = $conexion->doSelect("

		lista.lista_id, lista.lista_nombre,
		lista.lista_cod,
		lista.lista_img, lista.lista_orden, lista.lista_activo, lista.lista_ppal,			
		lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema,
				
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
		DATE_FORMAT(listaformapago_fechareg,'$formatofechaSQL %H:%i:%s') as listaformapago_fechareg,
		listaformapagooriginal.lista_cod as listaformapagooriginal_cod

	    ",
		"
		lista 

			inner join usuario cuentasistema on cuentasistema.usuario_id = lista.cuenta_id
	    	inner join compania companiasistema on companiasistema.compania_id = lista.compania_id
			inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel_activo = 1

			left join listacuenta on listacuenta.lista_id = lista.lista_id
			$wherelistacuenta

			left join listaformapago on listaformapago.l_formapago_id = lista.lista_id
					  and listaformapago.listacuenta_id = listacuenta.listacuenta_id
					  
			left join usuario cuenta on cuenta.usuario_id = listacuenta.cuenta_id
	    	left join compania on compania.compania_id = listacuenta.compania_id	    	    	

	    	$wherecuenta
		    $wherecompania

			left join lista listaformapagooriginal on listaformapagooriginal.lista_id = lista.lista_idrel

		",
		"lista.lista_eliminado = '0'   $where  and ((lista.lista_ppal = '1' $wherelistaactivo) or (lista.lista_ppal = '0' ))  $wheretipo ", null, "lista.lista_orden asc");

		//echo $where;
		//exit();

	foreach($arrresultado as $i=>$valor){

		$listaformapagooriginal_cod = utf8_encode($valor["listaformapagooriginal_cod"]);  
		$cuenta_idsistema = utf8_encode($valor["cuenta_idsistema"]);  
		$compania_idsistema = utf8_encode($valor["compania_idsistema"]);  		

		$listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
		$listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
		$listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
		$listacuenta_eliminado = utf8_encode($valor["listacuenta_eliminado"]);  
		$listacuenta_orden = utf8_encode($valor["listacuenta_orden"]); 
		$listacuenta_img = utf8_encode($valor["listacuenta_img"]); 

		if ($listacuenta_eliminado=="1"){
			continue;
		}

		$t_cuenta_id = utf8_encode($valor["cuenta_id"]);
		$t_compania_id = utf8_encode($valor["compania_id"]);		
		
		$lista_id = utf8_encode($valor["lista_id"]);
		$lista_nombre = utf8_encode($valor["lista_nombre"]);
		$lista_cod = utf8_encode($valor["lista_cod"]);
		$lista_img = utf8_encode($valor["lista_img"]);	
		$lista_orden = utf8_encode($valor["lista_orden"]);	
		$lista_activo = utf8_encode($valor["lista_activo"]);			
		$lista_ppal = utf8_encode($valor["lista_ppal"]);			

		$cuenta_nombre = utf8_encode($valor["cuenta_nombre"]);
		$cuenta_apellido = utf8_encode($valor["cuenta_apellido"]);
		$cuenta_codigo = utf8_encode($valor["cuenta_codigo"]);
		$cuenta = $cuenta_nombre." ".$cuenta_apellido." ";
		$compania_nombre = utf8_encode($valor["compania_nombre"]);


		$cuentasistema_nombre = utf8_encode($valor["cuentasistema_nombre"]);
		$cuentasistema_apellido = utf8_encode($valor["cuentasistema_apellido"]);
		$cuentasistema_codigo = utf8_encode($valor["cuentasistema_codigo"]);
		$cuentasistema = $cuentasistema_nombre." ".$cuentasistema_apellido." ";
		$companiasistema_nombre = utf8_encode($valor["companiasistema_nombre"]);

		$lista_activooriginal = $lista_activo;

		$listaformapago_id = utf8_encode($valor["listaformapago_id"]);
		$l_formapago_id = utf8_encode($valor["l_formapago_id"]);
		$listaformapago_titular = utf8_encode($valor["listaformapago_titular"]);
		$listaformapago_documento = utf8_encode($valor["listaformapago_documento"]);
		$listaformapago_email = utf8_encode($valor["listaformapago_email"]);
		$listaformapago_banco = utf8_encode($valor["listaformapago_banco"]);
		$listaformapago_tipocuenta = utf8_encode($valor["listaformapago_tipocuenta"]);
		$listaformapago_nrocuenta = utf8_encode($valor["listaformapago_nrocuenta"]);
		$listaformapago_otros = utf8_encode($valor["listaformapago_otros"]);
		$listaformapago_fechareg = utf8_encode($valor["listaformapago_fechareg"]);



		if ($listacuenta_id!=""){
			$lista_nombre = $listacuenta_nombre;
			$lista_orden = $listacuenta_orden;
			$lista_img = $listacuenta_img;
			$lista_activo = $listacuenta_activo;
		}

		if ($lista_ppal=="1" && $t_cuenta_id==""){ // Es porque no esta personalizado por la empresa
			$cuenta = $cuentasistema;
			$compania_nombre = $companiasistema_nombre;
		}
	
	
		if ($lista_activo=="0"){
			$activo = "<i onclick='cambiarestatuslista(\"".$lista_id."\",\"".$t_cuenta_id."\",\"".$t_compania_id."\",1)' title='Deshabilitar' class='fa fa-minus btn-deshabilitar'></i>";
		}else{
			$activo = "<i onclick='cambiarestatuslista(\"".$lista_id."\",\"".$t_cuenta_id."\",\"".$t_compania_id."\",0)' title='Habilitar' class='fa fa-check btn-habilitar'></i>";
		}
		
		$accioneliminar = "<i onclick='eliminarlista(\"".$lista_id."\",\"".$t_cuenta_id."\",\"".$t_compania_id."\",0)' title='Eliminar?' class='fa fa-trash btn-eliminar'></i>";	

		$modificar = "<a href='modificarformapago?id=$lista_id&lid=$listacuenta_id'><i title='Ver' class='fa fa-edit btn-modificar'></i></a>";

		$imagen = "<img src='arch/$lista_img' style='height: 80px'";
		

		if (P_Mod!="1"){$modificar = ""; $activo = "";}
		if (P_Eli!="1"){$accioneliminar = ""; $activo = "";}

		$mostrarcolumnacuenta = "<td>$cuenta </td>";
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

		if ($_COOKIE[iniuser]=="44"){			
			//$accioneliminar = "";
			//$activo = "";
		}

		

		$textohtml .= "
					 <tr>			          
						$mostrarcolumnacuenta
						$mostrarcolumnacompania
						<td style='text-align: center'>$lista_nombre <br> $imagen</td>						
						<td style='text-align: center'>$modificar &nbsp $activo &nbsp $accioneliminar</td>
				      </tr>
				";

	}

	return $arrresultado;

}

?>