<?php

/**
 * Webservices Facturas AX
 * 
 * Ejecuta los webservices (interno y externo) para que las facturas se manden a procesar
 * 
 * @author Carlos Hugo
 * 
 * @copyright (c) 2021, ABC Leasing
 * 
 * @version 1.0
 */
    
//Configuraciones
set_time_limit(0);
date_default_timezone_set('America/Mexico_City');

// //Librerías
require 'conexion.php';
require 'lib/nusoap.php';

// //Urls de los WS
$urlWSExterna = "https://abccfdi.com/abcleasingweb/abcWeb.php?wsdl";
$urlWSInterna = "http://abckummel/enterprise/api/register_CFDI";

// //Ruta de carpetas
//$carpetaFacturas = '\\\\SRVMDAOS\CFDI Prod\Facturas';
//$carpetaPagos = '\\\\SRVMDAOS\CFDI Prod\Pagos';
$carpetaFacturas = 'C:\\CXC\\Facturas';
$carpetaPagos = 'C:\\CXC\\Pagos';
//$carpetaFacturas = 'C:\\CXC\\Facturas';
//$carpetaPagos = 'C:\\CXC\\Pagos';

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Web Services Facturas AX | ABC Leasing</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div id="header">
        <img src="img/abc.png" alt="ABC Leasing" class="header">
    </div>
    <h1>Web Services Facturas</h1>
    <div>
        <h2>Inicia llamado a los Web Services</h2>
        <?php
        call_webService($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $carpetaFacturas, $carpetaPagos, $urlWSExterna, $urlWSInterna);
        ?>
        <h3>Termina llamado a los Web Services</h3>
    </div>
    <a href="index.php" class="link">Regresar al inicio</a>
</body>

</html>

<?php
function call_webService($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $carpetaFacturas, $carpetaPagos, $urlWSExterna, $urlWSInterna)
{
    try {
        $ConsultaSQLServer =
            "Select * "
            . "From Facturas_Clientes "
            . "Where (Isnull(codigo_resultado, '') = '') "
                . "Or (Isnull(resultado_interno, '') = '' "
                . "And Isnull(uuid, '') <> '') "
            . "Order by Id Asc";
        
//        $ConsultaSQLServer =
//            "Select * "
//                . "From Facturas_Clientes "
//                . "Where ((Isnull(codigo_resultado, '') = '') "
//                . "Or (Isnull(resultado_interno, '') = '' "
//                . "And Isnull(uuid, '') <> '')) "
//                . "And serie = 'A' "
//                . "Order by Id Asc";

        //$ConsultaSQLServer = "Select * From Facturas_Clientes Where (Isnull(codigo_resultado, '') = '') Or (Isnull(resultado_interno, '') = '' And Isnull(uuid, '') <> '') Order by Id Asc";
        //$ConsultaSQLServer = "Select top 10 * From Facturas_Clientes Where Isnull(resultado_interno, '') = '' And Isnull(uuid, '') <> '' Order by Id";
        //$ConsultaSQLServer = "Select * From Facturas_Clientes Where rfc In ('HEL010307G8A', 'HMA160928431', 'HEL010307G8A0') And ((Isnull(codigo_resultado, '') = '') Or (Isnull(resultado_interno, '') = '' And Isnull(uuid, '') <> '')) Order by Id Desc";
        
//        $ConsultaSQLServer = 
//            "Select * "
//                . "From Facturas_Clientes "
//                . "Where rfc In ('CCR950314II0', 'AAD180531SB6', 'EPL0806053V4', 'DHE160818B48', 'EPM880422LV3', 'EKM970207FB9', 'EAG811205M74', 'GMA040326F27', 'GCO0303128G0', 'HEL010307G8A0', 'HMA160928431', 'HEL010307G8A', 'HEL010307GBA', 'IBD1011305M1', 'PSA131113RI2', 'RVC131113IG3', 'SME880302R49', 'SAS0807079B7', 'SEM030423186', 'VFC160729BW3', 'NWM9709244W4', 'TFI211007QA5') "
//                . "    And ((Isnull(codigo_resultado, '') = '') Or (Isnull(resultado_interno, '') = '' And Isnull(uuid, '') <> '')) "
//                . "Order by Id Asc";

//        $ConsultaSQLServer = 
//            "Select *
//            From Facturas_Clientes
//            Where ((Isnull(codigo_resultado, '') = '') Or (Isnull(resultado_interno, '') = '' And Isnull(uuid, '') <> ''))
//                    --And serie = 'A'
//                    And Substring(fechaDocumento, 9, 2) = '28'
//                    And Substring(fechaDocumento, 6, 2) = '06'
//                    And Substring(fechaDocumento, 1, 4) = '2022'
//            Order by Id Asc";

        $ConexionSQLServer = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);
        $SentenciaSQLServer = $ConexionSQLServer->prepare($ConsultaSQLServer);
        $SentenciaSQLServer->execute();

//        $i = 0;
        echo "<ol>";
        while ($ResultadoSQLServer = $SentenciaSQLServer->fetch()) {
            if ($ResultadoSQLServer['resultado_interno'] == '' and $ResultadoSQLServer['uuid'] != '') {
                webService_portal_empleados($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $ResultadoSQLServer, $carpetaFacturas, $carpetaPagos, $urlWSInterna);
            }
            if ($ResultadoSQLServer['codigo_resultado'] == '') {
                $Registro = $ResultadoSQLServer;
                webService_portal_clientes($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $Registro, $carpetaFacturas, $carpetaPagos, $urlWSExterna);
            }
            //Borrar archivos procesados
            BorrarArchivosProcesados($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $ResultadoSQLServer['archivo'], $ResultadoSQLServer['serie'], $carpetaPagos, $carpetaFacturas);
        }
        echo "</ol>";
        $SentenciaSQLServer = null;
        $ConexionSQLServer = null;
    } catch (Exception $e) {
        echo "<br>Error al ejecutar ws con " . $ResultadoSQLServer['archivo'];
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function webService_portal_clientes($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $ResultadoSQLServer, $carpetaFacturas, $carpetaPagos, $urlWSExterna)
{
    try {
        $archivo = $ResultadoSQLServer['archivo'];
        $archivoPDF = $archivo . '.pdf';
        $archivoXML = $archivo . '.xml';
        $serie = $ResultadoSQLServer['serie'];
        if (strlen($serie) > 1) {
            $pathPdf = $carpetaPagos . "\\" . $archivoPDF;
            $pathXml = $carpetaPagos . "\\" . $archivoXML;
        } else {
            $pathPdf = $carpetaFacturas . "\\" . $archivoPDF;
            $pathXml = $carpetaFacturas . "\\" . $archivoXML;
        }
        
        $b64PDF = base64_encode(file_get_contents($pathPdf));
        $b64XML = base64_encode(file_get_contents($pathXml));

        $cve_cotizacion = $ResultadoSQLServer['cve_cotizacion'];

        $folio = $ResultadoSQLServer['folio'];
        $tipoDocumento = $ResultadoSQLServer['tipoDocumento'];
        $fechaDocumento = $ResultadoSQLServer['fechaDocumento'];
        $nombreXml = $ResultadoSQLServer['nombreXml'];
        $nombreCliente = $ResultadoSQLServer['nombreCliente'];
        $importeTotal = $ResultadoSQLServer['importeTotal'];
        $cancelado = $ResultadoSQLServer['cancelado'];
        $rfc = $ResultadoSQLServer['rfc'];
        $email = $ResultadoSQLServer['email'];

        $cliente = new nusoap_client($urlWSExterna, true);

        $Resultado = $cliente->call('GuardarRegistros', array(
            'cve_cotizacion' => $cve_cotizacion,
            'serie' => $serie,
            'folio' => $folio,
            'tipoDocumento' => $tipoDocumento,
            'fechaDocumento' => $fechaDocumento,
            'nombreXml' => $nombreXml,
            'nombreCliente' => $nombreCliente,
            'importeTotal' => $importeTotal,
            'cancelado' => $cancelado,
            'rfc' => $rfc,
            'email' => $email,
            'pdfB64' => $b64PDF,
            'xmlB64' => $b64XML,
        ));
        
        actualizar_respuesta_externa($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $Resultado, $archivo);
    } catch (Exception $e) {
        echo "<br>Error al ejecutar ws externo con " . $archivo;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function webService_portal_empleados($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $ResultadoSQLServer, $carpetaFacturas, $carpetaPagos, $urlWSInterna)
{
    try {
        $archivo = $ResultadoSQLServer['archivo'];
        $archivoPDF = $archivo . '.pdf';
        $archivoXML = $archivo . '.xml';
        $serie = $ResultadoSQLServer['serie'];
        if (strlen($serie) > 1) {
            $pathPdf = $carpetaPagos . "\\" . $archivoPDF;
            $pathXml = $carpetaPagos . "\\" . $archivoXML;
        } else {
            $pathPdf = $carpetaFacturas . "\\" . $archivoPDF;
            $pathXml = $carpetaFacturas . "\\" . $archivoXML;
        }

        $b64PDF = base64_encode(file_get_contents($pathPdf));
        $b64XML = base64_encode(file_get_contents($pathXml));

        $clv_receptor = $ResultadoSQLServer['cuentaCliente'];
        $rfc = $ResultadoSQLServer['rfc'];
        $nombreCliente = $ResultadoSQLServer['nombreCliente'];

        $cve_cotizacion = $ResultadoSQLServer['cve_cotizacion'];
        $fechaDocumento = $ResultadoSQLServer['fechaDocumento'];

        $version = $ResultadoSQLServer['version'];
        $folio = $ResultadoSQLServer['folio'];
        $subtotal = $ResultadoSQLServer['subtotal'];
        $iva = $ResultadoSQLServer['iva'];
        $importeTotal = $ResultadoSQLServer['importeTotal'];
        $tipoComprobante = $ResultadoSQLServer['tipoComprobante'];
        $moneda = $ResultadoSQLServer['moneda'];

        $uuid = $ResultadoSQLServer['uuid'];
        $fechaTimbrado = $ResultadoSQLServer['fechaTimbrado'];
        $sello = $ResultadoSQLServer['sello'];
        $noCertificado = $ResultadoSQLServer['noCertificado'];
        $certificado = $ResultadoSQLServer['certificado'];
        $selloSat = $ResultadoSQLServer['selloSat'];
        $noCertificadoSat = $ResultadoSQLServer['noCertificadoSat'];
        $cadenaOriginal = $ResultadoSQLServer['cadenaOriginal'];

        $email = $ResultadoSQLServer['email'];

        $object = array(
            'Receptor' => array(
                'clv_receptor' => $clv_receptor,
                'rfc' => $rfc,
                'razon_social' => $nombreCliente,
            ),
            'Erp' => array(
                'folio' => $cve_cotizacion,
                'fecha' => $fechaDocumento,
            ),
            'CDFI' => array(
                'version' => floatval($version),
                'serie' => $serie,
                'folio' => $folio,
                'fecha' => $fechaDocumento,
                'subtotal' => floatval($subtotal),
                'iva' => floatval($iva),
                'total' => floatval($importeTotal),
                'tipodecomprobante' => $tipoComprobante,
                'moneda' => $moneda,
            ),
            'Timbre' => array(
                'uuid' => $uuid,
                'fecha' => $fechaTimbrado,
                'sello' => $sello,
                'nocertificado' => $noCertificado,
                'certificado' => $certificado,
                'sellosat' => $selloSat,
                'nocertificadosat' => $noCertificadoSat,
                'cadenaoriginal' => 'CadenaOriginal', //$cadenaOriginal,
            ),
            'Files' => array(
                'xml' => $b64XML,
                'pdf' => $b64PDF,
            ),
            'Envio' => array(
                $email,
            )
        );

        $ch = curl_init($urlWSInterna);

        $jsonDataEncoded = json_encode($object);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $r = curl_exec($ch);
        $result = json_decode($r, 1);

        actualizar_respuesta_interna($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $result['message'], $archivo);
    } catch (Exception $e) {
        echo "<br>Error al ejecutar ws externo con " . $archivo;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function actualizar_respuesta_externa($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $ResultadoSQLServer, $NombreArchivo)
{
    try {
        if (is_array($ResultadoSQLServer))
            $RespuestaSalida = implode('|', $ResultadoSQLServer);
        else
            $RespuestaSalida = $ResultadoSQLServer;

        $ConsultaSQLExt = "Update Facturas_Clientes Set fecha_envio = getdate(), codigo_resultado = ? Where archivo = ?";

        $ConexionSQLExt = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);
        $SentenciaSQLExt = $ConexionSQLExt->prepare($ConsultaSQLExt);
        $SentenciaSQLExt->execute(array($RespuestaSalida, $NombreArchivo));

        if ($SentenciaSQLExt) {
            echo "<li><b>" . $NombreArchivo . "</b> Actualización Externa con: " . $RespuestaSalida . "</li>";
        } else {
            echo "<li>Se ha producido un error al cambiar el estatus externo de la factura: <b>" . $NombreArchivo . "</b></li>";
        }

        // sleep(1);
        $SentenciaSQLExt = null;
        $ConexionSQLExt = null;
    } catch (Exception $e) {
        echo "<br>Error al actualizar respuesta de " . $NombreArchivo;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function actualizar_respuesta_interna($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $RespuestaSalida, $NombreArchivo)
{
    try {
        $ConsultaSQL = "Update Facturas_Clientes Set fecha_envio_interno = getdate(), resultado_interno = ? Where archivo = ?";

        $ConexionSQLInt = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);
        $SentenciaSQLInt = $ConexionSQLInt->prepare($ConsultaSQL);
        $SentenciaSQLInt->execute(array($RespuestaSalida, $NombreArchivo));

        if ($SentenciaSQLInt) {
            echo "<li><b>" . $NombreArchivo . "</b> Actualización Interna con: " . $RespuestaSalida . "</li>";
        } else {
            echo "<li>Se ha producido un error al cambiar el estatus interno de la factura: <b>" . $NombreArchivo . "</b></li>";
        }

        $SentenciaSQLInt = null;
        $ConexionSQLInt = null;
    } catch (Exception $e) {
        echo "<br>Error al actualizar respuesta de " . $NombreArchivo;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function BorrarArchivosProcesados($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $archivo, $serie, $carpetaPagos, $carpetaFacturas)
{
    try {
        $ConsultaSQLServer =
            "Select * "
            . "From Facturas_Clientes "
            . "Where archivo = '" . $archivo . "' "
                . "And (Isnull(codigo_resultado, '') <> '' "
                . "And Isnull(resultado_interno, '') <> '')";
        
        $ConexionSQLServer = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQLServer = $ConexionSQLServer->prepare($ConsultaSQLServer);
        $SentenciaSQLServer->execute();
        
        while ($ResultadoSQLServer = $SentenciaSQLServer->fetch()) {
            if ($ResultadoSQLServer['resultado_interno'] != '' and $ResultadoSQLServer['codigo_resultado'] != '') {
                //$archivo = $ResultadoSQLServer['archivo'];
                $archivoPDF = $archivo . '.pdf';
                $archivoXML = $archivo . '.xml';
                //$serie = $ResultadoSQLServer['serie'];
                
                if (strlen($serie) > 1) {
                    $pathPdf = $carpetaPagos . "\\" . $archivoPDF;
                    $pathXml = $carpetaPagos . "\\" . $archivoXML;
                } else {
                    $pathPdf = $carpetaFacturas . "\\" . $archivoPDF;
                    $pathXml = $carpetaFacturas . "\\" . $archivoXML;
                }
            
//              echo "<br>" . $pathPdf . "<br>";
//              echo "<br>" . $pathXml . "<br>";        
                unlink($pathPdf);
                unlink($pathXml);
            }
        }
    } catch (Exception $e) {
        echo "<br>Error al borrar archivos " . $archivo;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}
?>