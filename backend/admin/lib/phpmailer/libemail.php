<?php
include_once "class.phpmailer.php";

class LibEmail{
    
    function enviarcorreo($email = null, $asunto = null, $htmlcuerpo = null,  $compania_id=null){

        if (is_valid_email($email)==false){
            return true;
        }

        if ($compania_id==408){
            return true;
        }

        $bgcolor = "FFFFFF";
        $bgcolorfooter = "000000";
        
        $conexion = new ConexionBd();

        $arrresultado = $conexion->doSelect("compania_nombre, compania_img, compania_email, compania_whatsapp, compania_urlweb",
            "compania",
            "compania_activo = '1' and compania_id = '$compania_id'");
        foreach($arrresultado as $i=>$valor){
                
            $compania_nombre = utf8_encode($valor["compania_nombre"]);
            $compania_img = utf8_encode($valor["compania_img"]);            
            $compania_email = utf8_encode($valor["compania_email"]);            
            $compania_whatsapp = utf8_encode($valor["compania_whatsapp"]);
            $compania_urlweb = utf8_encode($valor["compania_urlweb"]);

            $existeresultado = 1;
        }


        if ($existeresultado=="1"){
        
            $urlweb = $compania_urlweb;
            $urllogo = $compania_urlweb."admin/arch/$compania_img";
            $nombrecompania = $compania_nombre;
            $urldesuscribir = $compania_urlweb."desuscribir?email=$email";
            $emailcompania = $compania_email;
            $emailcompaniareply = $compania_email;
            $facebook ="https://www.facebook.com/";
            $instagram ="https://www.instagram.com/";

            if ($compania_urlweb==""){
                $urldesuscribir = "https://www.gestiongo.com/desuscribir?email=$email";
                $urllogo = "https://www.gestiongo.com/assets/img/logo.png";
                $compania_urlweb = "https://www.gestiongo.com/";
             }

            if ($compania_id=="376"){
                $urlweb = "https://www.virtualpay.com.ar/";
                $urllogo = "https://www.virtualpay.com.ar/assets/img/logo.png";
                $nombrecompania = "Virtual Pay";
                $urldesuscribir = "https://www.virtualpay.com.ar/desuscribir?email=$email";
                $emailcompania = "info@virtualpay.com.ar";
                $emailcompaniareply = "info@virtualpay.com.ar";
                $facebook ="https://www.facebook.com/";
                $instagram ="https://www.instagram.com/";
            }
            
            if ($compania_id=="377"){
                $urlweb = "https://www.trasladosgo.com/";
                $urllogo = "https://www.trasladosgo.com/assets/img/logo.png";
                $nombrecompania = "Traslados Go";
                $urldesuscribir = "https://www.trasladosgo.com/desuscribir?email=$email";
                $emailcompania = "info@trasladosgo.com";
                $emailcompaniareply = "info@trasladosgo.com";
                $facebook ="https://www.facebook.com/trasladosgo";
                $instagram ="https://www.instagram.com/trasladosgo";
            }

            if ($compania_id=="373"){
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }

            /*

            if ($compania_id=="373"){
                $urlweb = "https://www.gestiongo.com/";
                $urllogo = "https://www.gestiongo.com/assets/img/logo.png";
                $nombrecompania = "VT Taxi";
                $urldesuscribir = "https://www.gestiongo.com/desuscribir?email=$email";
                $emailcompania = "info@gestiongo.com";
                $emailcompaniareply = "info@gestiongo.com";
                $facebook ="https://www.facebook.com/gestiongo";
                $instagram ="https://www.instagram.com/gestiongo";
            }

            */

            if ($compania_id=="380"){
                $urlweb = "https://www.gestiongo.com/apps/mototaxibolivia/";
                $urllogo = "https://www.gestiongo.com/apps/mototaxibolivia/assets/img/logo.png";
                $nombrecompania = "Moto Taxi Bolivia";
                $urldesuscribir = "https://www.gestiongo.com/apps/mototaxibolivia/desuscribir?email=$email";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                //$facebook ="https://www.facebook.com/sistemasgo";
                //$instagram ="https://www.instagram.com/sistemasgo";
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="381"){
                $urlweb = "https://www.gestiongo.com/apps/gestion/";
                $urllogo = "https://www.gestiongo.com/apps/gestion/assets/img/logo.png";
                $nombrecompania = "VT Gestion";
                $urldesuscribir = "https://www.gestiongo.com/apps/gestion/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                //$facebook ="https://www.facebook.com/sistemasgo";
                //$instagram ="https://www.instagram.com/sistemasgo";
                $bgcolor = "002e62";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="445"){
                $urlweb = "https://www.gestiongo.com/apps/superaltos/";
                $urllogo = "https://www.gestiongo.com/apps/superaltos/assets/img/logo.png";
                $nombrecompania = "SUPER ALTOS";
                $urldesuscribir = "https://www.gestiongo.com/apps/superaltos/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "administracion@superaltos.com";
                //$facebook ="https://www.facebook.com/sistemasgo";
                //$instagram ="https://www.instagram.com/sistemasgo";
                $bgcolor = "2b3185";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="382"){
                $urlweb = "https://www.sistemasgo.com/portfolio/kepler/";
                $urllogo = "https://www.sistemasgo.com/portfolio/kepler/assets/img/logo.png";
                $nombrecompania = "KEPLER";
                $urldesuscribir = "https://www.sistemasgo.com/portfolio/kepler/desuscribir?email=$email";
                $emailcompania = "info@kepler.com";
                $emailcompaniareply = "info@kepler.com";
                //$facebook ="https://www.facebook.com/sistemasgo";
                //$instagram ="https://www.instagram.com/sistemasgo";
                $bgcolor = "6C2C67";
                $bgcolorfooter = "000000";
            }


            if ($compania_id=="395"){
                $urlweb = "https://www.appjuegana.com/";
                $urllogo = "https://www.appjuegana.com/assets/img/logojuegana.png";
                $nombrecompania = "Juegana";
                $urldesuscribir = "https://www.appjuegana.com/eliminar-cuenta";
                $emailcompania = "info@appjuegana.com";
                $emailcompaniareply = "info@appjuegana.com";
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }

            

            if ($compania_id=="387"){
                $urlweb = "https://www.agrocomercioec.com/";
                $urllogo = "https://www.agrocomercioec.com/assets/img/logoemail.png";
                $nombrecompania = "AgroComercio";
                $urldesuscribir = "https://www.agrocomercioec.com/";
                $emailcompania = "info@agrocomercioec.com";
                $emailcompaniareply = "info@agrocomercioec.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="388"){
                $urlweb = "https://www.latom.com.mx/";
                $urllogo = "https://www.latom.com.mx/assets/img/logo.png";
                $nombrecompania = "Latom";
                $urldesuscribir = "https://www.latom.com.mx/eliminacion-cuenta";
                $emailcompania = "info@latom.com.mx";
                $emailcompaniareply = "info@latom.com.mx";
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="409"){
                $urlweb = "https://www.gestiongo.com/apps/comida/";
                $urllogo = "https://www.gestiongo.com/apps/comida/assets/img/logo.png";
                $nombrecompania = "VT Comida";
                $urldesuscribir = "https://www.gestiongo.com/apps/comida/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000";
            }

            

            if ($compania_id=="394"){
                $urlweb = "https://www.gestiongo.com/appcrimepay/";
                $urllogo = "https://www.gestiongo.com/appcrimepay/assets/img/logo.png";
                $nombrecompania = "VT Recaudacion";
                $urldesuscribir = "https://www.gestiongo.com/appcrimepay/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="404"){
                $urlweb = "https://www.gestiongo.com/apps/turemolque/";
                $urllogo = "https://www.gestiongo.com/apps/turemolque/assets/img/logoturemolque.png";
                $nombrecompania = "Tu Remolque";
                $urldesuscribir = "https://www.gestiongo.com/apps/turemolque/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="405"){
                $urlweb = "https://www.gestiongo.com/apps/farmalife/";
                $urllogo = "https://www.gestiongo.com/apps/farmalife/assets/img/logo.png";
                $nombrecompania = "Farmalife";
                $urldesuscribir = "https://www.gestiongo.com/apps/farmalife/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "7b2476";
                $bgcolorfooter = "7b2476";
            }

            if ($compania_id=="406"){
                $urlweb = "https://www.desimlatam.com/";
                $urllogo = "https://www.desimlatam.com/assets/images/logo.png";
                $nombrecompania = "Desim Latam";
                $urldesuscribir = "https://www.desimlatam.com/eliminar-cuenta";
                $emailcompania = "info@desimlatam.com";
                $emailcompaniareply = "info@desimlatam.com";
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }
            
            if ($compania_id=="385"){
                $urlweb = "https://www.gestiongo.com/appmarket/";
                $urllogo = "https://www.gestiongo.com/appmarket/assets/img/logo.png";
                $nombrecompania = "VT MarketPlace";
                $urldesuscribir = "https://www.gestiongo.com/appmarket/";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="408"){
                $urlweb = "https://www.gestiongo.com/apps/kass/";
                $urllogo = "https://www.gestiongo.com/apps/kass/assets/img/logo.png";
                $nombrecompania = "KASS";
                $urldesuscribir = "https://www.gestiongo.com/apps/kass/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="441"){
                $urlweb = "https://www.gestiongo.com/apps/lahuerta/";
                $urllogo = "https://www.gestiongo.com/apps/lahuerta/assets/img/logo.png";
                $nombrecompania = "VT Verdulería";
                $urldesuscribir = "https://www.gestiongo.com/apps/lahuerta/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="444"){
                $urlweb = "https://www.gestiongo.com/apps/indproyect/";
                $urllogo = "https://www.gestiongo.com/apps/indproyect/assets/img/logo.png";
                $nombrecompania = "INDPROYECT";
                $urldesuscribir = "https://www.gestiongo.com/apps/indproyect/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "ventas@indproyect.com";
                $bgcolor = "000000";
                $bgcolorfooter = "000000";
            }

            if ($compania_id=="447"){
                $urlweb = "https://www.gestiongo.com/apps/cmlstore/";
                $urllogo = "https://www.gestiongo.com/apps/cmlstore/assets/img/logo.png";
                $nombrecompania = "CML Store";
                $urldesuscribir = "https://www.gestiongo.com/apps/cmlstore/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "sales@cml-store.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000";
                
            }

            if ($compania_id=="449"){
                $urlweb = "https://www.argenvios.com/";
                $urllogo = "https://www.argenvios.com/assets/img/logo.png";
                $nombrecompania = "Argenvios";
                $urldesuscribir = "https://www.argenvios.com/eliminar-cuenta";
                $emailcompania = "info@argenvios.com";
                $emailcompaniareply = "info@argenvios.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="451"){
                $urlweb = "https://www.gestiongo.com/";
                $urllogo = "https://www.gestiongo.com/apps/pacenosdriver/assets/img/logo.png";
                $nombrecompania = "Pacenos Driver";
                $urldesuscribir = "https://www.gestiongo.com/apps/pacenosdriver/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="452"){
                $urlweb = "https://www.medfyndhconecth.com/";
                $urllogo = "https://www.medfyndhconecth.com/assets/img/logo.png";
                $nombrecompania = "Med FyndH ConectH";
                $urldesuscribir = "https://www.medfyndhconecth.com/eliminar-cuenta";
                $emailcompania = "info@medfyndhconecth.com";
                $emailcompaniareply = "info@medfyndhconecth.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="453"){
                $urlweb = "https://www.iqnetingapps.com/";
                $urllogo = "https://www.iqnetingapps.com/assets/img/logo.png";
                $nombrecompania = "IQneting";
                $urldesuscribir = "https://www.iqnetingapps.com/eliminar-cuenta";
                $emailcompania = "info@iqnetingapps.com";
                $emailcompaniareply = "info@iqnetingapps.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="454"){
                $urlweb = "https://www.taxidurango.app/";
                $urllogo = "https://www.taxidurango.app/assets/img/logo.png";
                $nombrecompania = "Taxi Durango";
                $urldesuscribir = "https://www.taxidurango.app/eliminar-cuenta";
                $emailcompania = "info@taxidurango.app";
                $emailcompaniareply = "info@taxidurango.app";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="455"){
                $urlweb = "https://www.gestiongo.com/apps/globalexpress/";
                $urllogo = "httphttps://www.gestiongo.com/apps/globalexpress/assets/img/logo.png";
                $nombrecompania = "Global Express";
                $urldesuscribir = "https://www.gestiongo.com/apps/globalexpress/eliminar-cuenta";
                $emailcompania = "info@gestiongo.com";
                $emailcompaniareply = "info@gestiongo.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="457"){
                $urlweb = "https://www.satelitaxi.com/";
                $urllogo = "https://www.satelitaxi.com/assets/img/logo.png";
                $nombrecompania = "SateliTaxi STX App";
                $urldesuscribir = "https://www.satelitaxi.com/eliminar-cuenta";
                $emailcompania = "contacto@satelitaxi.com";
                $emailcompaniareply = "contacto@satelitaxi.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="459"){
                $urlweb = "https://www.appreinaroja.com/";
                $urllogo = "https://www.appreinaroja.com/assets/img/logo.png";
                $nombrecompania = "Reina Roja";
                $urldesuscribir = "https://www.appreinaroja.com/eliminar-cuenta";
                $emailcompania = "info@appreinaroja.com";
                $emailcompaniareply = "info@appreinaroja.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="460"){
                $urlweb = "https://www.99placeapp.com/";
                $urllogo = "https://www.99placeapp.com/assets/img/logo.png";
                $nombrecompania = "99 Place";
                $urldesuscribir = "https://www.99placeapp.com/eliminar-cuenta";
                $emailcompania = "info@99placeapp.com";
                $emailcompaniareply = "info@99placeapp.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }


            if ($compania_id=="462"){
                $urlweb = "https://www.amigomarket.com.mx/";
                $urllogo = "https://www.amigomarket.com.mx/assets/img/logo.png";
                $nombrecompania = "Amigo Market";
                $urldesuscribir = "https://www.amigomarket.com.mx/eliminar-cuenta";
                $emailcompania = "info@amigomarket.com.mx";
                $emailcompaniareply = "info@amigomarket.com.mx";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "FFFFFF"; 
            }

            if ($compania_id=="463"){ // Abunda Pay
                $urlweb = "https://www.gestiongo.com/apps/abundapay/";
                $urllogo = "https://www.gestiongo.com/apps/abundapay/assets/img/logo.png";
                $nombrecompania = "Abunda Pay";
                $urldesuscribir = "https://www.gestiongo.com/apps/abundapay/eliminar-cuenta";
                $emailcompania = "info@abundapay.com.ar";
                $emailcompaniareply = "info@abundapay.com.ar";
                $bgcolor = "000000";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="464"){ // Bufalo Soup
                $urlweb = "https://www.gestiongo.com/apps/bufalosuap/";
                $urllogo = "https://www.gestiongo.com/apps/bufalosuap/assets/img/logo.png";
                $nombrecompania = "Bufalo Soup";
                $urldesuscribir = "https://www.gestiongo.com/apps/bufalosuap/eliminar-cuenta";
                $emailcompania = "info@bufalosuap.com";
                $emailcompaniareply = "info@bufalosuap.com";
                $bgcolor = "000000";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="465"){ // PymeGo
                $urlweb = "https://www.gestiongo.com/apps/pymego/";
                $urllogo = "https://www.gestiongo.com/apps/pymego/assets/img/logo.png";
                $nombrecompania = "PymeGo";
                $urldesuscribir = "https://www.gestiongo.com/apps/pymego/eliminar-cuenta";
                $emailcompania = "info@gestiongo.com";
                $emailcompaniareply = "info@gestiongo.com";
                $bgcolor = "0b0c0b";
                $bgcolorfooter = "0b0c0b"; 
            }

            if ($compania_id=="466"){ // VT Panico
               $urlweb = "https://www.gestiongo.com/apps/panico/";
                $urllogo = "https://www.gestiongo.com/apps/panico/assets/img/logo.png";
                $nombrecompania = "VT Panico";
                $urldesuscribir = "https://www.gestiongo.com/apps/panico/eliminar-cuenta";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="467"){ // TipPal
                $urlweb = "https://www.tippalcorp.com/";
                $urllogo = "https://www.tippalcorp.com/assets/img/logo.png";
                $nombrecompania = "TipPal";
                $urldesuscribir = "https://www.tippalcorp.com/delete-account";
                $emailcompania = "info@tippalcorp.com";
                $emailcompaniareply = "info@tippalcorp.com";
                $bgcolor = "000000";
                $bgcolorfooter = "000000"; 
            }

            if ($compania_id=="468"){ // Canjear
                $urlweb = "https://www.gestiongo.com/apps/canjear/";
                $urllogo = "https://www.gestiongo.com/apps/canjear/assets/img/logo.png";
                $nombrecompania = "CanjeAR";
                $urldesuscribir = "https://www.gestiongo.com/apps/canjear/delete-account";
                $emailcompania = "info@gestiongo.com";
                $emailcompaniareply = "info@gestiongo.com";
                $bgcolor = "38A67F";
                $bgcolorfooter = "38A67F"; 
            }

            if ($compania_id=="470"){ // Control de Glucemia
                $urlweb = "https://www.gestiongo.com/apps/controldeglucemia/";
                $urllogo = "https://www.gestiongo.com/apps/controldeglucemia/assets/img/logo.png";
                $nombrecompania = "Control de Glucemia";
                $urldesuscribir = "https://www.gestiongo.com/apps/controldeglucemia/delete-account";
                $emailcompania = "info@vtdesarrollo.com";
                $emailcompaniareply = "info@vtdesarrollo.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "FFFFFF"; 
            }

            if ($compania_id=="473"){ // La Kress
                $urlweb = "https://www.avantysa.com/portfolio/lakress/";
                $urllogo = "https://www.avantysa.com/portfolio/lakress/assets/logo-Cg_u_xhZ.png";
                $nombrecompania = "La Kress";
                $urldesuscribir = "https://www.avantysa.com/portfolio/lakress/";
                $emailcompania = "info@avantysa.com";
                $emailcompaniareply = "info@avantysa.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "FFFFFF"; 
            }


            if ($compania_id=="477"){ 
                $urlweb = "https://www.yegodeliveryapp.com/";
                $urllogo = "https://www.yegodeliveryapp.com/assets/img/logo.png";
                $nombrecompania = "Yego Delivery App";
                $urldesuscribir = "https://www.yegodeliveryapp.com/delete-account";
                $emailcompania = "info@yegodeliveryapp.com";
                $emailcompaniareply = "info@yegodeliveryapp.com";
                $bgcolor = "FFFFFF";
                $bgcolorfooter = "FFFFFF"; 
            }
            
        }

        
        
        
        $html = "
        <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
        <html xmlns:v='urn:schemas-microsoft-com:vml'>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
            <meta name='viewport' content='width=device-width; initial-scale=1.0; maximum-scale=1.0;' />
            <meta name='viewport' content='width=600,initial-scale = 2.3,user-scalable=no'>
            <!--[if !mso]><!-- -->
            <link href='https://fonts.googleapis.com/css?family=Work+Sans:300,400,500,600,700' rel='stylesheet'>
            <link href='https://fonts.googleapis.com/css?family=Quicksand:300,400,700' rel='stylesheet'>
            <!-- <![endif]-->

            <title>$nombrecompania</title>

            <style type='text/css'>
                body {
                    width: 100%;
                    background-color: #ffffff;
                    margin: 0;
                    padding: 0;
                    -webkit-font-smoothing: antialiased;
                    mso-margin-top-alt: 0px;
                    mso-margin-bottom-alt: 0px;
                    mso-padding-alt: 0px 0px 0px 0px;
                }

                p,
                h1,
                h2,
                h3,
                h4 {
                    margin-top: 0;
                    margin-bottom: 0;
                    padding-top: 0;
                    padding-bottom: 0;
                }

                span.preheader {
                    display: none;
                    font-size: 1px;
                }

                html {
                    width: 100%;
                }

                table {
                    font-size: 14px;
                    border: 0;
                }
                /* ----------- responsivity ----------- */

                @media only screen and (max-width: 640px) {
                    /*------ top header ------ */
                    .main-header {
                        font-size: 20px !important;
                    }
                    .main-section-header {
                        font-size: 28px !important;
                    }
                    .show {
                        display: block !important;
                    }
                    .hide {
                        display: none !important;
                    }
                    .align-center {
                        text-align: center !important;
                    }
                    .no-bg {
                        background: none !important;
                    }
                    /*----- main image -------*/
                    .main-image img {
                        width: 440px !important;
                        height: auto !important;
                    }
                    /* ====== divider ====== */
                    .divider img {
                        width: 440px !important;
                    }
                    /*-------- container --------*/
                    .container590 {
                        width: 440px !important;
                    }
                    .container580 {
                        width: 400px !important;
                    }
                    .main-button {
                        width: 220px !important;
                    }
                    /*-------- secions ----------*/
                    .section-img img {
                        width: 320px !important;
                        height: auto !important;
                    }
                    .team-img img {
                        width: 100% !important;
                        height: auto !important;
                    }
                }

                @media only screen and (max-width: 479px) {
                    /*------ top header ------ */
                    .main-header {
                        font-size: 18px !important;
                    }
                    .main-section-header {
                        font-size: 26px !important;
                    }
                    /* ====== divider ====== */
                    .divider img {
                        width: 280px !important;
                    }
                    /*-------- container --------*/
                    .container590 {
                        width: 280px !important;
                    }
                    .container590 {
                        width: 280px !important;
                    }
                    .container580 {
                        width: 260px !important;
                    }
                    /*-------- secions ----------*/
                    .section-img img {
                        width: 280px !important;
                        height: auto !important;
                    }
                }
            </style>
            <!-- [if gte mso 9]><style type=”text/css”>
                body {
                font-family: arial, sans-serif!important;
                }
                </style>
            <![endif]-->
        </head>


        <body class='respond' leftmargin='0' topmargin='0' marginwidth='0' marginheight='0'>

            <!-- header -->
            <table border='0' width='100%' cellpadding='0' cellspacing='0' bgcolor='$bgcolor'>

                <tr>
                    <td align='center'>
                        <table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590' bgcolor='$bgcolor'>

                            <tr>
                                <td align='center'>

                                    <table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590' bgcolor='$bgcolor'>

                                        <tr>
                                            <td align='center' height='70' style='height:70px;'>
                                                <a href='$urlweb' style='display: block; border-style: none !important; border: 0 !important;'><img height='100' border='0' style='display: block; height: 100px;' src='$urllogo' alt='$nombrecompania' /></a>
                                            </td>
                                        </tr>

                                        <tr  >
                                            <td align='center' >
                                               
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td height='25' style='font-size: 25px; line-height: 25px;'>&nbsp;</td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
            <br>
            <!-- end header -->


            $htmlcuerpo


            <!-- contact section -->
            <table border='0' width='100%' cellpadding='0' cellspacing='0' bgcolor='$bgcolorfooter' class='bg_color'>

               


                <tr>
                    <td height='30' style='border-top: 1px solid #e0e0e0;font-size: 30px; line-height: 30px;'>&nbsp;</td>
                </tr>

                <tr>
                    <td align='center'>
                        <table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590 bg_color'>

                            <tr>
                                <td>
                                    <table border='0' width='590' align='left' cellpadding='0' cellspacing='0' style='border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;' class='container590'>

                                        


                                        <tr>
                                            <td align='left' style='color: #FFF; font-size: 11px; font-family:  Calibri, sans-serif; line-height: 23px; padding-left: 10px' class='text_color'>
                                                <div style='color: #FFF; font-size: 11px; font-family: Calibri, sans-serif; mso-line-height-rule: exactly; line-height: 23px;'>
                                                    Has recibido este email porque te encuentras suscrito al newsletter de $nombrecompania o porque te has registrado en nuestra plataforma.

                                                    <br>
                                                    

                                                </div>
                                            </td>
                                        </tr>
                                        <tr class='hide'>
                                            <td height='25' style='font-size: 25px; line-height: 25px;'>&nbsp;</td>
                                        </tr>                                        
                                    </table>



                                    <table border='0' width='600' align='right' cellpadding='0' cellspacing='0' style='border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; padding-left: 10px' class='container590'>


                                        <tr>
                                            <td align='left' style='padding-left: 10px'>
                                                <a href='$urlweb' style='display: block; border-style: none !important; border: 0 !important;'><img width='70' border='0' style='display: block; height: 70;' src='$urllogo' alt='$nombrecompania' /></a>
                                                
                                                <div style='color: #333333; font-size: 14px; font-family: Calibri, sans-serif; font-weight: 600; mso-line-height-rule: exactly; line-height: 23px; '>
                                                    
                                                    <a href='mailto:$emailcompaniareply' style='color: #fff; font-size: 14px; font-family: Calibri, Sans-serif; font-weight: 400;'>$emailcompaniareply</a>

                                                </div>
                                            </td>
                                            
                                        </tr>

                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td height='20' style='font-size: 20px; line-height: 20px;'>&nbsp;</td>
                </tr>

            </table>
            <!-- end section -->

        </body>

        </html>
                ";

        //$compania_id = 10;

        $arrresultado = $conexion->doSelect("

            configuracionemail.configemail_id, configemail_nombre, configuracionemail.configemail_autentica, configuracionemail.configemail_tipoconexion, configuracionemail.configemail_servidor, 
            configuracionemail.configemail_puerto, configuracionemail.configemail_encoding, configuracionemail.configemail_usuario, configuracionemail.configemail_clave, 
            configuracionemail.configemail_activo, configuracionemail.configemail_eliminado, 
            configuracionemail.usuario_idreg, configuracionemail.cuenta_id, configuracionemail.compania_id, 
            DATE_FORMAT(configemail_fechareg,'%d/%m/%Y %H:%i:%s') as configemail_fechareg,          
            cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
            cuenta.usuario_apellido as cuenta_apellido, compania_nombre
            
            ",
            "configuracionemail                     
                inner join usuario cuenta on cuenta.usuario_id = configuracionemail.cuenta_id
                inner join compania on compania.compania_id = configuracionemail.compania_id                    
            ",
            "configemail_activo = '1' and configuracionemail.compania_id = '$compania_id' ", null, "configemail_id desc");
        foreach($arrresultado as $i=>$valor){

            $configemail_id = utf8_encode($valor["configemail_id"]);
            $configemail_nombre = utf8_encode($valor["configemail_nombre"]);
            $configemail_autentica = utf8_encode($valor["configemail_autentica"]);
            $configemail_tipoconexion = utf8_encode($valor["configemail_tipoconexion"]);
            $configemail_servidor = utf8_encode($valor["configemail_servidor"]);
            $configemail_puerto = utf8_encode($valor["configemail_puerto"]);
            $configemail_encoding = utf8_encode($valor["configemail_encoding"]);
            $configemail_usuario = utf8_encode($valor["configemail_usuario"]);
            $configemail_clave = utf8_encode($valor["configemail_clave"]);
            $configemail_activo = utf8_encode($valor["configemail_activo"]);
            $configemail_fechareg = utf8_encode($valor["configemail_fechareg"]);   

        }

        $to      = $email;
        $subject = $asunto;
        $message = $html;
        //return true;

        //if (1==2){
        if ($configemail_autentica=="1" && $configemail_tipoconexion=="ssl" && $configemail_tipoconexion!="" && $configemail_puerto!="" && $configemail_encoding!="" && $configemail_usuario!="" && $configemail_clave!="" ){

            $mail = new PHPMailer(true); 

            if ($configemail_tipoconexion=="ssl"){$mail->IsSMTP();}
            if ($configemail_autentica=="1"){$mail->SMTPAuth = true;}
            

            $mail->SMTPDebug = 0;
            $mail->SMTPSecure = $configemail_tipoconexion;     
            $mail->Host = $configemail_servidor; 
            $mail->Port = $configemail_puerto;
            $mail->Encoding = $configemail_encoding;
            $mail->Username = $configemail_usuario;
            $mail->Password = $configemail_clave;  

            //$mail->SMTPDebug  = 1;

            /*
             $mail->IsSMTP();                // Sets up a SMTP connection  
            $mail->SMTPAuth = true;         // Connection with the SMTP does require authorization    
            $mail->SMTPSecure = "ssl";      // Connect using a TLS connection  
            $mail->Host = "vzla.mago-server.us";  //Gmail SMTP server address
            $mail->Port = 465;  //Gmail SMTP port
            $mail->Encoding = '7bit';


            // Authentication
            $mail->Username = "info@sistemasgo.com";
            $mail->Password = "ZVqkV3umnn";    
            */

            $mail->SetFrom($configemail_usuario, $nombrecompania);            
            $mail->AddReplyTo($configemail_usuario);

            $mail->Subject = $subject;      // Subject (which isn't required)  

            $mail->MsgHTML($message);

            // Send To  

            $mail->AddAddress($to); // Where to send it - Recipient

            $result = $mail->Send();    // Send!              

        }else{


            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: '.$nombrecompania.' '.$emailcompania.' ' . "\r\n" .
                    'Reply-To: '.$nombrecompania.' '.$emailcompaniareply.' ' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();
            
            mail($to, $subject, $message, $headers);

            //mail($compania_email, $subject, $message, $headers);

        }       
      
        return true;
    
    }
    
        
}
?>