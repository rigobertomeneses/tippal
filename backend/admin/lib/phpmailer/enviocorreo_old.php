<?php 

define ("EXP",6000000); setlocale (LC_CTYPE, 'es_ES');
ini_set ("display_errors","0");
ini_set ("memory_limit","-1");
include("class.phpmailer.php");
include("class.smtp.php");

class EnvioCorreo {
	
	function enviarcorreoactivacion($nombre, $apellido, $correo, $codactivacion){

		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "ssl";
		$mail->Host = "servidor.servidoresvenezuela.com";
		$mail->Port = 465;
		$mail->Username = "info@promoix.com";
		$mail->Password = "infopanel2014";
		
		$nombre = strtoupper($nombre);
		$apellido = strtoupper($apellido);
		$correoenviar ="
		Buenas ".$nombre." ".$apellido." <br><br>
	 	Actualmente ha creado un usuario en promoix, para activar su cuenta y puedas empezar a comprar
		tus cupones favoritos dele click en el siguiente enlace
		<br> o copielo y peguelo en su navegador.<br>
		<br>
	 	Activacion: http://www.promoix.com/temp/registro.php?ct=".$codactivacion." <br>
	 	<br><br>
	 	Le recomendamos agregar esta direccion info@promoix.com a su lista de contactos seguro.
	 	";
		$mail->From = "info@promoix.com";
		$mail->FromName = "Promoix";
		$mail->Subject = "Activacion de Cuenta Promoix";
		$mail->MsgHTML($correoenviar);
		$mail->AddAddress($correo, "Destinatario");
		$mail->IsHTML(true);
		$resultado = $mail->Send();		
		
		return $resultado;
	}
	
	
	function enviarcorreocontacto($nombre, $email, $telefono, $mensaje){
	
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "ssl";
		$mail->Host = "servidor.servidoresvenezuela.com";
		$mail->Port = 465;
		$mail->Username = "info@promoix.com";
		$mail->Password = "infopanel2014";
	
		$nombre = strtoupper($nombre);
		$apellido = strtoupper($apellido);
		$correoenviar ="
		Ha recibido un mensaje a traves de su pagina web: www.promoix.com por el formulario de contactanos <br><br>
	 	Nombre: ".$nombre." <br>
	 	Telefono: ".$telefono." <br>
	 	Correo: ".$email." <br>
	 	Mensaje: ".$mensaje." <br> <br>	
	 	Le recomendamos agregar esta direccion info@promoix.com a su lista de contactos seguro. 
	 	<br><br>
	 	";
		$mail->From = "info@promoix.com";
		$mail->FromName = "Promoix";
		$mail->Subject = "Contacto por Formulario de Contacto";
		$mail->MsgHTML($correoenviar);
		$mail->AddAddress("info@promoix.com", "Destinatario");
		$mail->IsHTML(true);
		$resultado = $mail->Send();
	
		return $resultado;
	}
	
	function olvidocontrasena($email, $codactivacion){
	
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "ssl";
		$mail->Host = "servidor.servidoresvenezuela.com";
		$mail->Port = 465;
		$mail->Username = "info@promoix.com";
		$mail->Password = "infopanel2014";
		
		$nombre = strtoupper($nombre);
		$apellido = strtoupper($apellido);
		$correoenviar ="
		Buenas ".$nombre." ".$apellido." <br><br>
	 	Actualmente ha solicitad reestablecer su contraseña, dele click en el siguiente enlace
		<br> o copielo y peguelo en su navegador.<br>
		<br>
	 	Activacion: http://www.promoix.com/temp/cambiarpass.php?ps=".$codactivacion." <br>
	 	<br><br>
	 	Le recomendamos agregar esta direccion info@promoix.com a su lista de contactos seguro.
	 	";
		$mail->From = "info@promoix.com";
		$mail->FromName = "Promoix";
		$mail->Subject = "Activacion de Cuenta Promoix";
		$mail->MsgHTML($correoenviar);
		$mail->AddAddress($email, "Destinatario");
		$mail->IsHTML(true);
		$resultado = $mail->Send();		
		
		return $resultado;
	}
	
}
?>