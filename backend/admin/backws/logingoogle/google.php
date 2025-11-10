<?php

ini_set ("display_errors","0");

  // google.php
  header('Content-Type: application/json');
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: POST");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../../lib/funciones.php';
include_once '../../lib/mysqlclass.php';
include_once '../../lib/phpmailer/libemail.php';
include_once '../../models/lista.php';

  // Include your database connection
  //include_once 'conexion.php'; // Ajusta según tu estructura

  // Include Google API Client
  // Opción 1: Si usas Composer
  include_once '../../vendor/autoload.php';

  // Opción 2: Descarga manual de Google API Client
  // Descarga desde: https://github.com/googleapis/google-api-php-client/releases
  // y extrae en tu proyecto
  // require_once 'google-api-php-client/vendor/autoload.php';

  // Get POST data
  $data = json_decode(file_get_contents('php://input'), true);

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      echo json_encode(['code' => 1, 'message' => 'Method not allowed']);
      exit;
  }

  // Your Google Client ID (same as in the app)
  $CLIENT_ID = '777163485744-o8dbiiv4l31uhsuhr5gnjh06isk8hsv5.apps.googleusercontent.com'; // REEMPLAZA CON TU CLIENT ID REAL

    //$CLIENT_ID = '777163485744-vudq8qjjjs6lf53ias02r8dppu1lan74.apps.googleusercontent.com'; // REEMPLAZA CON TU CLIENT ID REAL

  try {
      $accessToken = $data['access_token'] ?? '';
      $email = $data['email'] ?? '';
      $nombre = $data['nombre'] ?? '';
      $apellido = $data['apellido'] ?? '';
      $google_id = $data['google_id'] ?? '';
      $picture = $data['picture'] ?? '';
      $compania = $data['compania'] ?? '';

	    $compania = "468";

        if ($email!="democliente"){

            if (empty($accessToken) || empty($email)) {
                throw new Exception('Token o email faltante');
            }

            // Verify the Google ID token
            $client = new Google_Client(['client_id' => $CLIENT_ID]);
            $payload = $client->verifyIdToken($accessToken);

        }

        if ($payload || $email=="democliente") {
          // Token is valid, check if it matches the provided email
          if ($payload['email'] !== $email && $email!="democliente") {
              throw new Exception('Email no coincide con el token');
          }

		  $conexion = new ConexionBd();  

          $fechaactual = formatoFechaHoraBd(null, null, null, null, $compania);

          $emailinsertar=uniqid();

		  $arrresultado = $conexion->doSelect("usuario_id","usuario","compania_id = '$compania' and usuario_email = '$emailinsertar'");
			foreach($arrresultado as $i=>$valor){
				$usuario_id = utf8_encode($valor["usuario_id"]);
		  }
          
          if (count($arrresultado)==0) {

              $resultado = $conexion->doInsert("
				usuario
					(usuario_email, usuario_clave, usuario_nombre, usuario_apellido,
					usuario_fechareg, usuario_activo, usuario_eliminado, perfil_id,
					cuenta_id, compania_id,  usuario_img, 
					usuario_whatsapp, l_estatus_id, l_estatus_iddisponible,
					google_id
					) 
				",
				"'$emailinsertar', '', '$nombre', '$apellido',
				'$fechaactual', '1', '0', '504', 
				'7226', '$compania', '1.png', 
				'$whatsapp', '0', '0',
				'$google_id'
				");


               $arrresultado2 = $conexion->doSelect("usuario.usuario_id, 
                estatus.lista_cod as estatus_cod, 
                estatus.lista_nombre as estatus_nombre,
                usuario_nombre, usuario_apellido, usuario_telf, usuario_email, usuario_alias
                ","usuario
                        left join lista estatus on estatus.lista_id = usuario.l_estatus_id
                ", 
                "usuario.usuario_email = '$email' and usuario.compania_id = '$compania' and usuario_activo = '1'");
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
          } else {
             /*  
			 // Update existing user with Google info if needed
              if (empty($usuario['google_id']) && !empty($google_id)) {
                  $stmt = $conexion->prepare("
                      UPDATE usuarios
                      SET google_id = ?, foto_perfil = COALESCE(foto_perfil, ?)
                      WHERE id = ?
                  ");
                  $stmt->execute([$google_id, $picture, $usuario['id']]);
              } 
				  */
          }

          // Generate app token (usa tu método actual de generación de tokens)
          //$token = generarToken($usuario_id); // Implementa esta función

          $tokenlogin = uniqid();

          $perfil_idorig = "4";
          $perfil_id = "504";         

          // Prepare response data
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
				
			);

			$valores = array(
				"code" => 0,
				"data" => $data,
				"message" => $message,
				"token" => $tokenlogin
			); 

          echo json_encode($valores);

      } else {
          // Invalid token
          throw new Exception('Token de Google inválido');
      }

  } catch (Exception $e) {
      http_response_code(400);
      echo json_encode([
          'code' => 1,
          'message' => $e->getMessage()
      ]);
  }

  // Funciones auxiliares que necesitas implementar
  function generarToken($usuario_id) {
      // Implementa tu lógica de generación de tokens
      // Puedes usar JWT o tu método actual
      return bin2hex(random_bytes(32));
  }

  function generarCodigoReferido() {
      // Genera un código único para referidos
      return strtoupper(substr(md5(uniqid()), 0, 6));
  }

  function generarQR() {
      // Genera un código QR único para el usuario
      return 'QR' . time() . rand(1000, 9999);
  }
?>