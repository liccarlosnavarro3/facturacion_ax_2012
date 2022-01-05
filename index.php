<?php

/**
 * Envío de Facturas AX
 * 
 * Menú de opciones para leer las facturas AX
 * 
 * @author Carlos Hugo
 * 
 * @copyright (c) 2021, ABC Leasing
 * 
 * @version 1.0
 */
//Opciones de inicio
if (isset($_POST['options'])) {
    switch ($_POST['options']) {
        case 'optCarga':
            header("Location: cargar_facturas.php");
            break;
        case 'optCorreos':
            header("Location: actualizar_facturas.php");
            break;
        case 'optExterno':
            header("Location: webservices_facturas.php");
            break;
        case 'optInterno':
            header("Location: webservices_facturas.php");
            break;
        default:
            'Opción no válida';
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Actualizar Facturas AX | ABC Leasing</title>
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div id="header">
        <img src="img/abc.png" alt="ABC Leasing" class="header">
    </div>
    <h1>Opciones de Ejecución</h1>

    <form method="POST">
        <label>
            <input type="radio" value="optCarga" name="options" checked="checked">
            Buscar Nuevas Facturas
        </label>
        <br>
        <label>
            <input type="radio" value="optCorreos" name="options">
            Cargar Correo y Número de Cliente
        </label>
        <br>
        <label>
            <input type="radio" value="optExterno" name="options">
            Ejecutar Web Services
        </label>
        <br>
        <!-- <label>
            <input type="radio" value="optInterno" name="options">
            Ejecutar Web Service Interno
        </label>
        <br> -->
        <button type="submit">Cargar Facturas</button>
    </form>
</body>

</html>