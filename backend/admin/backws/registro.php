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

//require_once '../lib/twilio-php-main/src/Twilio/autoload.php';


use Twilio\Rest\Client;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

$writer = new PngWriter();


$perfil_cliente = 4;
$perfil_conductor = 10;

$metodo = $_SERVER['REQUEST_METHOD'];

function generateRandomNumber2() {
  $length = 4;
  $characters = '0123456789';
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, strlen($characters) - 1)];
  }
  return $randomString;
}


$valores = array(
	"code" => 101,
	"message" => "Sin permisos",
	"data" => [],
);

$valoresPost = json_decode(file_get_contents('php://input'), true);

(isset($valoresPost['pruebaenvioemail'])) ? $pruebaenvioemail=$valoresPost['pruebaenvioemail'] :$pruebaenvioemail='';

if ($pruebaenvioemail==1){
    $tipoenvio = 1;
}
else if ($metodo=="POST"){// Crear Cliente

    
    
	(isset($valoresPost['nombre'])) ? $nombre=$valoresPost['nombre'] :$nombre='';
    (isset($valoresPost['apellido'])) ? $apellido=$valoresPost['apellido'] :$apellido='';
	(isset($valoresPost['email'])) ? $email=$valoresPost['email'] :$email='';
	(isset($valoresPost['clave'])) ? $clave=$valoresPost['clave'] :$clave='';
    (isset($valoresPost['whatsapp'])) ? $whatsapp=$valoresPost['whatsapp'] :$whatsapp='';
    (isset($valoresPost['referido'])) ? $referido=$valoresPost['referido'] :$referido='';
    (isset($valoresPost['tipo'])) ? $tipo=$valoresPost['tipo'] :$tipo='';
    (isset($valoresPost['ciudad'])) ? $ciudad=$valoresPost['ciudad'] :$ciudad='';    
    (isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
    (isset($valoresPost['tipousuario'])) ? $tipousuario=$valoresPost['tipousuario'] :$tipousuario='';
    (isset($valoresPost['pais'])) ? $pais=$valoresPost['pais'] :$pais='';
    (isset($valoresPost['alias'])) ? $alias=$valoresPost['alias'] :$alias='';
    (isset($valoresPost['sexo'])) ? $sexo=$valoresPost['sexo'] :$sexo='';
    
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

    $nombre = utf8_decode($nombre);
    $apellido = utf8_decode($apellido);
    $email = utf8_decode($email);
    $clave = utf8_decode($clave);
    $whatsapp = utf8_decode($whatsapp);
    $referido = utf8_decode($referido);
    $alias = utf8_decode($alias);
    $whatsapp = str_replace("+", "", $whatsapp);
    if ($ciudad==""){$ciudad=0;}
    if ($pais==""){$pais=0;}
    if ($sexo==""){$sexo=0;}


    if ($tipo==""){$tipo="cliente";}

    if ($tipo=="cliente"){
        $perfilreturn = 4;
    }else if ($tipo=="vendedor"){
        $perfilreturn = 5;
    }else if ($tipo=="trabajador"){
        $perfilreturn = 21;
    }else if ($tipo=="empresa"){
        $perfilreturn = 22;
    }else{
        $perfilreturn = 10;
    }

    if ($compania_id=="444"){
        $perfilreturn = 4;
    }

	$uniqid = uniqid();

    // || $email ==""

	//if($nombre==""  || $apellido ==""  || ($whatsapp =="" && $email == "") || $clave ==""){
    if (1==2){

		$valores = array(
			"code" => 101,
			"message" => "Error: Valores requeridos de Nombre, Apellido, Email, WhatsApp y Clave"
		);

                
        $resultado = json_encode($valores);

        echo $resultado;

        exit();


	}else{

		$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

		$conexion = new ConexionBd();
		
		$usuario_codverif = uniqid();

        if ($email!="" && strlen($whatsapp)>6 ){
            if (strlen($whatsapp)>6){
                $whereverificar = " and (usuario_email = '$email' or usuario_whatsapp = '$whatsapp' ) ";
            }else {
                $whereverificar = " and (usuario_email = '$email') ";
            }            
        }else if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $whereverificar = " and (usuario_email = '$email') ";
        }else if ($whatsapp!=""){
            $whereverificar = " and (usuario_whatsapp = '$whatsapp') ";
        }else if ($whatsapp!=""){
            $whereverificar = " and 1 = 2 ";
        } 

        if ($alias!=""){
            $whereverificar .= " or (usuario_alias = '$alias') ";
        } 

        $formatofechaSQL = formatofechaSQL($compania_id);
		
		$arrresultado2 = $conexion->doSelect("usuario_id, usuario_nombre, usuario_apellido, usuario_codverif, usuario_emailverif, usuario_email, 
			  DATE_FORMAT(usuario_fechareg,'$formatofechaSQL %H:%i:%s') as usuario_fechareg",
              "usuario", 
              "compania_id = '$compania_id' and usuario_eliminado = '0' $whereverificar ");
		$total = count($arrresultado2);
		if (count($arrresultado2)>0){
			foreach($arrresultado2 as $i=>$valor){
				$usuario_id = utf8_encode($valor["usuario_id"]);
				$usuario_nombre = utf8_encode($valor["usuario_nombre"]);
                $usuario_apellido = utf8_encode($valor["usuario_apellido"]);
				$usuario_email = utf8_encode($valor["usuario_email"]);
				$usuario_codverif = utf8_encode($valor["usuario_codverif"]);
				$usuario_emailverif = utf8_encode($valor["usuario_emailverif"]);
				$usuario_fechareg = utf8_encode($valor["usuario_fechareg"]);
			}
			
			$data = array(
        		"nombre" => $usuario_nombre,
                "apellido" => $usuario_apellido,
        		"email" => $usuario_email,	
        		"idusuario" => $usuario_id,	
        	);

			$nombre = $usuario_nombre." ".$usuario_apellido;
			
			$message = "Cliente ya existe.";

			$tipoenvio = 0;
            
            $valores = array(
				"code" => 101,
				"data" => $data,
                "whereverificar" => $whereverificar,
				"message" => $message
			);

            $resultado = json_encode($valores);

            echo $resultado; 

            exit();

		}else{

            
   

            $usuario_idreferido = 0;

            $arrresultado2 = $conexion->doSelect("usuario_id","usuario", "usuario_activo = '1' and compania_id = '$compania_id' and usuario_codreferido = '$referido'");
            if (count($arrresultado2)>0){
                foreach($arrresultado2 as $i=>$valor){
                    $usuario_idreferido = ($valor["usuario_id"]);
                }
            }

            $arrresultado2 = $conexion->doSelect("compania_img, compania_nombre, cuenta_id, compania_email","compania", "compania_id = '$compania_id'");
            if (count($arrresultado2)>0){
                foreach($arrresultado2 as $i=>$valor){
                    $cuenta_id = utf8_encode($valor["cuenta_id"]);
                    $compania_img = utf8_encode($valor["compania_img"]);
                    $compania_nombre = utf8_encode($valor["compania_nombre"]);
                    $compania_email = utf8_encode($valor["compania_email"]);
                }
            }

            $arrresultado2 = $conexion->doSelect("perfil_id","perfil", "perfil_idorig = '$perfilreturn' and compania_id = '$compania_id'");
            if (count($arrresultado2)>0){
                foreach($arrresultado2 as $i=>$valor){
                    $perfil_id = utf8_encode($valor["perfil_id"]);
                }
            }

            if ($perfil_id==""){
                $perfil_id= 227;
            }
			
			$usuario_emailverif = generateRandomNumber2();
            $usuario_codverifwa = generateRandomNumber2();

            $usuario_codreferido = generarId();

            $instancialista = new Lista();

            if ($compania_id=="380" && $perfilreturn=="4"){
                $obtenerCodigoLista = 3; // Confirmado
                $usuario_emailverif = 0; // Para que quede confirmado         
            }else{
                $obtenerCodigoLista = 1; // Por confirmar email
            }

            
            $obtenerTipoLista = 53; // Estatus de Usuarios
            $estatususuarioconfirmaremail = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);

            $obtenerCodigoLista = 2; // No disponible para viajes
            $obtenerTipoLista = 268; // Estatus de Disponibilidad del Conductor
            $estatusdisponibilidad = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
			
            if ($tipousuario==""){
                $obtenerCodigoLista = 1; // Persona
                $obtenerTipoLista = 41; // Tipo de Usuario
                $tipousuario = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
            }


            $usuario_trabajador = 0;
            $usuario_empleador = 0;

            if ($compania_id=="444"){
                if ($tipousuario=="trabajador"){
                    $obtenerCodigoLista = 2; // Proveedor	
                }else {                    
                    $obtenerCodigoLista = 1; // Cliente	
                }
                $obtenerTipoLista = 41; // Tipo de Usuario
                $tipousuario = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista);
            }

            if ($compania_id=="449"){
                if ($tipo=="conductor"){
                    $obtenerCodigoLista = 2; // Conductor	
                }else {                    
                    $obtenerCodigoLista = 1; // Cliente	
                }
                $obtenerTipoLista = 41; // Tipo de Usuario
                $tipousuario = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista, $compania_id);
            }
            


            if ($tipousuario!=""){
                if ($compania_id=="406"){
                    if ($tipousuario=="trabajador"){
                        $obtenerCodigoLista = 1; // Trabajador	
                        $obtenerTipoLista = 41; // Tipo de Usuario
                        $tipousuario = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista, $compania_id);
                        $usuario_trabajador = 1;
                    }else if ($tipousuario=="empleador"){
                        $obtenerCodigoLista = 2; // Empleador	
                        $obtenerTipoLista = 41; // Tipo de Usuario
                        $tipousuario = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista, $compania_id);
                        $usuario_empleador = 1;
                    }else{
                        $obtenerCodigoLista = 2; // Empleador	
                        $obtenerTipoLista = 41; // Tipo de Usuario
                        $tipousuario = $instancialista->ObtenerIdLista($obtenerCodigoLista, $obtenerTipoLista, $compania_id);
                        $usuario_empleador = 1;
                    }
                    
                }

                if ($compania_id=="449"){

                }
            }


            // Busco la zona horaria del pais
            $l_zonahoraria_id = 2033;
            $arrresultado2 = $conexion->doSelect("l_zonahoraria_id","pais", "pais_id = '$pais'");
            if (count($arrresultado2)>0){
                foreach($arrresultado2 as $i=>$valor){
                    $l_zonahoraria_id = utf8_encode($valor["l_zonahoraria_id"]);
                }
            }
            if ($l_zonahoraria_id==""){$l_zonahoraria_id=2033;}

        
            $arrresultado2 = $conexion->doSelect("max(usuario_codigo) as usuario_codigo","usuario", "compania_id = '$compania_id'");
            if (count($arrresultado2)>0){
                foreach($arrresultado2 as $i=>$valor){
                    $usuario_codigo = utf8_encode($valor["usuario_codigo"]);
                }

                if ($usuario_codigo==""){$usuario_codigo=0;}
                $usuario_codigo = $usuario_codigo + 1;
            }

            function Quitar_Espacios($textooriginal)
            {
                $texto = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $textooriginal);
                return $texto;
            }

            if ($perfil_id=="420"){ // conductor
                $tipousuario = "2696";
            }
            if ($perfil_id=="419"){ // pasajero
                $tipousuario = "2695";
            }

            $alias = Quitar_Espacios($nombre);

            // CVU Banco
            $usuario_cvu = "0000000";
            for ($i=0; $i<15; $i++){
                $numeroAleatorio = random_int(0, 9);
                $usuario_cvu = $usuario_cvu.$numeroAleatorio;
            }

            

            // Alias Banco Argentina
            $aliasbanco = "";

            $palabras= "sol,luna,estrella,cielo,tierra,agua,fuego,aire,noche,dia,pais,montana,perro,gato";
            $arrayPalabras = explode(',',$palabras);
            shuffle($arrayPalabras);

            $contAlias = 0;

            foreach ($arrayPalabras as $value) {
                
                if ($aliasbanco==""){
                    $aliasbanco = $value;
                }else{
                    $aliasbanco .= ".".$value;
                }

                $contAlias = $contAlias + 1;

                if ($contAlias>=3){
                    break;
                }
            }

			$resultado = $conexion->doInsert("
				usuario
				(usuario_codigo, usuario_email, usuario_clave, usuario_nombre, usuario_apellido,
				usuario_fechareg, usuario_activo, usuario_eliminado, perfil_id,
				cuenta_id, compania_id, usuario_codverif, usuario_emailverif, usuario_img, 
                usuario_codverifwa, usuario_whatsapp, l_estatus_id, l_estatus_iddisponible,
                usuario_idreferido, usuario_codreferido, l_ciudad_id, l_tipousuarioserv_id, 
                usuario_alias, pais_id, l_zonahoraria_id, usuario_trabajador, usuario_empleador,
                sexo_id, usuario_cvu, usuario_aliasbanco
                ) 
			",
			"'$usuario_codigo', '$email', '$clave', '$nombre', '$apellido',
			'$fechaactual', '1', '0', '$perfil_id', 
			'$cuenta_id', '$compania_id', '$usuario_codverif', '$usuario_emailverif', '1.png', '$usuario_codverifwa', '$whatsapp', '$estatususuarioconfirmaremail', '$estatusdisponibilidad',
            '$usuario_idreferido', '$usuario_codreferido', '$ciudad', '$tipousuario',
            '$alias', '$pais', '$l_zonahoraria_id', '$usuario_trabajador', '$usuario_empleador',
            '$sexo', '$usuario_cvu', '$usuario_aliasbanco'
            ");

            // Verificar si existe un registro en referidos_pendientes con este email
            // para AgroComercio (compania_id = 387)
            if ($compania_id == "387") {
                $arrReferidoPendiente = $conexion->doSelect(
                    "referido_id, referidor_usuario_id, producto_id, codigo_unico, estado",
                    "referidos_pendientes",
                    "email = '$email' AND compania_id = '$compania_id' AND estado != 'convertido'"
                );

                if (count($arrReferidoPendiente) > 0) {
                    foreach($arrReferidoPendiente as $i=>$valor) {
                        $referido_pendiente_id = $valor["referido_id"];
                        $referidor_usuario_id = $valor["referidor_usuario_id"];
                        $producto_pendiente_id = $valor["producto_id"];
                        $codigo_unico_pendiente = $valor["codigo_unico"];

                        // Si hay un referidor, crear la relación en usuariorel
                        if ($referidor_usuario_id != null && $referidor_usuario_id > 0) {

                            // Obtener el ID del usuario recién creado
                            $arrNuevoUsuario = $conexion->doSelect(
                                "usuario_id",
                                "usuario",
                                "usuario_email = '$email' AND compania_id = '$compania_id' ORDER BY usuario_id DESC LIMIT 1"
                            );

                            if (count($arrNuevoUsuario) > 0) {
                                $nuevo_usuario_id = $arrNuevoUsuario[0]["usuario_id"];

                                // Verificar que no exista ya la relación
                                $existeRelacion = $conexion->doSelect(
                                    "usuariorel_id",
                                    "usuariorel",
                                    "usuario_id = '$referidor_usuario_id' AND usuario_idrel = '$nuevo_usuario_id' AND compania_id = '$compania_id'"
                                );

                                if (count($existeRelacion) == 0) {
                                    // Crear la relación en usuariorel
                                    // El referidor es usuario_id y el referido es usuario_idrel
                                    // l_tiporelacionusuario_id = 6107 es para tipo "Referido"
                                    $resultado_rel = $conexion->doInsert(
                                        "usuariorel (usuario_id, usuario_idrel, l_tiporelacionusuario_id, usuariorel_activo,
                                        usuariorel_eliminado, usuariorel_fechareg, compania_id)",
                                        "'$referidor_usuario_id', '$nuevo_usuario_id', '6107', '1', '0', '$fechaactual', '$compania_id'"
                                    );

                                    // Actualizar el registro de referidos_pendientes como convertido
                                    $conexion->doUpdate(
                                        "referidos_pendientes",
                                        "usuario_id_convertido = '$nuevo_usuario_id',
                                         fecha_conversion = '$fechaactual',
                                         estado = 'convertido'",
                                        "referido_id = '$referido_pendiente_id'"
                                    );

                                    // Actualizar estadísticas del referidor si existe la tabla
                                    $existeStats = $conexion->doSelect(
                                        "stat_id",
                                        "usuario_referidos_stats",
                                        "usuario_id = '$referidor_usuario_id' AND compania_id = '$compania_id'"
                                    );

                                    if (count($existeStats) > 0) {
                                        // Actualizar estadísticas existentes
                                        $conexion->doUpdate(
                                            "usuario_referidos_stats",
                                            "total_conversiones = total_conversiones + 1,
                                             puntos_ganados = puntos_ganados + 10",
                                            "usuario_id = '$referidor_usuario_id' AND compania_id = '$compania_id'"
                                        );
                                    } else {
                                        // Crear nuevo registro de estadísticas
                                        $conexion->doInsert(
                                            "usuario_referidos_stats
                                            (usuario_id, total_clicks, total_registros_web, total_conversiones, puntos_ganados, compania_id)",
                                            "'$referidor_usuario_id', '0', '0', '1', '10', '$compania_id'"
                                        );
                                    }

                                    // También actualizar el usuario_idreferido si no estaba establecido
                                    if ($usuario_idreferido == 0) {
                                        $conexion->doUpdate(
                                            "usuario",
                                            "usuario_idreferido = '$referidor_usuario_id'",
                                            "usuario_id = '$nuevo_usuario_id'"
                                        );

                                        // Totalizar referidos del usuario que refirió
                                        totalizarReferidos($referidor_usuario_id, $compania_id);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Cuento los referidos del usuario que refirio para colocar el totalizado en la columna
            if ($usuario_idreferido>0){
                totalizarReferidos($usuario_idreferido, $compania_id);
            }

            $arrresultado2 = $conexion->doSelect("usuario.usuario_id, 
                DATE_FORMAT(usuario_fechareg,'$formatofechaSQL %H:%i:%s') as usuario_fechareg,
                estatus.lista_cod as estatus_cod, 
                estatus.lista_nombre as estatus_nombre,
                usuario_nombre, usuario_apellido, usuario_telf, usuario_email, usuario_alias
                ","usuario
                        inner join lista estatus on estatus.lista_id = usuario.l_estatus_id
                ", 
                "usuario.usuario_email = '$email' and usuario.compania_id = '$compania_id' and usuario_activo = '1'");
            if (count($arrresultado2)>0){
                foreach($arrresultado2 as $i=>$valor){
                    $usuario_id = utf8_encode($valor["usuario_id"]);
                    $usuario_fechareg = utf8_encode($valor["usuario_fechareg"]);
                    $estatus_cod = utf8_encode($valor["estatus_cod"]);
				    $estatus_nombre = utf8_encode($valor["estatus_nombre"]);
                    $usuario_nombre = utf8_encode($valor["usuario_nombre"]);
                    $usuario_apellido = utf8_encode($valor["usuario_apellido"]);
                    $usuario_alias = utf8_encode($valor["usuario_alias"]);
                    $usuario_telf = utf8_encode($valor["usuario_telf"]);
                    $usuario_email = utf8_encode($valor["usuario_email"]);
                    
                }

            }

            /* if ($compania_id=="373" || $compania_id=="374" ){// Si es de taxi registre vehiculo

                // Registro y Vehiculo y relaciono.
                $placa = generarId();
                $resultado = $conexion->doInsert("
                vehiculo
                (vehiculo_patente, vehiculo_activo, vehiculo_eliminado, vehiculo_fechareg, 
                cuenta_id, compania_id, vehiculo_marca, vehiculo_modelo,
                vehiculo_img)
                ",
                "'', '1', '0', '$fechaactual',
                '$cuenta_id', '$compania_id', '', '', '0.jpg'");

                $arrresultado2 = $conexion->doSelect("max(vehiculo_id) as vehiculo_id",
                "vehiculo", "vehiculo_activo = '1'");
                if (count($arrresultado2)>0){
                    foreach($arrresultado2 as $i=>$valor){
                        $vehiculo_id = ($valor["vehiculo_id"]);
                    }

                    $resultado = $conexion->doInsert("
                    usuariovehiculo
                    (vehiculo_id, usuario_id, usuariovehiculo_fechareg, usuariovehiculo_activo, usuariovehiculo_eliminado) 
                    ",
                    "'$vehiculo_id', '$usuario_id', '$fechaactual', 1, 0");

                }

            } */

			$tipoenvio = 1;		
            
            if ($compania_id=="380" && $perfilreturn=="4"){
                $tipoenvio = 3; // No envie correo al cliente pero si al admin
            }
			

           
          
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
            

            $nombreimgqr = "qr_".$usuario_id.".png";

            $result->saveToFile("../arch/".$nombreimgqr);

            $dataUri = $result->getDataUri();

            $resultado = $conexion->doUpdate("usuario", "
            usuario_qr ='$dataUri',
            usuario_qrimg = '$nombreimgqr'							
            ",
            "usuario_id='$usuario_id'");

        
            $moneda = ObtenerMonedaPrincipalId($cuenta_id, $compania_id);

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

            $arrresultado2 = $conexion->doSelect("lista_nombre","lista", "lista.lista_id = '$ciudad'");
            if (count($arrresultado2)>0){
                foreach($arrresultado2 as $i=>$valor){
                    $ciudad_nombre = utf8_encode($valor["lista_nombre"]);
                }
            }

            $usuario_img = ObtenerUrlArch($compania_id)."/$usuario_img";

            $data = array(
        		"nombre" => $usuario_nombre,
                "apellido" => $usuario_apellido,
                "alias" => $usuario_alias,
                "ciudad" => $ciudad_nombre,
        		"email" => $usuario_email,	
                "imagen" => $usuario_img,
                "idusuario" => $usuario_id,
                "id" => $usuario_id,
                "usuarioqr" => $nombreimgqr,					
				"perfil" => $perfilreturn,
                "perfilact" => $perfil_id,
				"fecharegistro" => $usuario_fechareg,
				"whatsapp" => $whatsapp,
                "estatuscod" => $estatus_cod,
                "estatusnombre" => $estatus_nombre, 
                "tipousuario" => $tipousuario,             
        	); 
			
			$message = "Registro de cliente exitoso";
		
			$valores = array(
				"code" => 0,
				"token" => $usuario_codverif,
				"codigoverificar" => $usuario_emailverif,
                "codigoverificarwa" => $usuario_codverifwa,
				"data" => $data,
				"message" => $message
			);

            
           


        }
	}

}

if ($tipoenvio == 1 || $tipoenvio == 2 || $tipoenvio=="3"){// Registro Correcto
	

	$libemail = new LibEmail();  

	// Mensaje Cliente

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

    if ($tipoenvio=="1" || $tipoenvio=="2"){

        $asunto = "Codigo verificacion: $usuario_emailverif";

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $resultado = $libemail->enviarcorreo($email, $asunto, $texto, $compania_id);
        }

        if (filter_var($compania_email, FILTER_VALIDATE_EMAIL)) {
            $resultado = $libemail->enviarcorreo($compania_email, $asunto, $texto, $compania_id);
        }

        $resultado = $libemail->enviarcorreo("meneses.rigoberto@gmail.com", $asunto, $texto, $compania_id);
    
    }

   
    //echo "resultado:";
    //print_r($resultado);
    //exit();
	// Mensaje Admin

    $datosemail = "";

    if ($usuario_nombre!=""){
        $datosemail .= "<strong>Nombre:</strong> $usuario_nombre <br>";
    }

    if ($usuario_apellido!=""){
        $datosemail .= "<strong>Apellido:</strong> $usuario_apellido <br>";
    }

    if ($email!=""){
        $datosemail .= "<strong>Nombre:</strong> $email <br>";
    }

    if ($whatsapp!=""){
        $datosemail .= "<strong>WhatsApp:</strong> $whatsapp <br>";
    }

	$texto = "
    <table border='0' width='100%' cellpadding='0' cellspacing='0' bgcolor='ffffff' class='bg_color'>

      <tr>
          <td align='center'>
              <table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590'>
                  
                  <tr>
                      <td align='center' style='color: #343434; font-size: 24px; font-family: Quicksand, Calibri, sans-serif; font-weight:700;letter-spacing: 3px; line-height: 35px;' class='main-header'>
                          <div style='line-height: 35px'>
                              Se ha registrado un nuevo usuario en la App
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
                      <td align='left' style='color: #343434; font-size: 17px; font-family: Quicksand, Calibri, sans-serif; font-weight:normal;letter-spacing: 3px; line-height: 35px;' class='main-header'>
					  <div style='line-height: 26px;'>
						$datosemail
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
  
    $asunto = "Nuevo Usuario";

    if (filter_var($compania_email, FILTER_VALIDATE_EMAIL)) {
        $resultado = $libemail->enviarcorreo($compania_email, $asunto, $texto, $compania_id);
    }

    $resultado = $libemail->enviarcorreo("meneses.rigoberto@gmail.com", $asunto, $texto, $compania_id);

}

$resultado = json_encode($valores);

echo $resultado;

exit();

?>