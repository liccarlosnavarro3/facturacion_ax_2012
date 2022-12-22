<?php

/**
 * Cargar Nuevas Facturas AX
 * 
 * Lee la carpetas de facturas y pagos, y agrega las nuevas a SQL Server
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
libxml_use_internal_errors(TRUE);
// sleep(3000); //Espera tres segundos antes de iniciar a cargar

//Librerías
require 'conexion.php';

//Ruta de carpetas
//$carpetaFacturas = '\\\\SRVMDAOS\CFDI Prod\Facturas';
//$carpetaPagos = '\\\\SRVMDAOS\CFDI Prod\Pagos';
//$carpetaFacturas = '\\\\172.16.110.15\\CFDI Prod\\Facturas';
//$carpetaPagos = '\\\\172.16.110.15\\CFDI Prod\\Pagos';
$carpetaFacturas = 'C:\\temp\\Facturas';
$carpetaPagos = 'C:\\temp\\Pagos';
//$carpetaFacturas = 'C:\\CXC\\Facturas';
//$carpetaPagos = 'C:\\CXC\\Pagos';

//Fechas de actualización
$FechaActual = date('Y-m-d');
//$FechaDias = date("Y-m-d", strtotime($FechaActual . "- 3 days"));
$FechaDias = date("Y-m-d", strtotime($FechaActual . "- 33 days"));

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cargar Nuevas Facturas AX | ABC Leasing</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div id="header">
        <img src="img/abc.png" alt="ABC Leasing" class="header">
    </div>
    <h1>Cargar Nuevas Facturas</h1>
    <div>
        <h2>Inicia carga de facturas nuevas</h2>
        <?php
	echo "<br>".$FechaDias."<br>";
        cargar_facturas_nuevas($carpetaFacturas, $ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $FechaDias);
        cargar_pagos_nuevos($carpetaPagos, $ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $FechaDias);
        ?>
        <h3>Termina carga de facturas nuevas</h3>
    </div>
    <a href="index.php" class="link">Regresar al inicio</a>
</body>

</html>

<?php

function cargar_facturas_nuevas($carpetaFacturas, $ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $FechaDias)
{
    try {
        $insert = 0;
        $total = 0;

        $FechaObjeto = new DateTime($FechaDias);

        echo "<ol>";
        $IterarArchivos = new FilesystemIterator($carpetaFacturas);
        foreach ($IterarArchivos as $ArchivoIterado) {
            //if ($ArchivoIterado->getMTime() >= $FechaObjeto->getTimestamp()) {
                $archivo = $ArchivoIterado->getFilename();
                //echo "<br>". substr($archivo, 0, -4) . ".xml";
//                echo "<br>". substr($archivo, 0, -4) . ".pdf";
                //if (substr($archivo, 0, 1) == 'A') {
                    if (substr($archivo, -3) == 'xml') {
                        $total = $total + 1;
                        $ConsultaSQLServer = "Select * From Facturas_Clientes Where archivo = ?";

                        $ConexionSQLServer = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);

                        $SentenciaSQLServer = $ConexionSQLServer->prepare($ConsultaSQLServer);
                        $SentenciaSQLServer->execute(array(substr($archivo, 0, -4)));

                        $ResultadoSQLServer = $SentenciaSQLServer->fetch();

                        if (!$ResultadoSQLServer) {
                            $insert = $insert + 1;

                            $xmlArray = simplexml_load_file($carpetaFacturas . '\\' . $archivo);
                            $namespaces = $xmlArray->getNamespaces(true);

                            $xmlArray->registerXPathNamespace('t', $namespaces['tfd']);

                            $serie = $xmlArray['Serie'];
                            $folio = $xmlArray['Folio'];
                            $cve_cotizacion = $serie . $folio;

                            $TipoDeComprobante = $xmlArray['TipoDeComprobante'];

                            if ($TipoDeComprobante == 'I')
                                $tipoDocumento = '1';
                            elseif ($TipoDeComprobante == 'E')
                                $tipoDocumento = '5';
                            else
                                $tipoDocumento = '10';

                            $version = $xmlArray['Version'];
                            $subtotal = $xmlArray['SubTotal'];
                            $moneda = $xmlArray['Moneda'];
                            $sello = $xmlArray['Sello'];
                            $noCertificado = $xmlArray['NoCertificado'];
                            $certificado = $xmlArray['Certificado'];

                            $fechaDocumento = $xmlArray['Fecha'];
                            $FechaSinGuion = substr($fechaDocumento, 0, 4) . substr($fechaDocumento, 5, 2) . substr($fechaDocumento, 8, 2);

                            $emisor = $xmlArray->xpath("//cfdi:Emisor");

                            $nombreXml = $emisor[0]['Rfc'] . '_CFDI_' . $serie . $folio . '_' . $FechaSinGuion . '.xml';

                            $receptor = $xmlArray->xpath("//cfdi:Receptor");
                            $nombreCliente = $receptor[0]['Nombre'];

                            $nombreCliente = str_replace("'", "", $nombreCliente);

                            $importeTotal = $xmlArray['Total'];
                            $cancelado = 'N';
                            $rfc = $receptor[0]['Rfc'];

                            $conceptos = $xmlArray->xpath("//cfdi:Conceptos//cfdi:Concepto");

                            $descripcion = $conceptos[0]['Descripcion'];

                            $impuestos = $xmlArray->xpath("//cfdi:Impuestos");
                            foreach ($impuestos as $impuesto) {
                                if (isset($impuesto['TotalImpuestosTrasladados']))
                                    $iva = $impuesto['TotalImpuestosTrasladados'];
                            }

                            foreach ($xmlArray->xpath('//t:TimbreFiscalDigital') as $tfd) {
                                $uuid = $tfd['UUID'];
                                $fechaTimbrado = $tfd['FechaTimbrado'];
                                $selloSAT = $tfd['SelloSAT'];
                                $noCertificadoSAT = $tfd['NoCertificadoSAT'];
                            }

                            guardar_facturas_nuevas($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, substr($archivo, 0, -4), $cve_cotizacion, $serie, $folio, $tipoDocumento, $moneda, $fechaDocumento, $nombreXml, $nombreCliente, $importeTotal, $cancelado, $rfc, $descripcion, $version, $subtotal, $iva, $uuid, $fechaTimbrado, $sello, $noCertificado, $certificado, $selloSAT, $noCertificadoSAT, $TipoDeComprobante);
                        }
                        $SentenciaSQLServer = null;
                        $ConexionSQLServer = null;
                        unlink(substr($carpetaFacturas . '\\' . $archivo, 0, -4) . ".xml");
                        unlink(substr($carpetaFacturas . '\\' . $archivo, 0, -4) . ".pdf");
                    }
//                    unlink(substr($carpetaFacturas . '\\' . $archivo, 0, -4) . ".xml");
//                    unlink(substr($carpetaFacturas . '\\' . $archivo, 0, -4) . ".pdf");
                //}
            //}
//            unlink(substr($carpetaFacturas . '\\' . $archivo, 0, -4) . ".xml");
//            unlink(substr($carpetaFacturas . '\\' . $archivo, 0, -4) . ".pdf");
        }
        echo "</ol>";

        # El día de hoy
        $fechaActual = date('d-m-Y');

        $dia = substr($fechaActual, 0, 2);
        $mes = substr($fechaActual, 3, 2);
        $anio = substr($fechaActual, 6, 4);

        //Código para guardar log de Facturas
        $file = fopen("./reportes/log_" . $dia . $mes . $anio . ".log", "w");
        fwrite($file, "FACTURAS" . PHP_EOL);
        fwrite($file, "Total Facturas: " . $total . PHP_EOL);
        fwrite($file, "Insertadas: " . $insert . PHP_EOL);
        fclose($file);
    } catch (Exception $e) {
        echo "<br>Error al leer archivo : " . $archivo;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_facturas_nuevas($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $archivo, $cve_cotizacion, $serie, $folio, $tipoDocumento, $moneda, $fechaDocumento, $nombreXml, $nombreCliente, $importeTotal, $cancelado, $rfc, $descripcion, $version, $subtotal, $iva, $uuid, $fechaTimbrado, $sello, $noCertificado, $certificado, $selloSAT, $noCertificadoSAT, $TipoDeComprobante)
{
    try {
        $ConsultaSQL = "Insert Into Facturas_Clientes (archivo, cve_cotizacion, serie, folio, tipoDocumento, moneda, fechaDocumento, nombreXml, nombreCliente, importeTotal, cancelado, rfc, contrato_descripcion, version, subtotal, iva, uuid, fechaTimbrado, sello, noCertificado, certificado, selloSat, noCertificadoSat, tipoComprobante) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($archivo, $cve_cotizacion, $serie, $folio, $tipoDocumento, $moneda, $fechaDocumento, $nombreXml, $nombreCliente, $importeTotal, $cancelado, $rfc, $descripcion, $version, $subtotal, $iva, $uuid, $fechaTimbrado, $sello, $noCertificado, $certificado, $selloSAT, $noCertificadoSAT, $TipoDeComprobante));

        if ($SentenciaSQL) {
            echo "<li><b> Archivo " . $archivo . "</b> guardado" . "</li>";
        } else {
            echo "<li>Error al agregar " . $archivo . "</b></li>";
        }
        $SentenciaSQL = null;
        $ConexionSQL = null;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $archivo;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function cargar_pagos_nuevos($carpetaPagos, $ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $FechaDias)
{
    try {
        $insert = 0;
        $total = 0;

        $FechaObjeto = new DateTime($FechaDias);

        $IterarArchivos = new FilesystemIterator($carpetaPagos);
        echo "<ol>";
        foreach ($IterarArchivos as $ArchivoIterado) {
//            if ($ArchivoIterado->getMTime() >= $FechaObjeto->getTimestamp()) {
                $archivo = $ArchivoIterado->getFilename();
                if (substr($archivo, -3) == 'xml') {
                    $total = $total + 1;

                    $ConsultaSQLServer = "Select * From Facturas_Clientes Where archivo = ?";

                    $ConexionSQLServer = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);

                    $SentenciaSQLServer = $ConexionSQLServer->prepare($ConsultaSQLServer);
                    $SentenciaSQLServer->execute(array(substr($archivo, 0, -4)));

                    $ResultadoSQLServer = $SentenciaSQLServer->fetch();
                    if (!$ResultadoSQLServer) {
                        $insert = $insert + 1;

                        $xmlArray = simplexml_load_file($carpetaPagos . '\\' . $archivo);
                        $namespaces = $xmlArray->getNamespaces(true);

                        $xmlArray->registerXPathNamespace('t', $namespaces['tfd']);

                        $serie = $xmlArray['Serie'];
                        $folio = $xmlArray['Folio'];
                        $cve_cotizacion = $serie . $folio;

                        $TipoDeComprobante = $xmlArray['TipoDeComprobante'];

                        if ($TipoDeComprobante == 'I')
                            $tipoDocumento = '1';
                        elseif ($TipoDeComprobante == 'E')
                            $tipoDocumento = '5';
                        else
                            $tipoDocumento = '10';

                        $version = $xmlArray['Version'];
                        $subtotal = $xmlArray['SubTotal'];
                        $moneda = $xmlArray['Moneda'];
                        $sello = $xmlArray['Sello'];
                        $noCertificado = $xmlArray['NoCertificado'];
                        $certificado = $xmlArray['Certificado'];

                        $fechaDocumento = $xmlArray['Fecha'];
                        $FechaSinGuion = substr($fechaDocumento, 0, 4) . substr($fechaDocumento, 5, 2) . substr($fechaDocumento, 8, 2);

                        $emisor = $xmlArray->xpath("//cfdi:Emisor");
                        $nombreXml = $emisor[0]['Rfc'] . '_CFDI_' . $serie . $folio . '_' . $FechaSinGuion . '.xml';

                        $receptor = $xmlArray->xpath("//cfdi:Receptor");
                        $nombreCliente = $receptor[0]['Nombre'];

                        $nombreCliente = str_replace("'", "", $nombreCliente);

                        $importeTotal = $xmlArray['Total'];
                        $cancelado = 'N';
                        $rfc = $receptor[0]['Rfc'];

                        $conceptos = $xmlArray->xpath("//cfdi:Conceptos//cfdi:Concepto");
                        $descripcion = $conceptos[0]['Descripcion'];

                        $iva = 0;

                        foreach ($xmlArray->xpath('//t:TimbreFiscalDigital') as $tfd) {
                            $uuid = $tfd['UUID'];
                            $fechaTimbrado = $tfd['FechaTimbrado'];
                            $selloSAT = $tfd['SelloSAT'];
                            $noCertificadoSAT = $tfd['NoCertificadoSAT'];
                        }

                        guardar_facturas_nuevas($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, substr($archivo, 0, -4), $cve_cotizacion, $serie,  $folio, $tipoDocumento, $moneda, $fechaDocumento, $nombreXml, $nombreCliente, $importeTotal, $cancelado, $rfc, $descripcion, $version, $subtotal, $iva, $uuid, $fechaTimbrado, $sello, $noCertificado, $certificado, $selloSAT, $noCertificadoSAT, $TipoDeComprobante);
                    }
                    $SentenciaSQLServer = null;
                    $ConexionSQLServer = null;
                    unlink(substr($carpetaPagos . '\\' . $archivo, 0, -4) . ".xml");
                    unlink(substr($carpetaPagos . '\\' . $archivo, 0, -4) . ".pdf");
                }
//            }
        }
        echo "</ol>";

        //Código para guardar log
        # El día de hoy
        $fechaActual = date('d-m-Y');
        $dia = substr($fechaActual, 0, 2);
        $mes = substr($fechaActual, 3, 2);
        $anio = substr($fechaActual, 6, 4);

        //Incrustar código para guardar log de Facturas
        $file = fopen("./reportes/log_" . $dia . $mes . $anio . ".log", "a");
        fwrite($file, " " . PHP_EOL);
        fwrite($file, "PAGOS" . PHP_EOL);
        fwrite($file, "Total Pagos: " . $total . PHP_EOL);
        fwrite($file, "Insertados: " . $insert . PHP_EOL);
        fclose($file);
    } catch (Exception $e) {
        echo "<br>Error al leer archivo : " . $archivo;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}
?>