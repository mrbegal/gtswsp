<?php

    /*
    SCRIPT TO SINCRONIZE TWO DIFFERENT DATABASES IN DIFERENT HOST 
	PARSING MESSAGES TO SEND WHATSAPP NOTIFICATION

	Author: Renato Beltran
    */
    
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	header('Content-Type: text/html; charset=UTF-8');
  	header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
  	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
  	header("Cache-Control: no-store, no-cache, must-revalidate");
  	header("Cache-Control: post-check=0, pre-check=0", false);
  	header("Pragma: no-cache");
  	error_reporting(E_ALL);
	date_default_timezone_set('America/Lima');

    $devicesCount	= 0;    // Rows Quantity
    $firstRowID	    = 0;    // First ID from SQL Query
	$lastRowID	    = 0;    // Last ID from SQL Query

    $wa_profile     = 3;    // Profile 
    $wa_contact     = "https://j.mp/3xEEzt6";

    $insertQuery    = "INSERT INTO multi (tipe, profil, wa_mode, wa_no, wa_text, wa_media, wa_file ) VALUES ";   // Insert query results from GTS query
    $mensajeUpdate	= "";
	$waNumbers		= 0;

    $gts_conexion 		= @new mysqli($gts_server, $gts_username, $gts_password, $gts_database, $gts_port);

    if ($gts_conexion->connect_error){
		die('Error de conectando a la base de datos: ' . $gts_conexion->connect_error);
	}

    /*  `rowID`, `accountID`, `licensePlate`, `statusCode`, `latitude`, `longitude`, speed`, `heading`, `timestamp`, `waNumber`, `sent` */
    $sqlQuery   = "SELECT * FROM `WhatsApp` WHERE `sent`=0 ORDER BY `rowID` ASC LIMIT 50;";
    
    $resultado 	= $gts_conexion->query($sqlQuery);
    
    if ($resultado->num_rows > 0){
        
		while($row = $resultado->fetch_array(MYSQLI_ASSOC)){
			if ($firstRowID == 0){
				$firstRowID = $row['rowID'];
			}

            $devicesCount++;
            $wspMsg         = "";   // WhatsApp Notification to send

            $accoundID      = utf8_encode($row['accountID']);
            $licensePlate   = utf8_encode($row['licensePlate']);
            $coordinates    = utf8_encode($row['latitude']).",".utf8_encode($row['longitude']);
            $speedKph       = utf8_encode($row['speed'])."Kph";

            $localTime      = date("d/m/Y H:i:s", utf8_encode($row['timestamp']));
            $evento         = "";

            switch (utf8_encode($row['statusCode'])) {
                case 61722:
                    $evento = "Exceso de Velocidad";
                    break;
                case 62476:
                    $evento = "Motor Encendido";
                    break;
                case 62477:
                    $evento = "Motor Apagado";
                    break;
                case 64787:
                    $evento = "Desconexion de Corriente";
                    break;
                case 63553:
                    $evento = "Emergencia";
                    break;
				default:
					$evento = "Ubicacion";
            }

            /*
            Motor Encendido!
            Placa: DEM-001
            Velocidad: 35KpH
            09/07/2021 21:47:58

            Ubicación:
            https://maps.google.com/maps?&q=-19.54414,-69.95736&z=17&hl=es
            */
            
            $wspMsg     .= "*".$evento."*! \r\n";
            $wspMsg     .= "Placa: *".$licensePlate."*\r\n";
            $wspMsg     .= "Velocidad: *".$speedKph."*\r\n";
            $wspMsg     .= "*".$localTime."*\r\n";
            $wspMsg     .= "\r\nUbicacion:\r\n";
            $wspMsg     .= "https://maps.google.com/maps?&q=".$coordinates."&z=17&hl=es";
            // $wspMsg     .= "\r\nContactenos haciendo clic aqui ".$wa_contact;
            // $wspMsg     .= "\r\n_Esta es una notificacion automatica. *No responder a este mensaje*_";

			$waNumbers	= explode(";", utf8_encode($row['waNumber']));

			$numbersQty	= count($waNumbers);

			for ($i = 0; $i < $numbersQty; $i++) {
				$insertQuery .= "('O', '$wa_profile', 0, '$waNumbers[$i]', '$wspMsg', '', ''),";
			}

			$lastRowID = $row['rowID'];
    	}

	}else{
        mysqli_close($gts_conexion);
		die("Todos los registros han sido enviados! No hay data nueva que enviar...");
	}

    $insertQuery 	= rtrim($insertQuery, ", ").";";

	$wa_conexion 		= @new mysqli($wa_server, $wa_username, $wa_password, $wa_database, $wa_port);

    if ($wa_conexion->connect_error){
		die('Error de conectando al servidor de WhatsApp: ' . $wa_conexion->connect_error);
	}

    if ($wa_conexion->query($insertQuery) === TRUE) {
        
		$sqlUpdate 		= "UPDATE WhatsApp SET `sent`=1 WHERE `rowID` BETWEEN ".$firstRowID." AND ".$lastRowID." AND `sent`=0;";
        if ($gts_conexion->query($sqlUpdate) === TRUE) {
            $mensajeUpdate	= $sqlUpdate."Tablas actualizadas!";
        } else {
            $mensajeUpdate	= "Error actualizando la tabla ".$gts_conexion->error;
        }
	} else {
		print_r("Error actualizando la tabla ".$wa_conexion->error);
	}

    mysqli_close($gts_conexion);
    mysqli_close($wa_conexion);


    print_r("  <!DOCTYPE html>\n");
	print_r("  <html lang=\"en\">\n");
	print_r("    <head>\n");
	print_r("      <meta charset=\"utf-8\">\n");
	print_r("      <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, shrink-to-fit=no\">\n");
  	print_r("      <link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css\" integrity=\"sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u\" crossorigin=\"anonymous\">");
	print_r("      <title>WhatsApp Service</title>\n");
	print_r("    </head>\n");
	print_r("    <body>\n");
	print_r("      <div class=\"container\">\n");
	print_r("         <nav class=\"navbar navbar-default\">");
	print_r("           <div class=\"container-fluid\">");
	print_r("             <div class=\"navbar-header\">");
	print_r("               <a class=\"navbar-brand\" href=\"#\">");
	// print_r("                 WebService ".$QS_url." -> ".$QS_token.".\n");
	print_r("               </a>");
	print_r("             </div>");
	print_r("           </div>");
	print_r("         </nav>");
	print_r("         <div class=\"panel panel-default\">");
	print_r("           <div class=\"panel-body\">");
	print_r("               <span>Registros enviados al webservice: ".$devicesCount." </span>");
	print_r("           </div>");
	print_r("         </div>");
	print_r("         <div class=\"panel panel-default\">");
	print_r("           <div class=\"panel-body\">");
	print_r("             <hr>");
	print_r("							<pre><code>".$insertQuery."</code></pre>");
	print_r("           </div>");
	print_r("         </div>");
	print_r("         <div class=\"panel panel-default\">");
	print_r("           <div class=\"panel-body\">");
	print_r("             <hr>");
	print_r("							<pre><code>".$mensajeUpdate."</code></pre>");
	print_r("           </div>");
	print_r("         </div>");
	print_r("         <div class='mastfoot' align='center'>");
	print_r("           <div class='inner'>");
	print_r("             <p>Sistema desarrollado por  <a href='http://aguilacontrol.com'>AguilaControl</a>, by <a target='_blank' href='https://twitter.com/renato_beltran'>@renato_beltran</a>.</p>");
	print_r("			<p>ID Inicio: ".$firstRowID.", Final: ".$lastRowID."</p>");
	print_r("           </div>");
	print_r("         </div>");
	print_r("      </div>\n");
	print_r("    </body>\n");
	print_r("  </html>\n");

    
?>