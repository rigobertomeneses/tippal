<?php
define ("EXP",6000000); 
setlocale (LC_CTYPE, 'es_ES');
ini_set ("display_errors","0");
ini_set ("memory_limit","-1");


session_start();
$iniuser=$_SESSION[iniuser];

//if ($_COOKIE[iniuser] != $_SESSION[iniuser] && $_COOKIE[iniuser]!=""){
/*
if ($_COOKIE[iniuser] == ""){
  echo "<script language='JavaScript'>window.location = 'verificarinicio'; </script>";
  exit();
}
*/

$GLOBALS['P_Tod'] =""; // Acceso Total
$GLOBALS['P_Ins'] =""; // Acceso para Insertar
$GLOBALS['P_Lee'] =""; // Acceso para Leer
$GLOBALS['P_Mod'] =""; // Acceso para Modificar
$GLOBALS['P_Eli'] =""; // Acceso para Eliminar
$GLOBALS['P_Acceso'] =""; // Acceso para Acceder al Modulo
$GLOBALS['P_User'] =""; // Acceso con solo usuario en las transacciones

include_once 'lib/xajax_0.2.4/xajax.inc.php';
include_once 'lib/funciones.php';
include_once 'lib/phpmailer/class.phpmailer.php';
include_once 'lib/phpmailer/libemail.php';
include_once 'lib/phpmailer/libemailtruck.php';
include_once 'models/todos.php';



// Asigno Permisos
$arrresultado = VerificarPermisosPerfil();



$xajax = new xajax('lib/ajx_fnci.php');

if ($_COOKIE[iniuser]=="249"){
	$GLOBALS['P_Tod'] ="1"; // Acceso Total
	$GLOBALS['P_Ins'] ="1"; // Acceso para Insertar
	$GLOBALS['P_Lee'] ="1"; // Acceso para Leer
	$GLOBALS['P_Mod'] ="1"; // Acceso para Modificar
	$GLOBALS['P_Eli'] ="1"; // Acceso para Eliminar
	$GLOBALS['P_Acceso'] ="1"; // Acceso para Acceder al Modulo
  $GLOBALS['P_User'] =""; // Acceso con solo usuario en las transacciones
}


$GLOBALS['P_Tod'] ="1"; // Acceso Total
$GLOBALS['P_Ins'] ="1"; // Acceso para Insertar
$GLOBALS['P_Lee'] ="1"; // Acceso para Leer
$GLOBALS['P_Mod'] ="1"; // Acceso para Modificar
$GLOBALS['P_Eli'] ="1"; // Acceso para Eliminar
$GLOBALS['P_Acceso'] ="1"; // Acceso para Acceder al Modulo

/*
$GLOBALS['P_User'] ="";  // Acceso con solo usuario en las transacciones
*/




define ("P_Tod", $GLOBALS['P_Tod']);
define ("P_Ins", $GLOBALS['P_Ins']);
define ("P_Lee", $GLOBALS['P_Lee']);
define ("P_Mod", $GLOBALS['P_Mod']);
define ("P_Eli", $GLOBALS['P_Eli']);
define ("P_Acceso", $GLOBALS['P_Acceso']);
define ("P_User", $GLOBALS['P_User']);


if ($_COOKIE[perfil]=="81"  || $_COOKIE[perfil]=="2"  ){

  $GLOBALS['P_Tod'] ="1"; // Acceso Total
  $GLOBALS['P_Ins'] ="1"; // Acceso para Insertar
  $GLOBALS['P_Lee'] ="1"; // Acceso para Leer
  $GLOBALS['P_Mod'] ="1"; // Acceso para Modificar
  $GLOBALS['P_Eli'] ="1"; // Acceso para Eliminar
  $GLOBALS['P_Acceso'] ="1"; // Acceso para Acceder al Modulo
  $GLOBALS['P_User'] ="";  // Acceso con solo usuario en las transacciones

}

$urlactual = $_GET["url"];


if (P_Acceso!="1" && $urlactual!="index"){
	RedireccionNoAcceso();
}



$conexion = new ConexionBd();
$arrresultado = $conexion->doSelect("compania_id, compania_urlweb, compania_alias",
"compania",
"compania_id = '$_COOKIE[idcompania]'");  
foreach($arrresultado as $i=>$valor){
	$compania_urlweb = utf8_encode($valor["compania_urlweb"]);  
  $compania_alias = utf8_encode($valor["compania_alias"]);
  $compania_id = utf8_encode($valor["compania_id"]);  
}

$linksalir = "../?s=1";
if ($compania_urlweb==""){
	$linksalir = "../?s=1";
}else{
	$linksalir = $compania_urlweb."?s=1";
}



$arrresultado = $conexion->doSelect("usuariobalance_disponible, moneda.lista_nombredos as moneda_siglas, 
  usuario.perfil_id, usuarioplan_id
    ",
    "usuario
      left join compania on usuario.compania_id = compania.compania_id
      left join usuario referido on referido.usuario_id = usuario.usuario_idreferido
      left join usuariobalance on usuariobalance.usuario_id = usuario.usuario_id
      left join lista moneda on moneda.lista_id = usuariobalance.l_moneda_id
      left join usuarioplan on usuarioplan.usuario_id =  usuario.usuario_id and usuarioplan_activo = '1'
      left join lista plan  on plan.lista_id = usuarioplan.plan_id


    ",
    "usuario.usuario_eliminado = '0' and usuario.usuario_id = '$_COOKIE[iniuser]'");

  foreach($arrresultado as $i=>$valor){

    $moneda_siglas2 = utf8_encode($valor["moneda_siglas"]);
    $usuariobalance_disponible2 = utf8_encode($valor["usuariobalance_disponible"]);
    $usuariobalance_disponible2 = utf8_encode($valor["usuariobalance_disponible"]);
    $perfil_id2 = utf8_encode($valor["perfil_id"]);
    $usuarioplan_id = utf8_encode($valor["usuarioplan_id"]);

    if ($moneda_siglas2==""){$moneda_siglas2= " USD";}

    if ($usuariobalance_disponible2==""){$usuariobalance_disponible2=0;}

    $usuariobalance_disponible2 = number_format($usuariobalance_disponible2,2,",",".");
    $usuariobalance_disponibleheader = $usuariobalance_disponible2." ".$moneda_siglas2;

  }

  $arrresultado = $conexion->doSelect("modulo_relmodulo, modulo_padre, modulo_defecto, modulo_sistema, tipomodulo_id, modulo_id, modulo_multiplemenu","modulo","modulo_activo = '1' and modulo_url = '$urlactual' ");  
  foreach($arrresultado as $i=>$valor){
    $modulo_sistema = utf8_encode($valor["modulo_sistema"]);
  }

  /*
  if ( $_COOKIE[perfil]!="1" && $_COOKIE[perfil]!="2" && $_COOKIE[perfil]!="5" && $_COOKIE[perfilactual]!="109" && $usuarioplan_id=="" && $modulo_sistema!="1" && $urlactual!="" && $urlactual!="index"){
    echo "<script language='JavaScript'>window.location = 'panel?i=1'; </script>";
    exit();
  }
  */
  
  

if($perfil_id2=="83"){
  $userbalanceheader = "
    <li class='dropdown tasks-menu'>
        <a href='userbalance'>
          <center>
            <img src='dist/img/billetera.png' style='height: 30px'>
            $usuariobalance_disponibleheader
          </center>
        </a>              
      </li>     
  ";
}

if($perfil_id2=="75"){


  $arrresultado = $conexion->doSelect("sum(usuariobalance_disponible) as total
      ",
      "usuario
        left join compania on usuario.compania_id = compania.compania_id
        left join usuario referido on referido.usuario_id = usuario.usuario_idreferido
        left join usuariobalance on usuariobalance.usuario_id = usuario.usuario_id
        left join lista moneda on moneda.lista_id = usuariobalance.l_moneda_id
        left join usuarioplan on usuarioplan.usuario_id =  usuario.usuario_id and usuarioplan_activo = '1'
        left join lista plan  on plan.lista_id = usuarioplan.plan_id

      ",
      "usuario.usuario_eliminado = '0' and usuario.perfil_id = '83'");

    foreach($arrresultado as $i=>$valor){
      $total2 = utf8_encode($valor["total"]);  
    }

    if ($total2==""){$total2=0;}

    $total2 = number_format($total2,2,",",".");
    $total2 = $total2." USD";

    $userbalanceheader = "
      <li class='dropdown tasks-menu'>
          <a href='owners'>
            <center>
              <img src='dist/img/billetera.png' style='height: 30px'>
              $total2
            </center>
          </a>              
        </li>     
    ";



}



?>