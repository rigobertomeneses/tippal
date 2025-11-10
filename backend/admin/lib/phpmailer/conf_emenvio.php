<?php 

define ("EXP",6000000); setlocale (LC_CTYPE, 'es_ES');
ini_set ("display_errors","0");
ini_set ("memory_limit","-1");

(isset($_GET['nombre'])) ? $nombre=$_GET['nombre'] : $nombre='No Especificado';
(isset($_GET['msje'])) ? $msje=$_GET['msje'] : $msje='No Especificado';
(isset($_GET['correo'])) ? $correo=$_GET['correo'] : $correo='No Especificado';
(isset($_GET['telf'])) ? $telf=$_GET['telf'] : $telf='No Especificado';
 include("class.phpmailer.php"); 
 include("class.smtp.php"); 
 $mail = new PHPMailer(); 
 $mail->IsSMTP(); 
 $mail->SMTPAuth = true; 
 $mail->SMTPSecure = "ssl"; 
 $mail->Host = "servidor.servidoresvenezuela.com"; 
 $mail->Port = 465; 
 $mail->Username = "info@promoix.com"; 
 $mail->Password = "infopanel2014";
 $correoenviar ="
 	Ha recibido un mensaje a traves de su pagina web: www.promoix.com<br><br>
 	Nombre: ".$nombre." <br>
 	Telefono: ".$telf." <br>
 	Correo: ".$correo." <br>
 	Mensaje: ".$msje." <br> <br>	
 	Le recomendamos agregar esta direccion info@promoix.com a su lista de contactos seguro. 
 ";
$mail->From = "info@promoix.com"; 
$mail->FromName = "ContactoPagina"; 
$mail->Subject = "Contacto por Formulario de Pagina Web"; 
$mail->MsgHTML($correoenviar);
$mail->AddAddress("meneses.rigoberto@gmail.com", "Destinatario");  
$mail->IsHTML(true); 
 if(!$mail->Send()) { 
 	echo "<script language='JavaScript'>alert('Error: Disculpe, no hemos podido recibir su mensaje, intente enviarnos un correo a info@promoix.com');</script>"; 	
 } else { 
 	echo "<script language='JavaScript'>alert('Hemos recibido correctamente su mensaje, en breve lo contactaremos');</script>"; 	
}
?>