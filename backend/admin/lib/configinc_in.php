<?php
/************* Cuando este conectado local ************/
/*
define ("conex","local");
define ("R","orqui"); //Clave
define ("M","orqui"); //Usuario
define ("E","orquidea"); //Bd
define ("N","localhost"); //Servidor
*/

/************* Cuando este conectado en internet ************/
/*
define ("conex","internet");
define ("R","sisteduca"); //Clave
define ("M","uepeumel_sistedu"); //Usuario
define ("E","uepeumel_sisteduca"); //Bd
define ("N","localhost"); //Servidor
*/


define ("EXP",6000000); 
setlocale (LC_CTYPE, 'es_ES');
ini_set ("display_errors","0");
ini_set ("memory_limit","-1");
//session_start();
?>