<?php

/**
 * Cargar Nuevas Facturas 40
 * 
 * Lee el registro XML, y los agrega SQL Server
 * 
 * @author Carlos Hugo
 * 
 * @copyright (c) 2022, ABC Leasing
 * 
 * @version 1.0
 */
//Configuraciones
set_time_limit(0);
date_default_timezone_set('America/Mexico_City');
libxml_use_internal_errors(TRUE);

//Librerías
require 'conexion.php';
//include 'cantidad_letras.php';

//Fechas de actualización
$FechaActual = date('Y-m-d');
$FechaDias = date("Y-m-d", strtotime($FechaActual . "- 1 days"));
$FechaInicial = "2021-06-01";
$FechaFinal = "2021-10-01";
$RFC = "VIRR720612KW0";

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
        cargar_facturas_nuevas($ServidorSQL40, $BaseDatosSQL40, $UsuarioSQL40, $PassSQL40, $FechaInicial, $FechaFinal, $RFC);
        // cargar_pagos_nuevos($carpetaPagos, $ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $FechaDias);
        ?>
        <h3>Termina carga de facturas nuevas</h3>
    </div>
    <a href="index.php" class="link">Regresar al inicio</a>
</body>

</html>

<?php

function cargar_facturas_nuevas($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $FechaInicial, $FechaFinal, $RFC)
{
    try {
        $insert = 0;
        $total = 0;

        echo "<ol>";
        $total = $total + 1;
        $ConsultaSQLServer = "Select x.rfc, cliente, nombre, codpos, correo, xml_factura, voucher, fecha
            From xmls x
                Inner Join (
                Select rfc, nombre, codpos, correo
                From clientes
                ) c On c.rfc = x.rfc
            Where fecha Between '$FechaInicial' And '$FechaFinal'
                And Isnull(procesado, 0) = 0
                /*And x.rfc > '$RFC'*/
            Order By fecha, x.rfc";

        //echo $ConsultaSQLServer;

        $ConexionSQLServer = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);

        $SentenciaSQLServer = $ConexionSQLServer->prepare($ConsultaSQLServer);
        $SentenciaSQLServer->execute();

        while ($ResultadoSQLServer = $SentenciaSQLServer->fetch()) {
            $insert = $insert + 1;

            $FechaFactura = $ResultadoSQLServer['fecha'];

            $xmlFactura = simplexml_load_string($ResultadoSQLServer['xml_factura']);
            $namespaces = $xmlFactura->getNamespaces(true);

            $xmlFactura->registerXPathNamespace('t', $namespaces['tfd']);

            // $json = json_encode($xmlFactura);
            // $array = json_decode($json, TRUE);

            //$attributes = current($xmlFactura->attributes());

            //$xml_array = unserialize(serialize(json_decode(json_encode((array) $xmlFactura), 1)));

            // foreach ($xmlFactura->Formula as $element) {
            //     foreach ($element->children() as $key => $val) {
            //         echo "{$key}: {$val}";
            //     }
            // }

            //Receptor
            $clave_receptor = $ResultadoSQLServer['cliente'];
            $rfc = $ResultadoSQLServer['rfc'];
            $nombre = $ResultadoSQLServer['nombre'];
            $pais = 'MEXICO';
            $codigo_postal = $ResultadoSQLServer['codpos'];
            $correo_electronico = $ResultadoSQLServer['correo'];
            $portal_id = 2;

            //Cotización
            $clave_cotizacion = $ResultadoSQLServer['voucher'];
            $serie = $xmlFactura['Serie'];
            $folio = $xmlFactura['Folio'];
            // $cve_cotizacion = $serie . $folio;
            $fechaDocumento = $xmlFactura['Fecha'];
            // $FechaSinGuion = substr($fechaDocumento, 0, 4) . substr($fechaDocumento, 5, 2) . substr($fechaDocumento, 8, 2);
            $formaPago = $xmlFactura['FormaPago'];
            $metodoPago = $xmlFactura['MetodoPago'];

            $version = $xmlFactura['Version'];
            $sello = $xmlFactura['Sello'];
            $noCertificado = $xmlFactura['NoCertificado'];
            $certificado = $xmlFactura['Certificado'];

            if ($metodoPago == 'PUE')
                $condicionesPago = 'PAGO EN UNA SOLA EXHIBICION';
            elseif ($metodoPago == 'PPD')
                $condicionesPago = 'PAGO EN PARCIALIDADES O DIFERIDO';
            else
                $condicionesPago = '';

            $subtotal = $xmlFactura['SubTotal'];

            // echo ('<pre>');
            // var_dump($xmlFactura);
            // echo ('</pre>');

            $impuestos = $xmlFactura->xpath("//cfdi:Impuestos");
            foreach ($impuestos as $impuesto) {
                if (isset($impuesto['TotalImpuestosTrasladados']))
                    $iva = $impuesto['TotalImpuestosTrasladados'];
            }

            $descuento = 0;
            $tipoCambio = 1;
            $moneda = $xmlFactura['Moneda'];
            $importeTotal = $xmlFactura['Total'];
            $importeTotalLetra = FUN_IMPORTE_CON_LETRA($importeTotal, 'MXN', $ConexionSQLServer);

            $tipoComprobante = $xmlFactura['TipoDeComprobante'];
            if ($tipoComprobante == 'P')
                $tipoDocumento = 'COMPLEMENTO PAGOS';
            else
                $tipoDocumento = 'FACTURA';

            $lugarExpedicion = $xmlFactura['LugarExpedicion'];
            $tipoCFDI_id = 1;

            $emisor = $xmlFactura->xpath("//cfdi:Emisor");
            $regimenFiscal = $emisor[0]['RegimenFiscal'];
            $CIA = $emisor[0]['Nombre'];
            // $CIA = 'AB&C LEASING DE MEXICO';
            // $eDireccion = 'AV. CIRCUNVALACI?N 1471 PISO 5';
            // $eMunicipio = 'GUADALAJARA';
            // $eColonia = 'LOMAS DEL COUNTRY';
            // $eEstado = 'Jalisco';
            // $eCodigoPostal = '44610';

            // $nombreXml = $emisor[0]['Rfc'] . '_CFDI_' . $serie . $folio . '_' . $FechaSinGuion . '.xml';

            $receptor = $xmlFactura->xpath("//cfdi:Receptor");

            $usoCFDI = $receptor[0]['UsoCFDI'];

            // $nombreCliente = $receptor[0]['Nombre'];
            // $nombreCliente = str_replace("'", "", $nombreCliente);

            // $cancelado = 'N';
            // $rfc = $receptor[0]['Rfc'];

            $conceptos = $xmlFactura->xpath("//cfdi:Conceptos//cfdi:Concepto");
            $cantidad = $conceptos[0]['Cantidad'];
            $unidad = $conceptos[0]['Unidad'];
            $descripcion = $conceptos[0]['Descripcion'];
            $valorUnitario = $conceptos[0]['ValorUnitario'];
            $importe = $conceptos[0]['Importe'];
            $claveUM = $conceptos[0]['ClaveUnidad'];
            $claveProdServ = $conceptos[0]['ClaveProdServ'];

            foreach ($xmlFactura->xpath('//t:TimbreFiscalDigital') as $tfd) {
                $uuid = $tfd['UUID'];
                $fechaTimbrado = $tfd['FechaTimbrado'];
                $selloSAT = $tfd['SelloSAT'];
                $noCertificadoSAT = $tfd['NoCertificadoSAT'];
            }

            if ($tipoComprobante == 'P') {
                $pagos = $xmlFactura->xpath("//cfdi:Complemento//pago10:Pagos//pago10:Pago");
                $fechaPago = $pagos[0]['FechaPago'];
                $monedaPago = $pagos[0]['MonedaP'];
                $montoPago = $pagos[0]['Monto'];
                $formaDePagoPago = $pagos[0]['FormaDePagoP'];
                $metodoPago = "PPD";
                $condicionesPago = 'PAGO EN PARCIALIDADES O DIFERIDO';
            }
            else {
                $impuestos = $xmlFactura->xpath("//cfdi:Impuestos//cfdi:Traslados//cfdi:Traslado");
                $impuestoTipo = 'Traslado';
                $impuestoImporte = $impuestos[0]['Importe'];
                $impuestoImpuesto = $impuestos[0]['Impuesto'];
                $impuestoTasaOCuota = $impuestos[0]['TasaOCuota'];
                $impuestoTipoFactor = $impuestos[0]['TipoFactor'];
            }

            $receptor_id  = guardar_facturas_receptor($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $clave_receptor, $rfc, $nombre, $pais, $codigo_postal, $correo_electronico, $portal_id, $FechaFactura);

            $cotizacion_id  = guardar_facturas_cotizacion($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $receptor_id, $clave_cotizacion, $serie, $folio, $fechaDocumento, $formaPago, $condicionesPago, $subtotal, $iva, $descuento, $tipoCambio, $moneda, $importeTotal, $importeTotalLetra, $tipoComprobante, $metodoPago, $lugarExpedicion, $regimenFiscal, $portal_id, $tipoCFDI_id, $CIA, $pais, $lugarExpedicion, $usoCFDI, $tipoDocumento);

            guardar_facturas_cot_registro($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cotizacion_id, $version, $fechaDocumento, $noCertificado, $sello, $certificado, $uuid, $noCertificadoSAT, $selloSAT, $fechaTimbrado);

            $detalle_cotizacion_id = guardar_facturas_detalle_cotizacion($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cotizacion_id, $cantidad, $unidad, $descripcion, $valorUnitario, $importe, $claveUM, $claveProdServ);

            //echo "<br>1".$detalle_cotizacion_id;

            //cambiar_estatus_procesado($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $clave_cotizacion, $rfc);

            if ($tipoComprobante == 'P') {
                $cot_pago_id = guardar_cot_pagos($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cotizacion_id, $fechaPago, $monedaPago, $montoPago, $formaDePagoPago);
                $detallesPagos = $xmlFactura->xpath("//cfdi:Complemento//pago10:Pagos//pago10:Pago//pago10:DoctoRelacionado");
                foreach ($detallesPagos as $detallePago) {
                    $idDocumento = $detallePago['IdDocumento'];
                    $serie = $detallePago['Serie'];
                    $folio = $detallePago['Folio'];
                    $monedaDR = $detallePago['MonedaDR'];
                    $numParcialidad = $detallePago['NumParcialidad'];
                    $impSaldoAnt = $detallePago['ImpSaldoAnt'];
                    $ImpPagado = $detallePago['ImpPagado'];
                    $ImpSaldoInsoluto = $detallePago['ImpSaldoInsoluto'];
                    $MetodoDePagoDR = $detallePago['MetodoDePagoDR'];
                    
                    guardar_cot_documentos($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cot_pago_id, $idDocumento, $serie, $folio, $monedaDR, $numParcialidad, $impSaldoAnt, $ImpPagado, $ImpSaldoInsoluto, $MetodoDePagoDR);
                }
            } else {
                guardar_impuestos_globales($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cotizacion_id, $impuestoTipo, $impuestoImporte, $impuestoImpuesto, $impuestoTasaOCuota, $impuestoTipoFactor);
                //echo "<br>2".$detalle_cotizacion_id;
                $detallesImpuestos = $xmlFactura->xpath("//cfdi:Conceptos//cfdi:Concepto//cfdi:Impuestos//cfdi:Traslados//cfdi:Traslado");
                foreach ($detallesImpuestos as $detalleImpuesto) {
                    $impuestoTipo = 'Traslado';
                    $impuestoImpuesto = $detalleImpuesto['Impuesto'];
                    if ($detalleImpuesto['Importe'] == NULL)
                        $impuestoImporte = 0;
                    else
                        $impuestoImporte = $detalleImpuesto['Importe'];
                    $impuestoTasaOCuota = $detalleImpuesto['TasaOCuota'];
                    $impuestoTipoFactor = $detalleImpuesto['TipoFactor'];

                    //echo "<br>3".$detalle_cotizacion_id;
                    guardar_impuestos_detalle($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $detalle_cotizacion_id, $impuestoTipo, $impuestoImporte, $impuestoImpuesto, $impuestoTasaOCuota, $impuestoTipoFactor);
                }
            }                
        }
        cambiar_estatus_procesado_fecha($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $FechaInicial, $FechaFinal);
        $SentenciaSQLServer = null;
        $ConexionSQLServer = null;

        echo "</ol>";

        # El día de hoy
        $fechaActual = date('d-m-Y');

        $dia = substr($fechaActual, 0, 2);
        $mes = substr($fechaActual, 3, 2);
        $anio = substr($fechaActual, 6, 4);

        //Código para guardar log de Facturas
        $file = fopen("./reportes/log40_" . $dia . $mes . $anio . ".log", "w");
        fwrite($file, "FACTURAS" . PHP_EOL);
        fwrite($file, "Total Facturas: " . $total . PHP_EOL);
        fwrite($file, "Insertadas: " . $insert . PHP_EOL);
        fclose($file);
    } catch (Exception $e) {
        echo "<br>Error al leer archivo : " . "archivo";
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_facturas_receptor($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $clave_receptor, $rfc, $nombre, $pais, $codigo_postal, $correo_electronico, $portal_id, $FechaFactura)
{
    try {
        //Receptor - Catalogo de clientes de facturación
        $ConsultaSQL = "Insert Into receptor (clave_receptor, rfc, nombre, pais, codigopostal, contacto, correoelectronico, portal_id)
        Values (?, ?, ?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($clave_receptor, $rfc, $nombre, $pais, $codigo_postal, $nombre, $correo_electronico, $portal_id));

        if ($SentenciaSQL) {
            echo "<li> RFC " . $rfc . " del " . substr($FechaFactura, 0, 10) . " guardado" . "</li>";
            $receptor_id = $ConexionSQL->lastInsertId();
        } else {
            echo "<li>Error al agregar RFC " . $rfc . "</b></li>";
        }

        $SentenciaSQL = null;
        $ConexionSQL = null;

        return $receptor_id;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $rfc;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_facturas_cotizacion($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $receptor_id, $clave_cotizacion, $serie, $folio, $fechaDocumento, $formaPago, $condicionesPago, $subtotal, $iva, $descuento, $tipoCambio, $moneda, $importeTotal, $importeTotalLetra, $tipoComprobante, $metodoPago, $lugarExpedicion, $regimenFiscal, $portal_id, $tipoCFDI_id, $CIA, $pais, $usoCFDI, $tipoDocumento)
{
    try {
        //Cotización - Encabezado de facturas
        $ConsultaSQL = "Insert Into cotizacion (receptor_id, clave_cotizacion, serie, folio, fecha, formadepago, condicionesdepago, subtotal, iva, descuento, tipocambio, moneda, total, totalenletras, tipodecomprobante, metododepago, lugarexpedicion, regimenfiscal, portal_id, tipocfdi_id, t_imp_trasladados, t_imp_retenidos, CIA, EPAIS, ECODIGO_POSTAL, usoCfdi, tipoDocumento)
        Values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($receptor_id, $clave_cotizacion, $serie, $folio, $fechaDocumento, $formaPago, $condicionesPago, $subtotal, $iva, $descuento, $tipoCambio, $moneda, $importeTotal, $importeTotalLetra, $tipoComprobante, $metodoPago, $lugarExpedicion, $regimenFiscal, $portal_id, $tipoCFDI_id, $iva, 0, $CIA, $pais, $lugarExpedicion, $usoCFDI, $tipoDocumento));

        if ($SentenciaSQL) {
            $cotizacion_id = $ConexionSQL->lastInsertId();
        } else {
            echo "<li>Error al agregar Receptor " . $receptor_id . "</b></li>";
        }

        $SentenciaSQL = null;
        $ConexionSQL = null;

        return $cotizacion_id;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $receptor_id;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_facturas_cot_registro($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cotizacion_id, $version, $fechaDocumento, $noCertificado, $sello, $certificado, $uuid, $noCertificadoSAT, $selloSAT, $fechaTimbrado)
{
    try {
        //CotRegistro - Resultados del Timbre
        $ConsultaSQL = "Insert Into cotregistro (cotizacion_id, version, fecha, nocertificado, sello, certificado, uuid, nocertificadosat, sellosat, fechatimbrado, estatus, enviado, cancelado, in_process)
        Values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($cotizacion_id, $version, $fechaDocumento, $noCertificado, $sello, $certificado, $uuid, $noCertificadoSAT, $selloSAT, $fechaTimbrado, 1, 'S', 'N', 2));

        if ($SentenciaSQL) {
            // echo "<li><b> Cotización " . $cotizacion_id . "</b> guardado" . "</li>";
        } else {
            echo "<li>Error al agregar " . $cotizacion_id . "</b></li>";
        }

        $SentenciaSQL = null;
        $ConexionSQL = null;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $cotizacion_id;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_facturas_detalle_cotizacion($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cotizacion_id, $cantidad, $unidad, $descripcion, $valorUnitario, $importe, $claveUM, $claveProdServ)
{
    try {
        //DetalleCotizacion - Detalle de las facturas
        $ConsultaSQL = "Insert Into detallecotizacion (cotizacion_id, cantidad, unidad, descripcion, valorunitario, importe, clave_UM, clave_prod_serv, posicion, pct_decuento)
        Values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($cotizacion_id, $cantidad, $unidad, $descripcion, $valorUnitario, $importe, $claveUM, $claveProdServ, 1, 0));

        if ($SentenciaSQL) {
            $detalle_cotizacion_id = $ConexionSQL->lastInsertId();
        } else {
            echo "<li>Error al agregar Det_Cotización " . $cotizacion_id . "</b></li>";
        }

        $SentenciaSQL = null;
        $ConexionSQL = null;
        return $detalle_cotizacion_id;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $cotizacion_id;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_cot_pagos($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cotizacion_id, $fechaPago, $monedaPago, $montoPago, $formaDePagoPago)
{
    try {
        //cotpagos - Encabezado de complemento de pagos
        $ConsultaSQL = "Insert Into cotpagos (cotizacion_id, fecha_pago, moneda, monto, forma_pago, num_operacion, rfc_cta_ord, cta_ord, rfc_cta_ben, cta_ben)
        Values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($cotizacion_id, $fechaPago, $monedaPago, $montoPago, $formaDePagoPago, "", "", "", "", ""));

        if ($SentenciaSQL) {
            // echo "<li><b> Receptor " . $receptor_id . "</b> guardado" . "</li>";
        } else {
            echo "<li>Error al agregar Complemento " . $cotizacion_id . "</b></li>";
        }

        $cot_pago_id = $ConexionSQL->lastInsertId();
        $SentenciaSQL = null;
        $ConexionSQL = null;

        return $cot_pago_id;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $cotizacion_id;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_cot_documentos($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cot_pago_id, $idDocumento, $serie, $folio, $monedaDR, $numParcialidad, $impSaldoAnt, $ImpPagado, $ImpSaldoInsoluto, $MetodoDePagoDR)
{
    try {
        //cotdocumentos - Detalle complementos de pago
        $ConsultaSQL = "Insert Into cotdocumentos (cotpago_id, id_doc, serie, folio, moneda, parcialidad, saldo_ant, imp_pagado, saldo_ins, metodo_pago)
        Values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($cot_pago_id, $idDocumento, $serie, $folio, $monedaDR, $numParcialidad, $impSaldoAnt, $ImpPagado, $ImpSaldoInsoluto, $MetodoDePagoDR));

        if ($SentenciaSQL) {
            // echo "<li><b> Receptor " . $receptor_id . "</b> guardado" . "</li>";
        } else {
            echo "<li>Error al agregar Detalle Complemento " . $cot_pago_id . "</b></li>";
        }

        $SentenciaSQL = null;
        $ConexionSQL = null;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $cot_pago_id;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_impuestos_globales($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $cotizacion_id, $impuestoTipo, $impuestoImporte, $impuestoImpuesto, $impuestoTasaOCuota, $impuestoTipoFactor)
{
    try {
        //impuestosglobales - Encabezado de Impuestos
        $ConsultaSQL = "Insert Into impuestosglobales (cotizacion_id, tipoImpuesto, importe, impuesto, tasaCuota, tipoFactor)
        Values (?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($cotizacion_id, $impuestoTipo, $impuestoImporte, $impuestoImpuesto, $impuestoTasaOCuota, $impuestoTipoFactor));

        if ($SentenciaSQL) {
            // echo "<li><b> Receptor " . $receptor_id . "</b> guardado" . "</li>";
        } else {
            echo "<li>Error al agregar Impuesto " . $cotizacion_id . "</b></li>";
        }

        $SentenciaSQL = null;
        $ConexionSQL = null;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $cotizacion_id;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function guardar_impuestos_detalle($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $detalle_cotizacion_id, $impuestoTipo, $impuestoImporte, $impuestoImpuesto, $impuestoTasaOCuota, $impuestoTipoFactor)
{
    try {
        //impuestos_detalle - Detalle de impuestos
        $ConsultaSQL = "Insert Into impuestos_detalle (detallecotizacion_id, tipoImpuesto, importe, impuesto, tasaCuota, tipoFactor)
        Values (?, ?, ?, ?, ?, ?)";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($detalle_cotizacion_id, $impuestoTipo, $impuestoImporte, $impuestoImpuesto, $impuestoTasaOCuota, $impuestoTipoFactor));

        if ($SentenciaSQL) {
            // echo "<li><b> Receptor " . $receptor_id . "</b> guardado" . "</li>";
        } else {
            echo "<li>Error al agregar Detalle Impuesto " . $detalle_cotizacion_id . "</b></li>";
        }

        $SentenciaSQL = null;
        $ConexionSQL = null;
    } catch (Exception $e) {
        echo "<br>Error al guardar archivo : " . $detalle_cotizacion_id;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function cambiar_estatus_procesado($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $clave_cotizacion, $rfc)
{
    try {
        //Estatus Procesado
        $ConsultaSQL = "Update xmls Set procesado = 1 Where voucher = ? And rfc = ?";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($clave_cotizacion, $rfc));

        if ($SentenciaSQL) {
            echo "<li><b> RFC " . $rfc . " del Voucher " . $clave_cotizacion . "</b> guardado" . "</li>";
        } else {
            echo "<li>Error al actualizar Voucher " . $clave_cotizacion . "</b></li>";
        }

        $SentenciaSQL = null;
        $ConexionSQL = null;
    } catch (Exception $e) {
        echo "<br>Error al actualizar Voucher : " . $clave_cotizacion;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function cambiar_estatus_procesado_fecha($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL, $FechaInicial, $FechaFinal)
{
    try {
        //Estatus Procesado
        $ConsultaSQL = "Update xmls Set procesado = 1 Where fecha Between ? And ? And procesado = 0";

        $ConexionSQL = conectarSQLServer($ServidorSQL, $BaseDatosSQL, $UsuarioSQL, $PassSQL);
        $SentenciaSQL = $ConexionSQL->prepare($ConsultaSQL);
        $SentenciaSQL->execute(array($FechaInicial, $FechaFinal));

        // if ($SentenciaSQL) {
        //     echo "<li><b> RFC " . $rfc . " del Voucher " . $clave_cotizacion . "</b> guardado" . "</li>";
        // } else {
        //     echo "<li>Error al actualizar Voucher " . $clave_cotizacion . "</b></li>";
        // }

        $SentenciaSQL = null;
        $ConexionSQL = null;
    } catch (Exception $e) {
        // echo "<br>Error al actualizar Voucher : " . $clave_cotizacion;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function FUN_IMPORTE_CON_LETRA($Total, $TipoMoneda, $connectionPDO)
{
    try {
        $TotalDecimal = FormatDecimal($Total);
        $Moneda = '';
        switch ($TipoMoneda) {
            case 'MXN':
                $Moneda = 'PESOS MEXICANOS';
                break;
            case 'USD':
                $Moneda = 'DOLARES';
                break;
            default:
                $Moneda = 'PESOS MEXICANOS';
                break;
        }

        $sql = "Select [dbo].[FUN_IMPORTE_CON_LETRA] (?, ?)";
        $sentencia = $connectionPDO->prepare($sql);
        $sentencia->execute(array($TotalDecimal, $Moneda));
        $resultado = $sentencia->fetch();
        if ($resultado) {
            $Letra = $resultado[0];
            return $Letra;
        }
    } catch (Exception $ex) {
        echo $ex->getMessage();
    }
}

function FormatDecimal($valor)
{
    $decimal = str_replace(',', '', $valor);
    $decimal = str_replace('$', '', $decimal);
    $decimal = trim($decimal);

    return (float)$decimal;
}
?>