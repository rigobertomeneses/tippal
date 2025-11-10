<?php
define ("EXP",6000000);
ini_set ("display_errors","0");
ini_set ("memory_limit","-1");

//include_once $_SERVER['DOCUMENT_ROOT']."/admin/lib/phpmailer/libemail.php";

/*
// GESTION GO
define ("R","Sist_Gn2302"); //Clave
define ("M","gestiong_app"); //Usuario
define ("E","gestiong_app"); //Bd
define ("N","localhost"); //Servidor

// nautispress
define ("R","NautiSpr_1124"); //Clave
define ("M","u713197773_nauti"); //Usuario
define ("E","u713197773_nauti"); //Bd
define ("N","localhost"); //Servidor

*/

// nautispress
define ("R","Sist_Gn2302"); //Clave
define ("M","gestiong_app"); //Usuario
define ("E","gestiong_app"); //Bd
define ("N","localhost"); //Servidor


class ConexionBd {

	private $conexion;
	private $last_insert_id;
	private $total_consultas;

	function open(){

		if(!isset($this->conexion)){

			$this->conexion = (mysqli_connect(N,M,R,E));

			if (!$this->conexion) {

				echo mysqli_connect_error();

			}

		}

	}



	// Realiza las consultas a la Base de Datos

	function doSelect($strSelect,$strFrom,$strWhere=null,$strGroupBy=null,$strOrderBy=null) {

		$pos = strpos($strSelect, "select");if ($pos == true) {return false;}
		$pos = strpos($strSelect, "from");if ($pos == true) {return false;}
		$pos = strpos($strSelect, "<script");if ($pos == true) {return false;}
		$pos = strpos($strSelect, "database");if ($pos == true) {return false;}
		$pos = strpos($strSelect, "database");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "select");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "from");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "<script");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "<Script");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "alert(");if ($pos == true) {return false;}


		//$pos = strpos($strSelect, "update");if ($pos == true) {return false;}
		//$pos = strpos($strWhere, "update");if ($pos == true) {return false;}

		$this->open();

		$consulta = isset($strSelect) ? "SELECT $strSelect" : "";

		$consulta .= isset($strFrom) ? " FROM $strFrom" : "";

		$consulta .= (isset($strWhere) and (strcmp($strWhere,"") != 0)) ? " WHERE $strWhere" : "";

		$consulta .= isset($strGroupBy) ? " GROUP BY $strGroupBy" : "";

		$consulta .= isset($strOrderBy) ? " ORDER BY $strOrderBy" : "";

		$consulta .= ";";



		$resultado = $this->consulta($consulta);

		$row =array();



		while ($rowq = mysqli_fetch_assoc($resultado)) {

	        $row[] = $rowq;

	    }



		return $row;

	}






	function doInsert($strInsertInto,$strValues) {

		$pos = strpos($strInsertInto, "select");if ($pos == true) {return false;}
		$pos = strpos($strInsertInto, "from");if ($pos == true) {return false;}
		$pos = strpos($strInsertInto, "<script");if ($pos == true) {return false;}
		$pos = strpos($strValues, "select");if ($pos == true) {return false;}
		$pos = strpos($strValues, "from");if ($pos == true) {return false;}
		$pos = strpos($strValues, "<script");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "<Script");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "alert(");if ($pos == true) {return false;}
		$pos = strpos($strInsertInto, "<Script");if ($pos == true) {return false;}
		$pos = strpos($strInsertInto, "alert(");if ($pos == true) {return false;}
		//$pos = strpos($strInsertInto, "update");if ($pos == true) {return false;}
		//$pos = strpos($strValues, "update");if ($pos == true) {return false;}

		$this->open();

		$consulta = isset($strInsertInto) ? "INSERT INTO ".$strInsertInto : "";

		$consulta .= isset($strValues) ? " VALUES ($strValues);" : ";";

		$resultado = $this->consulta($consulta);

		// Guardar el último ID insertado
		if ($resultado) {
			$this->last_insert_id = mysqli_insert_id($this->conexion);
		}

		return $resultado;

	}



	function doUpdate($strUpdate,$strSet,$strWhere=null) {

		$pos = strpos($strUpdate, "select");if ($pos == true) {return false;}
		$pos = strpos($strUpdate, "from");if ($pos == true) {return false;}
		$pos = strpos($strUpdate, "<script");if ($pos == true) {return false;}
		$pos = strpos($strSet, "select");if ($pos == true) {return false;}
		$pos = strpos($strSet, "from");if ($pos == true) {return false;}
		$pos = strpos($strSet, "<script");if ($pos == true) {return false;}
		$pos = strpos($strSet, "<Script");if ($pos == true) {return false;}
		$pos = strpos($strSet, "alert(");if ($pos == true) {return false;}
		//$pos = strpos($strUpdate, "update");if ($pos == true) {return false;}
		//$pos = strpos($strWhere, "update");if ($pos == true) {return false;}

		$this->open();



		$consulta = isset($strUpdate) ? "UPDATE $strUpdate" : "";

		$consulta .= isset($strSet) ? " SET $strSet" : "";

		$consulta .= isset($strWhere) ? " WHERE $strWhere" : "";

		$consulta .= ";";

		$resultado = $this->consulta($consulta);



		return $resultado;

	}



	function doDelete($strDeleteFrom,$strWhere=null) {

		$pos = strpos($strDeleteFrom, "select");if ($pos == true) {return false;}
		$pos = strpos($strDeleteFrom, "from");if ($pos == true) {return false;}
		$pos = strpos($strDeleteFrom, "<script");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "select");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "from");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "<script");if ($pos == true) {return false;}
		$pos = strpos($strDeleteFrom, "<Script");if ($pos == true) {return false;}
		$pos = strpos($strDeleteFrom, "alert(");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "<Script");if ($pos == true) {return false;}
		$pos = strpos($strWhere, "alert(");if ($pos == true) {return false;}
		//$pos = strpos($strDeleteFrom, "update");if ($pos == true) {return false;}
		//$pos = strpos($strWhere, "update");if ($pos == true) {return false;}

		$this->open();



		$consulta = isset($strDeleteFrom) ? "DELETE FROM $strDeleteFrom" : "";

		$consulta .= isset($strWhere) ? " WHERE $strWhere" : "";

		$consulta .= ";";

		$resultado = $this->consulta($consulta);



		return $resultado;

	}



	function doQuery($strQuery=null) {

		$this->open();

		$resultado = $this->consulta($strQuery);

		$row =array();



		while ($rowq = mysqli_fetch_assoc($resultado)) {

	        $row[] = $rowq;

	    }

		return $row;

	}



	public function consulta($consulta){


		mysqli_set_charset($this->conexion, 'latin1');
		$resultado = mysqli_query($this->conexion, $consulta);

		$fichero = 'prueba.txt';

		// Escribe el contenido al fichero
		//file_put_contents($fichero, $consulta);


		//if($resultado){

		if(!$resultado){

			$link = $_SERVER['PHP_SELF'];
			$link_array = explode('/',$link);
			$url = end($link_array);

			$mensaje = mysqli_error($this->conexion)."<br>Query: $consulta <br><br>modulourl:$url";

			/*
			$libemail = new LibEmail();

			$texto = $mensaje;
			$asunto = "Error Presentado en GestionGo";
			$email = "meneses.rigoberto@gmail.com";

			$resultado = $libemail->enviarcorreo($email, $asunto, $texto);

			*/



			echo 'MySQL Error: ' . mysqli_error($this->conexion).' favor reportar al administrador del sistema<br>'.$consulta;
			//echo 'Estamos trabajando en este modulo, pronto estará disponible para utilizarlo';

			exit;

		}

		return $resultado;

	}



	public function fetch_array($consulta){

		return mysql_fetch_array($consulta);

	}



	public function num_rows($consulta){

		return mysql_num_rows($consulta);

	}



	public function getTotalConsultas(){

		return $this->total_consultas;

	}



	public function close(){

		if ($this->conexion){

			return mysqli_close($this->conexion);

		}

	}

	/**
	 * Obtiene el ID del último registro insertado
	 * @return int El ID del último INSERT
	 */
	public function lastInsertId() {
		return isset($this->last_insert_id) ? $this->last_insert_id : 0;
	}

}

?>
