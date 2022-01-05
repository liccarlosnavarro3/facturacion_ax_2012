<?php
set_time_limit(0);
$carpetaFacturas = '\\\\SRVMDAOS\CFDI Prod\Facturas';
$carpetaPagos = '\\\\SRVMDAOS\CFDI Prod\Pagos';
$IterarArchivos = new FilesystemIterator($carpetaPagos);

$FechaActual = date('Y-m-d');
$FechaDias = date("Y-m-d", strtotime($FechaActual . "- 1 days"));
$FechaObjeto = new DateTime($FechaDias);

$i = 0;
foreach ($IterarArchivos as $ArchivoIterado) {
    if ($ArchivoIterado->getMTime() >= $FechaObjeto->getTimestamp()) {
        echo $ArchivoIterado->getFilename() .  " getATime: " . $ArchivoIterado->getATime() . " getCTime: " . $ArchivoIterado->getCTime() . " getMTime: " . $ArchivoIterado->getMTime() . '<br>';
        $i = $i + 1;
    }
}

echo '<br>Fecha: ' . $FechaDias;
echo '<br>Total: ' . $i;
