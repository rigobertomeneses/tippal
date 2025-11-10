<?php
//include_once $_SERVER['DOCUMENT_ROOT']."/admin/lib/phpmailer/libemail.php";

class LibEmailTruck{
    
    function enviarcorreo($email = null, $asunto = null, $htmlcuerpo = null,  $compania_id=null){      

        $conexion = new ConexionBd();

        $arrresultado = $conexion->doSelect("conexiontruck_email",
            "conexiontruck",
            "conexiontruck_activo = '1'");
        foreach($arrresultado as $i=>$valor){                
            $conexiontruck_email = utf8_encode($valor["conexiontruck_email"]);       
        }      


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

        $nombrecompania = $compania_nombre;

        
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
            <!-- pre-header -->
            <table style='display:none!important;'>
                <tr>
                    <td>
                        <div style='overflow:hidden;display:none;font-size:1px;color:#ffffff;line-height:1px;font-family:Arial;maxheight:0px;max-width:0px;opacity:0;'>
                            $nombrecompania
                        </div>
                    </td>
                </tr>
            </table>
            <!-- pre-header end -->
            <!-- header -->
            <table border='0' width='100%' cellpadding='0' cellspacing='0' bgcolor='ffffff'>

                <tr>
                    <td align='center'>
                        <table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590'>

                            <tr>
                                <td align='center'>

                                    <table border='0' align='center' width='590' cellpadding='0' cellspacing='0' class='container590'>

                                        <tr>
                                            <td align='center' height='70' style='height:70px;'>
                                                <a href='$urlweb' style='display: block; border-style: none !important; border: 0 !important;'><img width='300' border='0' style='display: block; width: 300px;' src='http://app.mytruckwasheuropa.com/dist/img/logo.png' alt='$nombrecompania' /></a>
                                            </td>
                                        </tr>

                                        <tr  >
                                            <td align='center' style='border-top: 3px solid #0EB521'>
                                               
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
            <!-- end header -->


            $htmlcuerpo



            

        </body>

        </html>
                ";

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

        //if (1==1){
        if ($configemail_autentica=="1" && $configemail_tipoconexion=="ssl" && $configemail_tipoconexion!="" && $configemail_puerto!="" && $configemail_encoding!="" && $configemail_usuario!="" && $configemail_clave!="" ){
        
            $mail = new PHPMailer();  

            if ($configemail_tipoconexion=="ssl"){$mail->IsSMTP();}
            if ($configemail_autentica=="1"){$mail->SMTPAuth = true;}
            

            $mail->SMTPSecure = $configemail_tipoconexion;     
            $mail->Host = $configemail_servidor; 
            $mail->Port = $configemail_puerto;
            $mail->Encoding = $configemail_encoding;
            $mail->Username = $configemail_usuario;
            $mail->Password = $configemail_clave;  

            $mail->SetFrom($compania_email, $nombrecompania);            
            $mail->AddReplyTo($emailcompaniareply);

            $mail->Subject = $subject;      // Subject (which isn't required)  

            $mail->MsgHTML($message);

            // Send To  

            $mail->AddAddress($to); // Where to send it - Recipient

            $result = $mail->Send();    // Send!  
            

        }else{


            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: '.$nombrecompania.' '.$emailcompaniareply.' ' . "\r\n" .
                    'Reply-To: '.$nombrecompania.' '.$emailcompaniareply.' ' . "\r\n" .
                    //'Cc: meneses.rigoberto@gmail.com ' . "\r\n" .

                    'X-Mailer: PHP/' . phpversion();
            
            mail($to, $subject, $message, $headers);

            mail($compania_email, $subject, $message, $headers);

        }   

        return true;
    
    }
    
        
}
?>