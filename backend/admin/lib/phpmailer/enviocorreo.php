<?php 

define ("EXP",6000000); setlocale (LC_CTYPE, 'es_ES');
ini_set ("display_errors","0");
ini_set ("memory_limit","-1");
include("class.phpmailer.php");
include ("libemail.php");

class EnvioCorreo {
	
	function enviarcorreo($emailparaarray, $textoemail, $asunto, $remitente, $replyto, $nombrecliente){
	
		$libemail = new LibEmail();
		$resultado = $libemail->enviarcorreo($emailparaarray, $asunto, $textoemail, $remitente, $replyto, $nombrecliente);	
		
		return $resultado;
	}
	
	
	function enviarcorreomasivo($emailparaarray, $asunto, $textoemail){
	
		$libemail = new LibEmail();
		$resultado = $libemail->enviarcorreomasivo($emailparaarray, $asunto, $textoemail);
	
		return $resultado;
	}
	
		
}
?>