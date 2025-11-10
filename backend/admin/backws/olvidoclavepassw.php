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


if ($metodo=="POST"){// Olvido de Clave

    $valoresPost = json_decode(file_get_contents('php://input'), true);
	(isset($valoresPost['email'])) ? $email=$valoresPost['email'] :$email='';
    (isset($valoresPost['compania'])) ? $compania_id=$valoresPost['compania'] :$compania_id='';
    if ($compania_id==""){
		$compania_id = 0;
	}

    $email = utf8_decode($email);


	if($email ==""){

		$valores = array(
			"code" => 101,
			"message" => "Error: Valores requeridos de Email"
		);

                
        $resultado = json_encode($valores);

        echo $resultado;

        exit();


	}else{

		$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

		$conexion = new ConexionBd();
		
		$usuario_codverif = uniqid();

        $clavegenerada = generateRandomNumber2();
		
        $formatofechaSQL = formatofechaSQL($compania_id);
		
		$arrresultado2 = $conexion->doSelect("usuario.usuario_id, usuario_nombre, usuario_apellido,
            usuario_codverif, usuario_emailverif, usuario_email, 
			  DATE_FORMAT(usuario_fechareg,'$formatofechaSQL %H:%i:%s') as usuario_fechareg,
              compania_nombre, compania_email
              ",
              "usuario
                inner join compania on compania.compania_id = usuario.compania_id
              ", 
              "usuario_email = '$email' and usuario_activo = '1' and compania.compania_id = '$compania_id'");
		$total = count($arrresultado2);
		if (count($arrresultado2)>0){
			foreach($arrresultado2 as $i=>$valor){
				$compania_nombre = utf8_encode($valor["compania_nombre"]);
				$compania_email = utf8_encode($valor["compania_email"]);
				$usuario_id = ($valor["usuario_id"]);
				$usuario_nombre = ($valor["usuario_nombre"]);
                $usuario_apellido = ($valor["usuario_apellido"]);
				$usuario_email = ($valor["usuario_email"]);
				$usuario_codverif = ($valor["usuario_codverif"]);
				$usuario_emailverif = ($valor["usuario_emailverif"]);
				$usuario_fechareg = ($valor["usuario_fechareg"]);
			}

            $resultado = $conexion->doUpdate("usuario", "
            usuario_clavereset ='$clavegenerada'					
            ",
            "usuario_id='$usuario_id'");   
            
            $data = array(
        		"correcto" => 1
        	); 

            $code = 0;
            $message = "Enviado nueva clave con exito";
            
            $valores = array(
				"code" => $code,
				"data" => $data,
				"message" => $message
			);

            $tipoenvio = 1;			

           

		}else{

            $message = "El email ingresado no existe";

            $valores = array(
				"code" => 101,
				"data" => [],
				"message" => $message
			);

            $resultado = json_encode($valores);

            echo $resultado; 

            exit();            			
        }
	}

}

if ($tipoenvio == 1){// Envio Correcto
	

	$libemail = new LibEmail();  

    //$resultado = $libemail->enviarcorreo("meneses.rigoberto@gmail.com", $asunto, $texto, $compania_id);

	// Mensaje Cliente

	$texto = "
    <table border='0' width='100%' cellpadding='0' cellspacing='0' bgcolor='ffffff' class='bg_color'>

      <tr>
          <td align='center'>
              <table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590'>
                  
                  <tr>
                      <td align='center' style='color: #343434; font-size: 24px; font-family: Quicksand, Calibri, sans-serif; font-weight:700;letter-spacing: 3px; line-height: 35px;' class='main-header'>
                          <div style='line-height: 35px'>
                              Hola $usuario_nombre $usuario_apellido, has solicitar recuperar la clave para ingresar en nuestra App $compania_nombre, a continuacion tienes la clave para iniciar nuevamente. Luego del ingreso vas a poder cambiarla.
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
                              <strong> $clavegenerada </strong>
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
                              Ingrese en la App con esta nueva clave
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
  
    $asunto = "Olvido de clave";

    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resultado = $libemail->enviarcorreo($email, $asunto, $texto, $compania_id);
    }       

    /* if (filter_var($compania_email, FILTER_VALIDATE_EMAIL)) {
		$resultado = $libemail->enviarcorreo($compania_email, $asunto, $texto, $compania_id);
	} 
    */

    $resultado = $libemail->enviarcorreo("meneses.rigoberto@gmail.com", $asunto, $texto, $compania_id);

}



$resultado = json_encode($valores);

echo $resultado;

exit();

?>