<?php
include_once "lib/mysqlclass.php";

class Chat {

	function InsertarChat($chat_id=null, $mensaje=null, $usuario_idorigen=null, $usuario_iddestino=null, $modulo_id = null, $elementoid= null, $cuenta_id=null, $compania_id=null, $tipo=null){

		$fechaactual = formatoFechaHoraBd(null, null, null, null, $compania_id);

		if ($elementoid==""){$elementoid=0;}
	
		$conexion = new ConexionBd();  

		if ($chat_id==""){
			$resultado = $conexion->doInsert("
			chat(
				chat_titulo, chat_ultfecha, chat_ultmsje, usuario_idorigen, usuario_iddestino, 
				chat_leido, modulo_id, elemento_id, cuenta_id, compania_id, 
				chat_fechareg, chat_activo, chat_eliminado, 
				usuario_idcliente, usuario_idpropietario, chat_msjes, chat_tipo)
			",
			"'', '$fechaactual','$mensaje','$usuario_idorigen', '$usuario_iddestino',
			'0','$modulo_id','$elementoid','$cuenta_id', '$compania_id',
			'$fechaactual', '1','0',
			'$usuario_idorigen','$usuario_iddestino','1', '$tipo'");
			
			$arrresultado = $conexion->doSelect("max(chat_id) as chat_id","chat");	    	
			foreach($arrresultado as $i=>$valor){  
				$chat_id = utf8_encode($valor["chat_id"]);
			}	
		}else{

			$resultado = $conexion->doUpdate("chat", "
			chat_ultmsje ='$mensaje',
			chat_ultfecha ='$fechaactual'
			",
			"chat_id='$chat_id'");
		}

		$resultado = $conexion->doInsert("
		chatmsje
			(chat_id, chatmsje_titulo, chatmsje_arch, chatmsje_archorig, chatmsje_texto, chatmsje_fechareg, 
			usuario_idorigen, usuario_iddestino, chatmsje_leido, chatmsje_activo, chatmsje_eliminado, l_tipoarchivo_id)
		",
		"'$chat_id', '','','', '$mensaje', '$fechaactual',
		'$usuario_idorigen','$usuario_iddestino', '0', '1','0','0'");


		return $chat_id;
	}

	
}
?>