<?php

include_once "lib/mysqlclass.php";
include_once "lib/funciones.php";


class Lista{

  function ObtenerPaisTaxiCodigo($tipolista_id=null, $cuenta=null, $compania=null){

    $conexion = new ConexionBd();  

    session_start();
    $_COOKIE[perfil] = $_COOKIE[perfil];
    $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
    $_COOKIE[idcompania] = $_COOKIE[idcompania];

    $arrresultado = $conexion->doSelect("pais.pais_codmapa",
      "
      tipolista
        inner join listaconfig on tipolista.tipolista_id = listaconfig.tipolista_id and listaconfig_activo = '1' 
        inner join pais on pais.pais_id = listaconfig.listaconfig_valoruno
        
        
      ",
      "tipolista_activo = '1' and tipolista.tipolista_config = '1' and tipolista.tipolista_id = '$tipolista_id' and listaconfig.cuenta_id = '$cuenta' and listaconfig.compania_id = '$compania' ");

    foreach($arrresultado as $i=>$valor){
        
      $pais_codmapa = utf8_encode($valor["pais_codmapa"]);
    }

    return $pais_codmapa;
  }
 
   function ObtenerListaCategoria($tipolista_id=null, $cuenta=null, $compania=null, $elementoid=null, $ordenar=null, $agregardespues=null, $listaid_rel=null, $nomarcardefecto=null, $tipocodigocategoria=null){

      if ($ordenar=="entero"){
        $orderby = "CAST(lista.lista_nombre AS UNSIGNED)";
      }else{
        $orderby = "lista.lista_orden, lista.lista_nombre";
      }

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      $option = "<option value=''>-- Seleccione --</option>";

      if ($listaid_rel!=""){
        $wherelistarel = " and lista.lista_idrel = '$listaid_rel' ";
      }

      /*
         inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                    and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' 
                    and listacuentarel_activo = '1'
      */

      $tipolista_idbuscar = $tipolista_id;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }


      if ($cuenta!="" ){
        
          $arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_nombre, listacuenta_id, lista.lista_activo, 
            listacuenta_activo, listacuenta_nombre, listacuenta_nombredos",
                "lista       
                  inner join listacategoria on listacategoria.l_categ_id = lista.lista_id and listacateg_activo = '1'
                  inner join lista tipocategoria on tipocategoria.lista_id = listacategoria.l_tipocateg_id

                    left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                    and listacuenta.compania_id = '$compania'  and listacuenta_activo = '1'
                ",
                "lista.lista_activo = '1' and (tipocategoria.lista_cod = '$tipocodigocategoria' or tipocategoria.lista_id = '$tipocodigocategoria')  and lista.tipolista_id = '$tipolista_id' $wherelistabuscar $wherelistarel  and ((lista.lista_ppal = '1') or (lista.lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "$orderby asc");
       

          if (count($arrresultado)==0){


             $arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_nombre, listacuenta_id, 
              listacuenta_activo, listacuenta_nombre, listacuenta_nombredos",
                "lista     
                    inner join listacategoria on listacategoria.l_categ_id = lista.lista_id and listacateg_activo = '1'
                    inner join lista tipocategoria on tipocategoria.lista_id = listacategoria.l_tipocateg_id            
                    left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                    and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
                ",
                "lista.lista_activo = '1' and (tipocategoria.lista_cod = '$tipocodigocategoria' or tipocategoria.lista_id = '$tipocodigocategoria') $wherelistabuscar $wherelistarel and lista.tipolista_id = '$tipolista_id'  and ((lista.lista_ppal = '1') or (lista.lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "$orderby asc");


          }

/*
          if ($tipolista_id=="7")
          {
           
          }

*/
        
        foreach($arrresultado as $i=>$valor){

          $lista_id = utf8_encode($valor["lista_id"]);  
          $lista_nombre = utf8_encode($valor["lista_nombre"]);  
          $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
          $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
          $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
          $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  

          if ($listacuenta_id!=""){
            if ($listacuenta_activo=="1"){
              if ($elementoid==$lista_id){
                  $option .= "<option selected='selected' value='$lista_id'>$listacuenta_nombre$agregardespues</option>";
              }else{
                  $option .= "<option value='$lista_id'>$listacuenta_nombre$agregardespues</option>";
              }    
            }
          }else{
            if ($elementoid==$lista_id){
                $option .= "<option selected='selected' value='$lista_id'>$lista_nombre$agregardespues</option>";
            }else{
                $option .= "<option value='$lista_id'>$lista_nombre$agregardespues</option>";
            }    
          }  
        }
      }
      
      if (count($arrresultado)==0 && $compania==""){
        $option = "<option  value=''>Seleccione Primero la Compañia</option>";
      }else if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1 && $nomarcardefecto!="1"){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre $agregardespues</option>";
      }


      return $option;

  }

  function ObtenerListaUsuarioObra2($tipolista_id=null, $cuenta=null, $compania=null, $elementoid=null, $solocompania=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      $tipolista_idbuscar = $tipolista_id;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }

      if ($solocompania=="1"){
        $wheresolocompania = " and lista.cuenta_id = '$cuenta' and lista.compania_id = '$compania' ";
      }else{
        $wheresolocompania = " and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania')) ";
      }

      if ($_COOKIE[perfil]=="1"){ // Administrador del Sistema
        
        $where = "";
        $columnacuenta = "<th>Cuenta</th>";
        $columnacompania = "<th>Compañia</th>";

      } else if ($_COOKIE[perfil]=="2"){ // Administrador de Cuenta 
          
          //$wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta' ";
          $wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta' and listacuenta.compania_id = '$compania'  ";
          $where = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";



          $wherecuenta = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";
          $wherecuentaor = " or listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";
          $wherelistacuenta = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";
          $wherelistaactivo = " and lista.lista_activo = '1' ";


      }  else { 

        $wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta' and listacuenta.compania_id = '$compania'  ";


        $where = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' and listacuenta.compania_id = '$_COOKIE[idcompania]' ";
        $wherecuenta = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";
        $wherecuentaor = " or (listacuenta.cuenta_id = '$_COOKIE[idcuenta]' and listacuenta.compania_id = '$_COOKIE[idcompania]') ";
        $wherecompania = " and listacuenta.compania_id = '$_COOKIE[idcompania]' ";    
        
        $wherelistaactivo = " and lista.lista_activo = '1' ";
      } 

      if ($_COOKIE[perfil]=="1"){

      }else if ($_COOKIE[perfil]=="2"){

      }else{ 

        $innerjoin = "
          inner join usuarioobra on usuarioobra.l_obra_id = lista.lista_id and usuarioobra_activo = '1' and usuarioobra.usuario_id = '$_COOKIE[iniuser]'
        ";
      }

      
      
      $tipolista_idbuscar = $tipolista_id;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }
      

      /*


      $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id ",
            "lista 
                inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'

                left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
                
              ",
            "lista_activo = '1' $wheresolocompaniaa $wherelistabuscara ", null, "lista_nombre asc");

     



      if (count($arrresultado)==0){

         $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id, 
         listacuenta_activo, listacuentarel_activo, listacuenta_eliminado ",
            "lista 

                left join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'
              
                left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
               
              ",
            "lista_activo = '1' $wheresolocompania $wherelistabuscara and lista.tipolista_id = '$tipolista_id' ", null, "lista_nombre asc");  

      }

      */


        $arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_nombre, lista.lista_img, lista.lista_orden, lista.lista_activo, lista.lista_ppal,      
          lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema,
              
          cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
            cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre as compania_nombre,
            cuenta.usuario_codigo as cuentasistema_codigo, cuenta.usuario_nombre as cuentasistema_nombre,
            cuenta.usuario_apellido as cuentasistema_apellido, companiasistema.compania_nombre as companiasistema_nombre,

            listacuenta.cuenta_id, listacuenta.compania_id,
            listacuenta.listacuenta_id, listacuenta.listacuenta_activo, listacuenta.listacuenta_eliminado, 
            listacuenta.listacuenta_img, listacuenta.listacuenta_orden,
          listacuenta.listacuenta_nombre,
          lista.tipolista_id",
            "lista 

              inner join usuario cuentasistema on cuentasistema.usuario_id = lista.cuenta_id
              inner join compania companiasistema on companiasistema.compania_id = lista.compania_id              

              left join listacuenta on listacuenta.lista_id = lista.lista_id
              $wherelistacuenta
                    
              left join usuario cuenta on cuenta.usuario_id = listacuenta.cuenta_id
                left join compania on compania.compania_id = listacuenta.compania_id              

                $wherelistacuenta___
                $wherecompania
               
              ",
            "
            (
            (lista.lista_activo = '1' and lista.tipolista_id = '$tipolista_id' and lista_ppal = '1' ) 
              or 
              (lista.lista_activo = '1' and lista.tipolista_id = '$tipolista_id' and lista_ppal = '0' $wherelistacuenta)
            )
            $wherelistabuscar
             ", null, "lista_nombre asc");  


        if ($tipolista_id=="118" || $tipolista_id=="117"){

            $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id, listacuentarel.tipolista_id ",
            "lista 
                inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'

                left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
                
              ",
            "lista_activo = '1' $wheresolocompaniaa $wherelistabuscara $wherelistabuscar ", null, "lista_nombre asc");
            /*
            echo "<pre>";
            print_r($arrresultado);
            echo "</pre>";


          exit();
          */
        }

        // 
/*
       


      echo "<pre>";
      print_r($tipolista_id);
      echo "</pre>";

      exit();

       print_r($arrresultado);

      exit();

      $total = count($arrresultado);

      echo $total;
      exit();

      echo "tl:$tipolista_id";
      echo "<br>";

      echo $wheresolocompania;
      exit();

*/

      $option = "<option value=''>-- Seleccione --</option>";

      foreach($arrresultado as $i=>$valor){

        /*

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);  
        $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
        $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
        $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
        $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
        $listacuentarel_id = utf8_encode($valor["listacuentarel_id"]);  

        $nombrecolocar = $lista_nombre;

        if ($listacuenta_id!=""){
          $nombrecolocar = $listacuenta_nombre;
        }

        */

        $condominio_nombre = utf8_encode($valor["condominio_nombre"]);  
        $condominio_id = utf8_encode($valor["condominio_id"]);  

        $cuenta_idsistema = utf8_encode($valor["cuenta_idsistema"]);  
        $compania_idsistema = utf8_encode($valor["compania_idsistema"]);      

        $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
        $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
        $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
        $listacuenta_eliminado = utf8_encode($valor["listacuenta_eliminado"]);  
        $listacuenta_orden = utf8_encode($valor["listacuenta_orden"]); 
        $listacuenta_img = utf8_encode($valor["listacuenta_img"]); 

        $t_cuenta_id = utf8_encode($valor["cuenta_id"]);
        $t_compania_id = utf8_encode($valor["compania_id"]);    
        
        $lista_id = utf8_encode($valor["lista_id"]);
        $lista_nombre = utf8_encode($valor["lista_nombre"]);
        $lista_img = utf8_encode($valor["lista_img"]);  
        $lista_orden = utf8_encode($valor["lista_orden"]);  
        $lista_activo = utf8_encode($valor["lista_activo"]);      
        $lista_ppal = utf8_encode($valor["lista_ppal"]);      

        $cuenta_nombre = utf8_encode($valor["cuenta_nombre"]);
        $cuenta_apellido = utf8_encode($valor["cuenta_apellido"]);
        $cuenta_codigo = utf8_encode($valor["cuenta_codigo"]);
        $cuenta = $cuenta_nombre." ".$cuenta_apellido." ";
        $compania_nombre = utf8_encode($valor["compania_nombre"]);


        $cuentasistema_nombre = utf8_encode($valor["cuentasistema_nombre"]);
        $cuentasistema_apellido = utf8_encode($valor["cuentasistema_apellido"]);
        $cuentasistema_codigo = utf8_encode($valor["cuentasistema_codigo"]);
        $cuentasistema = $cuentasistema_nombre." ".$cuentasistema_apellido." ";
        $companiasistema_nombre = utf8_encode($valor["companiasistema_nombre"]);


        $lista_activooriginal = $lista_activo;

        if ($listacuenta_eliminado=="1"){
          continue;
        }


        if ($listacuenta_id!=""){
          $lista_nombre = $listacuenta_nombre;
          $lista_orden = $listacuenta_orden;
          $lista_img = $listacuenta_img;
          $lista_activo = $listacuenta_activo;
        }

        if ($listacuentarel_id!=""){
          if ($elementoid==$lista_id){
              $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
          }else{
              $option .= "<option value='$lista_id'>$lista_nombre</option>";
          }   
        }else{
          if ($elementoid==$lista_id){
              $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
          }else{
              $option .= "<option value='$lista_id'>$lista_nombre</option>";
          }    
        } 

      }

      if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
      }

      return $option;

  }

  function ObtenerTipoListaRelacionadosCompania($cuentaseleccionada=null, $companiaseleccionada=null, $idseleccionado=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];
      //and tipolistarel.compania_id = '$companiaseleccionada'
    
      $arrresultado = $conexion->doSelect("distinct tipolistarel_id, tipolista.tipolista_id, tipolista_nombre, tipolistarel_activo",
            "tipolista
                inner join tipolistarel on tipolistarel.tipolista_id = tipolista.tipolista_id and tipolistarel_activo='1'
            ",
            "tipolista_activo = '1'  and tipolista.tipodato_id is null   and tipolistarel.cuenta_id = '$cuentaseleccionada'", null, "tipolista_nombre asc");
     

      $option = "<option value=''>-- Seleccione --</option>";

      foreach($arrresultado as $i=>$valor){

        $tipolista_id = utf8_encode($valor["tipolista_id"]);  
        $tipolista_nombre = utf8_encode($valor["tipolista_nombre"]);  

        if ($idseleccionado==$tipolista_id){
            $option .= "<option selected='selected' value='$tipolista_id'>$tipolista_nombre</option>";
        }else{
            $option .= "<option value='$tipolista_id'>$tipolista_nombre</option>";
        }          

      }

      if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$tipolista_id'>$tipolista_nombre</option>";
      }

      return $option;

  }

   function ObtenerListaListadoPerfiles($tipolista_id=null, $perfil_id=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

    

      $option = "<option value=''>-- Seleccione --</option>";
        
      $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, lista_activo, lista_cod, perfillista_id ",
              "lista 
                left join perfillistado on perfillistado.lista_id = lista.lista_id and perfillistado.perfil_id = '$perfil_id'
                and perfillista_activo = '1'
              ",
              "lista_activo = '1' and lista.tipolista_id = '$tipolista_id'", null, "lista_nombre asc");
     
      
      foreach($arrresultado as $i=>$valor){

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);        
        $lista_cod = utf8_encode($valor["lista_cod"]);  
        $perfillista_id = utf8_encode($valor["perfillista_id"]);  

        if ($perfillista_id!=""){
            $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
        }else{
            $option .= "<option value='$lista_id'>$lista_nombre</option>";
        }        
      
      }

      return $option;

    }

  function ObtenerTipoListaTodos($idseleccionado=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];
    
      $arrresultado = $conexion->doSelect("tipolista_id, tipolista_nombre","tipolista",
            "tipolista_activo = '1' ", null, "tipolista_nombre asc");
     

      $option = "<option value=''>-- Seleccione --</option>";

      foreach($arrresultado as $i=>$valor){

        $tipolista_id = utf8_encode($valor["tipolista_id"]);  
        $tipolista_nombre = utf8_encode($valor["tipolista_nombre"]);  

        if ($idseleccionado==$tipolista_id){
            $option .= "<option selected='selected' value='$tipolista_id'>$tipolista_nombre</option>";
        }else{
            $option .= "<option value='$tipolista_id'>$tipolista_nombre</option>";
        }          

      }

      if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$tipolista_id'>$tipolista_nombre</option>";
      }

      return $option;

  }


  function ObtenerListaFormaPagoPrincipal($elementoid=null){

      if ($ordenar=="entero"){
        $orderby = "CAST(lista_nombre AS UNSIGNED)";
      }else{
        $orderby = "lista_orden, lista_nombre";
      }



      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      //$option = "<option value=''>-- Seleccione --</option>";

      if ($listaid_rel!=""){
        $wherelistarel = " and lista.lista_idrel = '$listaid_rel' ";
      }

      /*
         inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                    and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' 
                    and listacuentarel_activo = '1'
      */

      $option = "<option value=''>-- Seleccione --</option>";

        
      $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, lista_activo, lista_cod",
              "lista                                  
              ",
              "lista_activo = '1' and lista.tipolista_id = '21' and lista.cuenta_id ='2' and lista.compania_id = '1' ", null, "lista_nombre asc");
     
      
      foreach($arrresultado as $i=>$valor){

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);        
        $lista_cod = utf8_encode($valor["lista_cod"]);  

        if ($elementoid==$lista_id){
            $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
        }else{
            $option .= "<option value='$lista_id'>$lista_nombre</option>";
        }        
      
      }
    
      /*
      if (count($arrresultado)==0 && $compania==""){
        $option = "<option  value=''>Seleccione Primero la Compañia</option>";
      }else if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre $agregardespues</option>";
      }

      */

      /*
      
      $arrresultado = $conexion->doSelect("lista_id, lista_nombre","lista ",
                "lista_activo = '1' and tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and cuenta_id ='$cuenta' and compania_id = '$compania'))  ", null, "lista_nombre asc");

      $option = "<option value='0'>-- Seleccione --</option>";
      foreach($arrresultado as $i=>$valor){

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);  

        if ($elementoid==$lista_id){
            $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
        }else{
            $option .= "<option value='$lista_id'>$lista_nombre</option>";
        }
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
      }

      */

      return $option;

    }

  function ObtenerListaUsuarioObra($tipolista_id=null, $cuenta=null, $compania=null, $elementoid=null, $ordenar=null, $agregardespues=null, $listaid_rel=null, $nomarcardefecto=null){

      if ($ordenar=="entero"){
        $orderby = "CAST(lista_nombre AS UNSIGNED)";
      }else{
        $orderby = "lista_orden, lista_nombre";
      }



      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      $option = "<option value=''>-- Seleccione --</option>";

      if ($listaid_rel!=""){
        $wherelistarel = " and lista.lista_idrel = '$listaid_rel' ";
      }

      /*
         inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                    and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' 
                    and listacuentarel_activo = '1'
      */

      if ($_COOKIE[perfil]=="1"){

      }else if ($_COOKIE[perfil]=="2"){

      }else{ 

        $innerjoin = "
          inner join usuarioobra on usuarioobra.l_obra_id = lista.lista_id and usuarioobra_activo = '1' and usuarioobra.usuario_id = '$_COOKIE[iniuser]'
        ";
      }

      
      
      $tipolista_idbuscar = $tipolista_id;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }


      if ($cuenta!="" ){

        
          $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuenta_id, lista_activo, 
            listacuenta_activo, listacuenta_nombre, listacuenta_nombredos",
                "lista                
                    left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                    and listacuenta.compania_id = '$compania'  and listacuenta_activo = '1'

                    $innerjoin


                ",
                "lista_activo = '1' $wherelistabuscar and lista.tipolista_id = '$tipolista_id' $wherelistarel  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "$orderby asc");
       

          if (count($arrresultado)==0){

             $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuenta_id, 
              listacuenta_activo, listacuenta_nombre, listacuenta_nombredos",
                "lista                 
                    left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                    and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'

                    $innerjoin
                ",
                "lista_activo = '1' $wherelistabuscar $wherelistarel and lista.tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "$orderby asc");


          }

        
        foreach($arrresultado as $i=>$valor){

          $lista_id = utf8_encode($valor["lista_id"]);  
          $lista_nombre = utf8_encode($valor["lista_nombre"]);  
          $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
          $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
          $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
          $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  

          if ($listacuenta_id!=""){
            if ($listacuenta_activo=="1"){
              if ($elementoid==$lista_id){
                  $option .= "<option selected='selected' value='$lista_id'>$listacuenta_nombre$agregardespues</option>";
              }else{
                  $option .= "<option value='$lista_id'>$listacuenta_nombre$agregardespues</option>";
              }    
            }
          }else{
            if ($elementoid==$lista_id){
                $option .= "<option selected='selected' value='$lista_id'>$lista_nombre$agregardespues</option>";
            }else{
                $option .= "<option value='$lista_id'>$lista_nombre$agregardespues</option>";
            }    
          }  
        }
      }
      
      if (count($arrresultado)==0 && $compania==""){
        $option = "<option  value=''>Seleccione Primero la Compañia</option>";
      }else if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1 && $nomarcardefecto!="1"){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre $agregardespues</option>";
      }

     

      /*
      
      $arrresultado = $conexion->doSelect("lista_id, lista_nombre","lista ",
                "lista_activo = '1' and tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and cuenta_id ='$cuenta' and compania_id = '$compania'))  ", null, "lista_nombre asc");

      $option = "<option value='0'>-- Seleccione --</option>";
      foreach($arrresultado as $i=>$valor){

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);  

        if ($elementoid==$lista_id){
            $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
        }else{
            $option .= "<option value='$lista_id'>$lista_nombre</option>";
        }
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
      }

      */

      return $option;

    }

  function ObtenerListaSolicitudMaterial($cuentaseleccionada=null, $companiaseleccionada=null, $proveedor =null, $trans_idseleccionar=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];
      
      if ($cuentaseleccionada==""){
        $option = "<option  value=''>Seleccione Primero la Cuenta</option>";
      }else if ($cuentaseleccionada==""){
        $option = "<option  value=''>Seleccione Primero la Compañia</option>";
      }  
      else {

      
          
         $arrresultado = $conexion->doSelect("transaccion.trans_id, trans_codigo",
            "transaccion
              inner join transaccionsolicitudmaterial on transaccionsolicitudmaterial.trans_id = transaccion.trans_id
            ",
            "trans_eliminado = '0'  and cuenta_id = '$cuentaseleccionada' and compania_id = '$companiaseleccionada' $whereproveedor", null, "trans_codigo desc");

          $option = "<option value=''>-- Seleccione --</option>";
          foreach($arrresultado as $i=>$valor){

            $trans_id = utf8_encode($valor["trans_id"]);
            $trans_codigo = utf8_encode($valor["trans_codigo"]);            

            if ($trans_idseleccionar==$trans_id){
                $option .= "<option selected='selected' value='$trans_id'>$trans_codigo</option>";
            }else{
                $option .= "<option value='$trans_id'>$trans_codigo</option>";
            }

          }
            
          if (count($arrresultado)==0){
            $option = "<option  value=''>NO EXISTEN REQUISICIONES</option>";
          }

      }
      
      return $option;

    } 

  function ObtenerListaOrdenCompra($cuentaseleccionada=null, $companiaseleccionada=null, $proveedor =null, $trans_idseleccionar=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];
      
      if ($cuentaseleccionada==""){
        $option = "<option  value=''>Seleccione Primero la Cuenta</option>";
      }else if ($cuentaseleccionada==""){
        $option = "<option  value=''>Seleccione Primero la Compañia</option>";
      }  
      else {

        if ($proveedor!=""){
          $whereproveedor = " and transaccioncompra.proveedor_id = '$proveedor' ";
        }
          
         $arrresultado = $conexion->doSelect("transaccion.trans_id, trans_codigo",
            "transaccion
              inner join transaccioncompra on transaccioncompra.trans_id = transaccion.trans_id
            ",
            "trans_eliminado = '0'  and cuenta_id = '$cuentaseleccionada' and compania_id = '$companiaseleccionada' $whereproveedorr", null, "trans_codigo desc");

          $option = "<option value=''>-- Seleccione --</option>";
          foreach($arrresultado as $i=>$valor){

            $trans_id = utf8_encode($valor["trans_id"]);
            $trans_codigo = utf8_encode($valor["trans_codigo"]);            

            if ($trans_idseleccionar==$trans_id){
                $option .= "<option selected='selected' value='$trans_id'>$trans_codigo</option>";
            }else{
                $option .= "<option value='$trans_id'>$trans_codigo</option>";
            }

          }
            
          if (count($arrresultado)==0){
            $option = "<option  value=''>NO EXISTEN ORDENES</option>";
          }

      }
      
      return $option;

    } 

  function ObtenerListaFormaPagoUsuario($usuario_id=null, $usuarioformapago_id=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];
              
        $arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_nombre",
          "listaformapago

            inner join lista on lista.lista_id = listaformapago.l_formapago_id",

          "lista_activo = '1' and listaformapago.usuario_id = '$usuario_id'");

        $option = "<option value=''>-- Seleccione --</option>";
        foreach($arrresultado as $i=>$valor){

            $lista_id = utf8_encode($valor["lista_id"]);  
            $lista_nombre = utf8_encode($valor["lista_nombre"]);  

            if ($usuarioformapago_id==$lista_id) {
                $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
              }else{
                $option .= "<option value='$lista_id'>$lista_nombre</option>";
              }
        }
          
        if (count($arrresultado)==0){
          $option = "<option  value=''>No existen Formas de Pago</option>";
        }

        if (count($arrresultado)==1){
          $option = "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
        } 
      
      
      return $option;


    }

  function ObtenerListaFormaPagoTruck($tipolista_id=null, $cuenta=null, $compania=null, $elementoid=null, $ordenar=null, $agregardespues=null, $listaid_rel=null){

      if ($ordenar=="entero"){
        $orderby = "CAST(lista_nombre AS UNSIGNED)";
      }else{
        $orderby = "lista_orden, lista_nombre";
      }



      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      //$option = "<option value=''>-- Seleccione --</option>";

      if ($listaid_rel!=""){
        $wherelistarel = " and lista.lista_idrel = '$listaid_rel' ";
      }

      /*
         inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                    and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' 
                    and listacuentarel_activo = '1'
      */


      if ($cuenta!="" ){
        
          $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuenta_id, lista_activo, lista_cod,
            listacuenta_activo, listacuenta_nombre, listacuenta_nombredos",
                "lista                
                    left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                    and listacuenta.compania_id = '$compania'  and listacuenta_activo = '1'
                ",
                "lista_activo = '1' and lista.tipolista_id = '$tipolista_id' $wherelistarel  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "$orderby asc");
       

          if (count($arrresultado)==0){

             $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuenta_id,  lista_cod,
              listacuenta_activo, listacuenta_nombre, listacuenta_nombredos",
                "lista                 
                    left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                    and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
                ",
                "lista_activo = '1' $wherelistarel and lista.tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "$orderby asc");


          }

        
        foreach($arrresultado as $i=>$valor){

          $lista_id = utf8_encode($valor["lista_id"]);  
          $lista_nombre = utf8_encode($valor["lista_nombre"]);  
          $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
          $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
          $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
          $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
          $lista_cod = utf8_encode($valor["lista_cod"]);  


          if ($elementoid==$lista_id){
              $option .= "
                <button type='button' class='btn btn-primary formapagoseleccionado' name='btnformapago' id='btn$lista_cod' onclick='marcarformapagotruck(\"".$lista_cod."\", \"".$lista_id."\")' >
                  $lista_nombre
                </button>
              ";              
          }else{
              $option .= "
                <button type='button' class='btn btn-primary' name='btnformapago' id='btn$lista_cod' onclick='marcarformapagotruck(\"".$lista_cod."\",\"".$lista_id."\")' >
                  $lista_nombre
                </button>
              ";
          }    
        
        }
      }
      /*
      if (count($arrresultado)==0 && $compania==""){
        $option = "<option  value=''>Seleccione Primero la Compañia</option>";
      }else if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre $agregardespues</option>";
      }

      */

      /*
      
      $arrresultado = $conexion->doSelect("lista_id, lista_nombre","lista ",
                "lista_activo = '1' and tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and cuenta_id ='$cuenta' and compania_id = '$compania'))  ", null, "lista_nombre asc");

      $option = "<option value='0'>-- Seleccione --</option>";
      foreach($arrresultado as $i=>$valor){

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);  

        if ($elementoid==$lista_id){
            $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
        }else{
            $option .= "<option value='$lista_id'>$lista_nombre</option>";
        }
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
      }

      */

      return $option;

    }

  function ObtenerListaUbicacionMenu($cuenta=null, $compania=null, $seccion=null){

        $conexion = new ConexionBd();  

        $tipolista_id = "112";

        session_start();
        $_COOKIE[perfil] = $_COOKIE[perfil];
        $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
        $_COOKIE[idcompania] = $_COOKIE[idcompania];

        $tipolista_idbuscar = $tipolista_id;
        $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

        if ($wherelista!=""){
          $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
        }

        $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id, seccionubicacion_id ",
            "lista 
              
              left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
              and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'

              left join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
              and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'

              left join seccionubicacion on seccionubicacion.l_ubicseccion_id = lista.lista_id and seccionubicacion.seccion_id = '$seccion' and seccionubicacion_activo= '1'
            ",
            "lista_activo = '1' $wherelistabuscar and lista.tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "lista_nombre asc");

        foreach($arrresultado as $i=>$valor){

          $seccionubicacion_id = utf8_encode($valor["seccionubicacion_id"]);  
          $lista_id = utf8_encode($valor["lista_id"]);  
          $lista_nombre = utf8_encode($valor["lista_nombre"]);  
          $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
          $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
          $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
          $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
          $listacuentarel_id = utf8_encode($valor["listacuentarel_id"]);  

          $nombrecolocar = $lista_nombre;

          if ($listacuenta_id!=""){
            $nombrecolocar = $listacuenta_nombre;
          }

          if ($seccionubicacion_id!=""){
            $option .= "<option selected='selected' value='$lista_id'>$nombrecolocar</option>";
          }else{
            $option .= "<option value='$lista_id'>$nombrecolocar</option>";
          }
        }       

        return $option;

      }

  function ObtenerLista($tipolista_id=null, $cuenta=null, $compania=null, $elementoid=null, $ordenar=null, $agregardespues=null, $listaid_rel=null, $nomarcardefecto=null, $codigobuscar=null, $devolverarrayresultado=null, $tipocategoria=null){

      if ($ordenar=="entero"){
        $orderby = "CAST(lista.lista_nombre AS UNSIGNED)";
      }else{
        $orderby = "lista.lista_orden, lista.lista_nombre";
        if ($ordenar!=""){
          $orderby = $ordenar;
        }
        
      }
      

      if ($compania=="408" && $tipolista_id=="39"){
        $orderby = "lista.lista_orden desc";
      }



      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      $option = "<option value=''>-- Seleccione --</option>";

      if ($listaid_rel!=""){
        $wherelistarel = " and lista.lista_idrel = '$listaid_rel' ";
      }

      /*
         inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                    and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' 
                    and listacuentarel_activo = '1'
      */

      $tipolista_idbuscar = $tipolista_id;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }

      if ($codigobuscar!=""){
        $wherelistabusc = " and lista.lista_cod in ($codigobuscar) ";
      }

      if ($codigobuscar!=""){
        $wherelistabusc = " and lista.lista_cod in ($codigobuscar) ";
      }

      //tipocategoria
      if ($tipocategoria!=""){
        $wherelistabusc .= " and tipocategoria.lista_nombre = '$tipocategoria'";
      }


      if ($cuenta!="" ){

        if ( ($compania=="408" || $compania=="385" || $compania=="409" || $compania == "440") && ($tipolista_id == "3" || $tipolista_id == "40" || $tipolista_id == "395" )){
          $wherelistabusc .= " and lista.compania_id = '$compania' ";
        }

        if ($compania=="406" && $tipolista_id == "16" ){
          $wherelistabusc .= " and lista.compania_id = '$compania' ";
        }
        
          $arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_nombre, lista.lista_nombredos, listacuenta_id, lista.lista_activo, listacuenta_activo, listacuenta_nombre, listacuenta_nombredos, lista.lista_cod, lista.lista_img, lista.lista_color, lista.lista_img2, lista.lista_descrip, lista.lista_icono
            ",
                "lista            
                    left join listacategoria on listacategoria.l_categ_id = lista.lista_id and listacateg_activo = '1'
		    		        left join lista tipocategoria on listacategoria.l_tipocateg_id = tipocategoria.lista_id 

                    left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                    and listacuenta.compania_id = '$compania'  and listacuenta_activo = '1'
                ",
                "lista.lista_activo = '1'  and lista.tipolista_id = '$tipolista_id' $wherelistabusc $wherelistabuscar $wherelistarel  and ((lista.lista_ppal = '1') or (lista.lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "$orderby");
       

          if (count($arrresultado)==0){


             $arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_nombre, lista.lista_nombredos, listacuenta_id, lista.lista_icono,
              listacuenta_activo, listacuenta_nombre, listacuenta_nombredos, lista.lista_cod, lista.lista_img, lista.lista_color, lista.lista_img2, lista.lista_descrip",
                "lista                 
                    left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
                    
                    left join listacategoria on listacategoria.l_categ_id = lista.lista_id    
                    left join lista listacategoriatabla on listacategoriatabla.lista_id = listacategoria.l_categ_id    
                ",
                "lista.lista_activo = '1' $wherelistabusc $wherelistabuscar $wherelistarel and lista.tipolista_id = '$tipolista_id'  and ((lista.lista_ppal = '1') or (lista.lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "$orderby");


          }

/*
          if ($tipolista_id=="7")
          {
             echo "<pre>";
            print_r($arrresultado);
            echo "</pre>";
            exit();
          }

*/  

        
        foreach($arrresultado as $i=>$valor){

          $lista_id = utf8_encode($valor["lista_id"]);  
          $lista_nombre = utf8_encode($valor["lista_nombre"]);  
          $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
          $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
          $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
          $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
          $lista_cod = utf8_encode($valor["lista_cod"]);  

          $lista_nombre = cambiarNombreEstatus($compania, $lista_nombre, $tiposervicio_cod);


          if ($listacuenta_id!=""){
            if ($listacuenta_activo=="1"){
              if ($elementoid==$lista_id){
                  $option .= "<option selected='selected' value='$lista_id'>$listacuenta_nombre$agregardespues</option>";
              }else{
                  $option .= "<option value='$lista_id'>$listacuenta_nombre$agregardespues</option>";
              }    
            }
          }else{
            if ($elementoid==$lista_id){
                $option .= "<option selected='selected' value='$lista_id'>$lista_nombre$agregardespues</option>";
            }else{
                $option .= "<option value='$lista_id'>$lista_nombre$agregardespues</option>";
            }    
          }  
        }
      }
      
      if (count($arrresultado)==0 && $compania==""){
        $option = "<option  value=''>Seleccione Primero la Compañia</option>";
      }else if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1 && $nomarcardefecto!="1"){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre $agregardespues</option>";
      }

     

      /*
      
      $arrresultado = $conexion->doSelect("lista_id, lista_nombre","lista ",
                "lista_activo = '1' and tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and cuenta_id ='$cuenta' and compania_id = '$compania'))  ", null, "lista_nombre asc");

      $option = "<option value='0'>-- Seleccione --</option>";
      foreach($arrresultado as $i=>$valor){

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);  

        if ($elementoid==$lista_id){
            $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
        }else{
            $option .= "<option value='$lista_id'>$lista_nombre</option>";
        }
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
      }

      */

      if ($devolverarrayresultado==true){
        return $arrresultado;
      }

      return $option;

    }

  function ObtenerDefectoListaConfig($tipolista_id=null, $cuenta=null, $compania=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      $arrresultado = $conexion->doSelect("tipolista.tipolista_id, tipolista_nombre, tipolista_descrip, 
        tipodato_id, listaconfig_valoruno, listaconfig_valordos, listaconfig_valortres, listaconfig_activo,
        tipolista_idlistarel, tipolista_multiple
      ",
        "
        tipolista
          inner join listaconfig on tipolista.tipolista_id = listaconfig.tipolista_id and listaconfig_activo = '1' 
                    and listaconfig.cuenta_id = '$cuenta' and listaconfig.compania_id = '$compania'
        ",
        "tipolista_activo = '1' and tipolista.tipolista_config = '1' and tipolista.tipolista_id = '$tipolista_id' ", null, "tipolista_orden asc");

      foreach($arrresultado as $i=>$valor){
          
        $tipolista_id = utf8_encode($valor["tipolista_id"]);
        $tipolista_nombre = utf8_encode($valor["tipolista_nombre"]);
        $tipolista_descrip = utf8_encode($valor["tipolista_descrip"]);  
        $t_tipodato_id = utf8_encode($valor["tipodato_id"]);
        $lista_orden = utf8_encode($valor["lista_orden"]);
        $lista_activo = utf8_encode($valor["lista_activo"]);
        $listaconfig_valoruno = utf8_encode($valor["listaconfig_valoruno"]);
        $listaconfig_valordos = utf8_encode($valor["listaconfig_valordos"]);
        $listaconfig_valortres = utf8_encode($valor["listaconfig_valortres"]);
        $tipolista_idlistarel = utf8_encode($valor["tipolista_idlistarel"]);
        $tipolista_multiple = utf8_encode($valor["tipolista_multiple"]);

      }

      return $listaconfig_valoruno;


    }

  function ObtenerListaRel($tipolista_id=null, $cuenta=null, $compania=null, $elementoid=null, $solocompania=null, $devolverarrayresultado=null, $orden=null){

    if ($orden==""){
      $orden = "lista_nombre";
    }

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      $tipolista_idbuscar = $tipolista_id;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }

      if ($solocompania=="1"){
        $wheresolocompania = " and lista.cuenta_id = '$cuenta' and lista.compania_id = '$compania' ";
      }else{
        $wheresolocompania = " and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania')) ";
      }

      if ($_COOKIE[perfil]=="1"){ // Administrador del Sistema
        
        $where = "";
        $columnacuenta = "<th>Cuenta</th>";
        $columnacompania = "<th>Compañia</th>";

      } else if ($_COOKIE[perfil]=="2"){ // Administrador de Cuenta 
          
          //$wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta' ";
          $wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta' and listacuenta.compania_id = '$compania'  ";
          $where = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";



          $wherecuenta = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";
          $wherecuentaor = " or listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";
          $wherelistacuenta = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";
          $wherelistaactivo = " and lista.lista_activo = '1' ";


      }  else { 

        $wherelistacuenta = " and listacuenta.cuenta_id = '$cuenta' and listacuenta.compania_id = '$compania'  ";


        $where = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' and listacuenta.compania_id = '$_COOKIE[idcompania]' ";
        $wherecuenta = " and listacuenta.cuenta_id = '$_COOKIE[idcuenta]' ";
        $wherecuentaor = " or (listacuenta.cuenta_id = '$_COOKIE[idcuenta]' and listacuenta.compania_id = '$_COOKIE[idcompania]') ";
        $wherecompania = " and listacuenta.compania_id = '$_COOKIE[idcompania]' ";    
        
        $wherelistaactivo = " and lista.lista_activo = '1' ";
      }   
         $arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_nombre, lista.lista_img, lista.lista_orden, lista.lista_activo, lista.lista_icono, lista.lista_ppal, lista.lista_cod,    
            lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema,
                
            cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
              cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre as compania_nombre,
              cuenta.usuario_codigo as cuentasistema_codigo, cuenta.usuario_nombre as cuentasistema_nombre,
              cuenta.usuario_apellido as cuentasistema_apellido, companiasistema.compania_nombre as companiasistema_nombre,

              listacuenta.cuenta_id, listacuenta.compania_id,
              listacuenta.listacuenta_id, listacuenta.listacuenta_activo, listacuenta.listacuenta_eliminado, 
              listacuenta.listacuenta_img, listacuenta.listacuenta_orden,
            listacuenta.listacuenta_nombre,
            lista.tipolista_id",
            "lista 

              inner join usuario cuentasistema on cuentasistema.usuario_id = lista.cuenta_id
              inner join compania companiasistema on companiasistema.compania_id = lista.compania_id              

              left join listacuenta on listacuenta.lista_id = lista.lista_id
              $wherelistacuenta

              inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'
                    
              left join usuario cuenta on cuenta.usuario_id = listacuenta.cuenta_id
                left join compania on compania.compania_id = listacuenta.compania_id              

                $wherelistacuenta___
                $wherecompania
               
              ",
            "

            (lista.lista_activo = '1' and lista.tipolista_id = '$tipolista_id' and lista_ppal = '1' ) 
            or 
            (lista.lista_activo = '1' and lista.tipolista_id = '$tipolista_id' and lista_ppal = '0' $wherelistacuenta)
             ", null, "$orden");  

         if (count($arrresultado)==0){

          $arrresultado = $conexion->doSelect("lista.lista_id, lista.lista_nombre, lista.lista_img, lista.lista_orden, lista.lista_activo, lista.lista_ppal, lista.lista_icono, lista.lista_cod,    
            lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema,
                
            cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
              cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre as compania_nombre,
              cuenta.usuario_codigo as cuentasistema_codigo, cuenta.usuario_nombre as cuentasistema_nombre,
              cuenta.usuario_apellido as cuentasistema_apellido, companiasistema.compania_nombre as companiasistema_nombre,

              listacuenta.cuenta_id, listacuenta.compania_id,
              listacuenta.listacuenta_id, listacuenta.listacuenta_activo, listacuenta.listacuenta_eliminado, 
              listacuenta.listacuenta_img, listacuenta.listacuenta_orden,
            listacuenta.listacuenta_nombre,
            lista.tipolista_id",
            "lista 

              inner join usuario cuentasistema on cuentasistema.usuario_id = lista.cuenta_id
              inner join compania companiasistema on companiasistema.compania_id = lista.compania_id              

              left join listacuenta on listacuenta.lista_id = lista.lista_id
              $wherelistacuenta
                    
              left join usuario cuenta on cuenta.usuario_id = listacuenta.cuenta_id
                left join compania on compania.compania_id = listacuenta.compania_id              

                $wherelistacuenta___
                $wherecompania
               
              ",
            "

            (lista.lista_activo = '1' and lista.tipolista_id = '$tipolista_id' and lista_ppal = '1' ) 
            or 
            (lista.lista_activo = '1' and lista.tipolista_id = '$tipolista_id' and lista_ppal = '0' $wherelistacuenta)
             ", null, "$orden");  

        }


        if ($tipolista_id=="118" || $tipolista_id=="117"){

            $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, lista.lista_icono, listacuentarel_id, listacuenta_nombre, listacuenta_id, listacuentarel.tipolista_id ",
            "lista 
                inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'

                left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
                
              ",
            "lista_activo = '1' $wheresolocompaniaa $wherelistabuscara ", null, "$orden");
            /*
            echo "<pre>";
            print_r($arrresultado);
            echo "</pre>";


          exit();
          */
        }

        // 
/*
       


      echo "<pre>";
      print_r($tipolista_id);
      echo "</pre>";

      exit();

       print_r($arrresultado);

      exit();

      $total = count($arrresultado);

      echo $total;
      exit();

      echo "tl:$tipolista_id";
      echo "<br>";

      echo $wheresolocompania;
      exit();

*/

      $option = "<option value=''>-- Seleccione --</option>";

      foreach($arrresultado as $i=>$valor){

        /*

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);  
        $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
        $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
        $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
        $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
        $listacuentarel_id = utf8_encode($valor["listacuentarel_id"]);  

        $nombrecolocar = $lista_nombre;

        if ($listacuenta_id!=""){
          $nombrecolocar = $listacuenta_nombre;
        }

        */

        $condominio_nombre = utf8_encode($valor["condominio_nombre"]);  
        $condominio_id = utf8_encode($valor["condominio_id"]);  

        $cuenta_idsistema = utf8_encode($valor["cuenta_idsistema"]);  
        $compania_idsistema = utf8_encode($valor["compania_idsistema"]);      

        $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
        $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
        $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
        $listacuenta_eliminado = utf8_encode($valor["listacuenta_eliminado"]);  
        $listacuenta_orden = utf8_encode($valor["listacuenta_orden"]); 
        $listacuenta_img = utf8_encode($valor["listacuenta_img"]); 

        $t_cuenta_id = utf8_encode($valor["cuenta_id"]);
        $t_compania_id = utf8_encode($valor["compania_id"]);    
        
        $lista_id = utf8_encode($valor["lista_id"]);
        $lista_nombre = utf8_encode($valor["lista_nombre"]);
        $lista_img = utf8_encode($valor["lista_img"]);  
        $lista_orden = utf8_encode($valor["lista_orden"]);  
        $lista_activo = utf8_encode($valor["lista_activo"]);      
        $lista_ppal = utf8_encode($valor["lista_ppal"]);      

        $cuenta_nombre = utf8_encode($valor["cuenta_nombre"]);
        $cuenta_apellido = utf8_encode($valor["cuenta_apellido"]);
        $cuenta_codigo = utf8_encode($valor["cuenta_codigo"]);
        $cuenta = $cuenta_nombre." ".$cuenta_apellido." ";
        $compania_nombre = utf8_encode($valor["compania_nombre"]);


        $cuentasistema_nombre = utf8_encode($valor["cuentasistema_nombre"]);
        $cuentasistema_apellido = utf8_encode($valor["cuentasistema_apellido"]);
        $cuentasistema_codigo = utf8_encode($valor["cuentasistema_codigo"]);
        $cuentasistema = $cuentasistema_nombre." ".$cuentasistema_apellido." ";
        $companiasistema_nombre = utf8_encode($valor["companiasistema_nombre"]);


        $lista_activooriginal = $lista_activo;

        if ($listacuenta_eliminado=="1"){
          continue;
        }


        if ($listacuenta_id!=""){
          $lista_nombre = $listacuenta_nombre;
          $lista_orden = $listacuenta_orden;
          $lista_img = $listacuenta_img;
          $lista_activo = $listacuenta_activo;
        }

        if ($listacuentarel_id!=""){
          if ($elementoid==$lista_id){
              $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
          }else{
              $option .= "<option value='$lista_id'>$lista_nombre</option>";
          }   
        }else{
          if ($elementoid==$lista_id){
              $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
          }else{
              $option .= "<option value='$lista_id'>$lista_nombre</option>";
          }    
        } 

      }

      if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
      }

      if ($devolverarrayresultado==true){
        return $arrresultado;
      }

      return $option;

  }

  function ObtenerIdLista($codigo=null, $tipolista=null, $compania_id=null){  

      $conexion = new ConexionBd(); 

      $tipolista_idbuscar = $tipolista;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }

      if ($compania_id!=""){
        $wherelistabuscar .= " and lista.compania_id = '$compania_id' ";
      }

      $lista_id = 0;

      $arrresultado2 = $conexion->doSelect("lista_id","lista", "tipolista_id = '$tipolista' and lista_cod = '$codigo' and lista_activo = '1' $wherelistabuscar");
      if (count($arrresultado2)>0){
        foreach($arrresultado2 as $i=>$valor){
          $lista_id = $valor["lista_id"];
        }
      }

      return $lista_id;

    }


  function ObtenerListaProveedores($cuentaseleccionada=null, $companiaseleccionada=null, $proveedorseleccionado =null){

        $conexion = new ConexionBd();  

        session_start();
        $_COOKIE[perfil] = $_COOKIE[perfil];
        $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
        $_COOKIE[idcompania] = $_COOKIE[idcompania];
        
        if ($cuentaseleccionada==""){
          $option = "<option  value=''>Seleccione Primero la Cuenta</option>";
        }else if ($cuentaseleccionada==""){
          $option = "<option  value=''>Seleccione Primero la Compañia</option>";
        }  
        else {
            
           $arrresultado = $conexion->doSelect("usuario_id, usuario_nombre, usuario_apellido, usuario_empresa",
              "usuario",
              "usuario_activo = '1' and perfil_id = '6' and cuenta_id = '$cuentaseleccionada' and compania_id = '$companiaseleccionada'", null, "usuario_nombre, usuario_apellido asc");

            $option = "<option value=''>-- Seleccione --</option>";
            foreach($arrresultado as $i=>$valor){

              $usuario_id = utf8_encode($valor["usuario_id"]);
              $usuario_nombre = utf8_encode($valor["usuario_nombre"]);
              $usuario_apellido = utf8_encode($valor["usuario_apellido"]);
              $usuario_empresa = utf8_encode($valor["usuario_empresa"]);

              if ($proveedorseleccionado==$usuario_id){
                    $option .= "<option selected='selected' value='$usuario_id'>$usuario_empresa</option>";
                }else{
                    $option .= "<option value='$usuario_id'>$usuario_empresa</option>";
                }

            }
              
            if (count($arrresultado)==0){
              $option = "<option  value=''>NO EXISTEN PROVEEDORES</option>";
            }

            if (count($arrresultado)==1){
              //$option = "<option selected='selected' value='$usuario_id'>$usuario_empresa</option>";
            } 
        }
        
        return $option;

      } 


  function ObtenerTipoLista($cuentaseleccionada=null, $companiaseleccionada=null, $idseleccionado =null){

        $conexion = new ConexionBd();  

        session_start();
        $_COOKIE[perfil] = $_COOKIE[perfil];
        $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
        $_COOKIE[idcompania] = $_COOKIE[idcompania];
        
        if ($cuentaseleccionada==""){
          $option = "<option  value=''>Seleccione Primero la Cuenta</option>";
        }else if ($cuentaseleccionada==""){
          $option = "<option  value=''>Seleccione Primero la Compañia</option>";
        }  
        else {
            
           $arrresultado = $conexion->doSelect("tipolista_id, tipolista_nombre",
              "tipolista",
              "tipolista_activo = '1' and tipolista_person = '1' ", null, "tipolista_nombre asc");

            $option = "<option value=''>-- Seleccione --</option>";
            foreach($arrresultado as $i=>$valor){

              $tipolista_id = utf8_encode($valor["tipolista_id"]);
              $tipolista_nombre = utf8_encode($valor["tipolista_nombre"]);        

              if ($idseleccionado==$tipolista_id){
                    $option .= "<option selected='selected' value='$tipolista_id'>$tipolista_nombre</option>";
                }else{
                    $option .= "<option value='$tipolista_id'>$tipolista_nombre</option>";
                }

            }
              
            if (count($arrresultado)==0){
              $option = "<option  value=''>NO EXISTEN TIPOS DE LISTA</option>";
            }

            if (count($arrresultado)==1){
              $option = "<option selected='selected' value='$tipolista_id'>$tipolista_nombre</option>";
            } 
        }
        

        return $option;

      }

    function ObtenerListaMultiple($tipolista_id=null, $cuenta=null, $compania=null, $tipolista_idbuscar=null, $retornar_array=false){

        $conexion = new ConexionBd();

        if ($tipolista_idbuscar!=""){
          $solouno = "1";
          $tipolista_idbuscar = $tipolista_idbuscar;
        }else{
          $tipolista_idbuscar = $tipolista_id;
        }


        session_start();
        $_COOKIE[perfil] = $_COOKIE[perfil];
        $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
        $_COOKIE[idcompania] = $_COOKIE[idcompania];


        if ($tipolista_id=="117" || $tipolista_id=="118" || $tipolista_id=="270" || $tipolista_id=="271" || $tipolista_id=="272"){
          $wherelistabuscar = "";

        }else{

          $tipolista_idbuscar = $tipolista_id;
          $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);
         
          /*
          if ($wherelista!=""){
            $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
          }
          */

          // este de abajo es para quitar los permisos por listado, debo modificar la logica del viaje
          $wherelistabuscar = " and lista.tipolista_id in ($tipolista_idbuscar) ";

        }

        /*
         

        
        */


        if ($solouno=="1"){

          // 

          $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuenta_nombre, listacuenta_id, listacuentarel_id ",
            "lista 
              
              inner join listacuenta on listacuenta.lista_id = lista.lista_id 

              left join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
              and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'

             
            ",
            "lista_activo = '1' and listacuenta.cuenta_id ='$cuenta'  and listacuenta.compania_id = '$compania' and listacuenta_activo = '1' $wherelistabuscar and lista.tipolista_id = '$tipolista_idbuscar'   ", null, "lista_nombre asc");

        }else{


          $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id ",
            "lista 
              
              left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
              and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'

              left join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
              and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'
            ",
            "lista_activo = '1' $wherelistabuscar and lista.tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "lista_nombre asc");

        }

        // Si se solicita retornar array, construir array de datos
        if ($retornar_array) {
            $opciones = array();
            $valores_seleccionados = array();

            foreach($arrresultado as $i=>$valor){
                $lista_id = utf8_encode($valor["lista_id"]);
                $lista_nombre = utf8_encode($valor["lista_nombre"]);
                $listacuenta_id = utf8_encode($valor["listacuenta_id"]);
                $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);
                $listacuentarel_id = utf8_encode($valor["listacuentarel_id"]);

                $nombrecolocar = $lista_nombre;
                if ($listacuenta_id!=""){
                    $nombrecolocar = $listacuenta_nombre;
                }

                $opciones[] = array(
                    "lista_id" => $lista_id,
                    "lista_nombre" => $nombrecolocar,
                    "lista_cod" => "",
                    "lista_orden" => ""
                );

                // Si está seleccionado, agregarlo al array de seleccionados
                if ($listacuentarel_id!=""){
                    $valores_seleccionados[] = $lista_id;
                }
            }

            return array(
                "opciones" => $opciones,
                "valores_seleccionados" => $valores_seleccionados
            );
        }
        // Si no, retornar HTML como siempre
        else {
            foreach($arrresultado as $i=>$valor){

              $lista_id = utf8_encode($valor["lista_id"]);
              $lista_nombre = utf8_encode($valor["lista_nombre"]);
              $listacuenta_id = utf8_encode($valor["listacuenta_id"]);
              $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);
              $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);
              $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);
              $listacuentarel_id = utf8_encode($valor["listacuentarel_id"]);

              $nombrecolocar = $lista_nombre;

              if ($listacuenta_id!=""){
                $nombrecolocar = $listacuenta_nombre;
              }

              if ($listacuentarel_id!=""){
                $option .= "<option selected='selected' value='$lista_id'>$nombrecolocar</option>";
              }else{
                $option .= "<option value='$lista_id'>$nombrecolocar</option>";
              }
            }

            $option = "
              <select class='form-control select2' id='$tipolista_id' name='valoresmultiple".$tipolista_id."[]' multiple='multiple' style='width:100%' >
                $option
              </select>
            ";

            return $option;
        }

    }
      
    function ObtenerListaMultipleRel($tipolista_id=null, $cuenta=null, $compania=null){

        $conexion = new ConexionBd();  

        session_start();
        $_COOKIE[perfil] = $_COOKIE[perfil];
        $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
        $_COOKIE[idcompania] = $_COOKIE[idcompania];

        /*

         

        */
        /*

        $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id ",
            "lista 
              
              left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
              and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'

              left join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
              and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'
            ",
            "lista_activo = '1' and lista.tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "lista_nombre asc");

        */

      $tipolista_idbuscar = 21;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }


       $arrresultado = $conexion->doSelect("

        lista.lista_id, lista.lista_nombre, lista.lista_img, lista.lista_orden, lista.lista_activo, lista.lista_ppal,     
        lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema,
            
        cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
          cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre as compania_nombre,
          cuenta.usuario_codigo as cuentasistema_codigo, cuenta.usuario_nombre as cuentasistema_nombre,
          cuenta.usuario_apellido as cuentasistema_apellido, companiasistema.compania_nombre as companiasistema_nombre,

          listacuenta.cuenta_id, listacuenta.compania_id,
          listacuenta.listacuenta_id, listacuenta.listacuenta_activo, listacuenta.listacuenta_eliminado, 
          listacuenta.listacuenta_img, listacuenta.listacuenta_orden,
        listacuenta.listacuenta_nombre,
        lista.tipolista_id,

        listaformapago.listaformapago_id, listaformapago.l_formapago_id, listaformapago.listaformapago_titular,
        listaformapago.listaformapago_documento, listaformapago.listaformapago_email, 
        listaformapago.listaformapago_banco, listaformapago.listaformapago_tipocuenta, 
        listaformapago.listaformapago_nrocuenta, listaformapago.listaformapago_otros, 
        listaformapago.usuario_idreg,
        DATE_FORMAT(listaformapago_fechareg,'%d/%m/%Y %H:%i:%s') as listaformapago_fechareg

          ",
        "
        lista 

          inner join usuario cuentasistema on cuentasistema.usuario_id = lista.cuenta_id
            inner join compania companiasistema on companiasistema.compania_id = lista.compania_id              

          inner join listacuenta on listacuenta.lista_id = lista.lista_id
          

          inner join listaformapago on listaformapago.l_formapago_id = lista.lista_id
                and listaformapago.listacuenta_id = listacuenta.listacuenta_id
                
          left join usuario cuenta on cuenta.usuario_id = listacuenta.cuenta_id
            left join compania on compania.compania_id = listacuenta.compania_id              

            $wherecuenta
            $wherecompania

        ",
        "lista.lista_eliminado = '0' $wherelistabuscar and listacuenta_activo = '1' and listacuenta.compania_id = '$compania' and lista.tipolista_id = '21'  ", null, "lista.lista_orden asc");
        
        /*
        if (count($arrresultado)==0){

           $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id ",
              "lista 

                  left join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'
                
                  left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
                 
                ",
              "lista_activo = '1' and lista.tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.compania_id = '$compania'))  ", null, "lista_orden, lista_nombre asc");  

        }

        */
        

        foreach($arrresultado as $i=>$valor){

          $lista_id = utf8_encode($valor["lista_id"]);  
          $lista_nombre = utf8_encode($valor["lista_nombre"]);  
          $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
          $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
          $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
          $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
          $listacuentarel_id = utf8_encode($valor["listacuentarel_id"]);  

          $nombrecolocar = $lista_nombre;

          if ($listacuenta_id!=""){
            $nombrecolocar = $listacuenta_nombre;
          }

          if ($listacuentarel_id!=""){
            $option .= "<option selected='selected' value='$lista_id'>$nombrecolocar</option>";
          }else{
            $option .= "<option value='$lista_id'>$nombrecolocar</option>";
          }
        }

        $option = "
          <select class='form-control select2' id='$tipolista_id' name='valoresmultiple".$tipolista_id."[]' multiple='multiple' style='width:100%' >
            $option
          </select>
        ";


        return $option;

  }


  function ObtenerListaRelCondominio($tipolista_id=null, $cuenta=null, $compania=null, $elementoid=null, $condominio_id=null){

      $conexion = new ConexionBd();  

      session_start();
      $_COOKIE[perfil] = $_COOKIE[perfil];
      $_COOKIE[idcuenta] = $_COOKIE[idcuenta];
      $_COOKIE[idcompania] = $_COOKIE[idcompania];

      $tipolista_idbuscar = $tipolista_id;
      $wherelista = obtenerEstatusPermitidos($tipolista_idbuscar, $_COOKIE[perfilactual]);

      if ($wherelista!=""){
        $wherelistabuscar = " and lista.lista_id in ($wherelista) ";
      }

      
      $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id ",
            "lista 
                inner join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'

                inner join listacondominio on listacondominio.lista_id = lista.lista_id  and listacondominio_activo = '1'

                left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'


                
              ",
            "lista_activo = '1' $wherelista ", null, "lista_orden, lista_nombre asc");


      if (count($arrresultado)==0){

         $arrresultado = $conexion->doSelect("lista.lista_id, lista_nombre, listacuentarel_id, listacuenta_nombre, listacuenta_id ",
            "lista 

                inner join listacondominio on listacondominio.lista_id = lista.lista_id and listacondominio_activo = '1'

                left join listacuentarel on listacuentarel.lista_id = lista.lista_id and listacuentarel.cuenta_id ='$cuenta' 
                and listacuentarel.compania_id = '$compania' and listacuentarel.tipolista_id = '$tipolista_id' and listacuentarel_activo = '1'
              
                left join listacuenta on listacuenta.lista_id = lista.lista_id and listacuenta.cuenta_id ='$cuenta' 
                and listacuenta.compania_id = '$compania' and listacuenta_activo = '1'
               
              ",
            "lista_activo = '1' $wherelista and listacondominio.condominio_id = '$condominio_id' and lista.tipolista_id = '$tipolista_id'  and ((lista_ppal = '1') or (lista_ppal = '0' and lista.cuenta_id ='$cuenta' and lista.compania_id = '$compania'))  ", null, "lista_orden, lista_nombre asc");  

      }


      $option = "<option value=''>-- Seleccione --</option>";

      foreach($arrresultado as $i=>$valor){

        $lista_id = utf8_encode($valor["lista_id"]);  
        $lista_nombre = utf8_encode($valor["lista_nombre"]);  
        $listacuenta_id = utf8_encode($valor["listacuenta_id"]);  
        $listacuenta_nombre = utf8_encode($valor["listacuenta_nombre"]);  
        $listacuenta_nombredos = utf8_encode($valor["listacuenta_nombredos"]);  
        $listacuenta_activo = utf8_encode($valor["listacuenta_activo"]);  
        $listacuentarel_id = utf8_encode($valor["listacuentarel_id"]);  

        $nombrecolocar = $lista_nombre;

        if ($listacuenta_id!=""){
          $nombrecolocar = $listacuenta_nombre;
        }

        if ($listacuentarel_id!=""){
          if ($elementoid==$lista_id){
              $option .= "<option selected='selected' value='$lista_id'>$nombrecolocar</option>";
          }else{
              $option .= "<option value='$lista_id'>$nombrecolocar</option>";
          }   
        }else{
          if ($elementoid==$lista_id){
              $option .= "<option selected='selected' value='$lista_id'>$lista_nombre</option>";
          }else{
              $option .= "<option value='$lista_id'>$lista_nombre</option>";
          }    
        } 

      }

      if (count($arrresultado)==0){
        $option = "<option  value=''>NO EXISTEN VALORES</option>";
      }

      if (count($arrresultado)==1){
        $option = "<option selected='selected' value='$lista_id'>$nombrecolocar</option>";
      }

      return $option;

  }


}
?>