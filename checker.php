<?php
    
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

    // GTS Server side parameters


    $devicesCount	= 0;    // Rows Quantity
    $firstRowID	    = 0;    // First ID from SQL Query
	$lastRowID	    = 0;    // Last ID from SQL Query

    $insertQuery    = "";   // Insert query results from GTS query
    $wspMsg         = "";   // WhatsApp Notification to send

    $gts_conexion 		= @new mysqli($gts_server, $gts_username, $gts_password, $gts_database, $gts_port);

    if ($gts_conexion->connect_error){
		die('Error de conectando a la base de datos: ' . $gts_conexion->connect_error);
	}

    /*  `rowID`, `accountID`, `licensePlate`, `statusCode`, `latitude`, `longitude`, speed`, `heading`, `timestamp`, `waNumber`, `sent` */
    $sqlQuery   = "SELECT * FROM `WhatsApp` WHERE `sent`=0 ORDER BY `rowID` DESC LIMIT 1;";

    $resultado 	= $gts_conexion->query($sqlQuery);
    
    if ($resultado->num_rows > 0){

		while($row = $resultado->fetch_array(MYSQLI_ASSOC)){
			if ($firstRowID == 0){
				$firstRowID = $row['rowID'];
			}

            $devicesCount++;

            $accoundID      = utf8_encode($row['accountID']);
            $licensePlate   = utf8_encode($row['licensePlate']);
            $coordinates    = utf8_encode($row['latitude']).",".utf8_encode($row['longitude']);
            $speedKph       = utf8_encode($row['speed'])."Kph";

            $localTime      = date("d/m/Y H:i:s", utf8_encode($row['timestamp']));
            $evento         = "";

            switch (utf8_encode($row['statusCode'])) {
                case 63553:
                    $evento = "Emergencia";
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
                case 61722:
                    $evento = "Exceso de Velocidad";
                        break;
            }

            $waNumner   = utf8_encode($row['waNumber']);

            /*
            Motor Encendido!
            Placa: DEM-001
            Velocidad: 35KpH
            09/07/2021 21:47:58

            Ubicación:
            https://maps.google.com/maps?&q=-19.54414,-69.95736&z=17&hl=es
            */
            
            $wspMsg     .= "*Alerta!* \r\n";
            $wspMsg     .= "Se ha registrado un evento de *".$evento."* en la unidad: *".$licensePlate."* el dia: ".$localTime." \r\n";
            $wspMsg     .= "https://maps.google.com/maps?&q=".$coordinates."&z=17&hl=es\r\n";
            $wspMsg     .= "\r\n_Esta es una notificacion automatica. *No responder a este mensaje*_";


            $insertQuery = "INSERT INTO multi (tipe, profil, wa_mode, wa_no, wa_text, wa_media, wa_file ) VALUES ('O', '3', 0, '$waNumner', '$wspMsg', '', '');";

			$lastRowID = $row['rowID'];

    	}

	}else{
		die("Todos los registros han sido enviados! No hay data nueva que enviar...");
	}

    print_r($insertQuery);

    // WhatsApp Server parameters

    $wa_conexion 		= @new mysqli($wa_server, $wa_username, $wa_password, $wa_database, $wa_port);

    if ($wa_conexion->connect_error){
		die('Error de conectando a la base de datos: ' . $wa_conexion->connect_error);
	}

    if ($wa_conexion->query($insertQuery) === TRUE) {
		print_r("Stored!");
        // $sqlUpdate 		= "UPDATE WhatsApp SET `sent`=1 WHERE `sent`=0 AND `rowID` BETWEEN ".$lastRowID." AND ".$firstRowID.";";

        // $mensajeUpdate	= "";
	
        // if ($gts_conexion->query($sqlUpdate) === TRUE) {
            
        //     $mensajeUpdate	= "Tablas actualizadas!  ";
        // } else {
        //     $mensajeUpdate	= "Error actualizando la tabla ".$gts_conexion->error;
        // }
	} else {
		print_r("Error actualizando la tabla ".$wa_conexion->error);
	}

    mysqli_close($gts_conexion);
    mysqli_close($wa_conexion);

?>