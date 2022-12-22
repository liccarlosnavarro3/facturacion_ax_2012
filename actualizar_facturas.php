<?php

/**
 * Actualizar Facturas AX
 * 
 * Actualiza el correo y número de cliente de la tabla SQL Server
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
// sleep(3000);
//Librerías
require 'conexion.php';

//Conexión a SQL Server y Oracle
// $CadenaConexionOracle = $ServidorOracleR . ":" . $PuertoOracleR . "/" . $BaseDatosOracleR;
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
    <h1>Actualizar Facturas</h1>
    <div>
        <h2>Inicia Actualización de correos y número de Cliente</h2>
        <?php
        // actualiza_correo_numeroCliente($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $UsuarioOracleR, $PassOracleR, $CadenaConexionOracle);
        actualiza_correo_numeroCliente($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);
        ?>
        <h3>Termina actualización de correos y número de Cliente</h3>
    </div>
    <a href="index.php" class="link">Regresar al inicio</a>
</body>

</html>

<?php
function actualiza_correo_numeroCliente($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX)
{
    try {
        //$ConsultaSQLServer = "Select Distinct rfc From Facturas_Clientes Where Isnull(email, '') = '' Or Isnull(cuentaCliente, '') = '' Order by rfc";
        $ConsultaSQLServer = 
            "Select Distinct rfc"
            . " From Facturas_Clientes"
            . " Where (Isnull(email, '') = '' Or Isnull(cuentaCliente, '') = '')"
            //. " And Substring(fechaDocumento, 9, 2) = '30'"
            //. " And Substring(fechaDocumento, 6, 2) = '06'"
            //. " And Substring(fechaDocumento, 1, 4) = '2022'"
            . " Order by rfc";

        $ConexionSQLServer = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);

        $SentenciaSQLServer = $ConexionSQLServer->prepare($ConsultaSQLServer);
        $SentenciaSQLServer->execute();

        echo "<ol>";
        while ($ResultadoSQLServer = $SentenciaSQLServer->fetch()) {
            $rfc = $ResultadoSQLServer['rfc'];

            // $result = buscar_email_imx($UsuarioOracleR, $PassOracleR, $CadenaConexionOracle, $rfc);
            $result = buscar_email_sql($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $rfc);
            $email = $result[0];
            $cuentaCliente = $result[1];
            if ($email != '' or $cuentaCliente != '') {
                actualizar_email($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $rfc, $email, $cuentaCliente);
            } else
                echo "<br><p>" . $rfc . " No se encuentra en iMX</p>";
        }
        echo "</ol>";
        $SentenciaSQLServer = null;
        $ConexionSQLServer = null;
    } catch (Exception $e) {
        echo "<br>Error al actualizar correo y/o número del cliente : " . $rfc;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function buscar_email_imx($UsuarioOracleR, $PassOracleR, $CadenaConexionOracle, $rfc)
{
    try {
        $ConexionOracle = conectar_Oracle($UsuarioOracleR, $PassOracleR, $CadenaConexionOracle);

        $ConsultaOracle = "Select g.TVA As RFC, NVL(NUMTEL, '') As EMAIL, NVL(G.refindividu, '') CUENTA_CLIENTE From g_telephone t, g_individu g where t.refindividu = G.refindividu and TYPETEL = 'EMAIL' and g. tva = '" . $rfc . "' and numtel not like '%@ABCLEASING%'";

        $ConexionOracle = conectar_Oracle($UsuarioOracleR, $PassOracleR, $CadenaConexionOracle);
        $SentenciaOracle = oci_parse($ConexionOracle, $ConsultaOracle);
        oci_execute($SentenciaOracle);
        $email = '';
        $cuentaCliente = substr($rfc, 0, 6);

        while (oci_fetch($SentenciaOracle)) {
            $email = oci_result($SentenciaOracle, 'EMAIL');
            $cuentaCliente = oci_result($SentenciaOracle, 'CUENTA_CLIENTE');
        }

        oci_free_statement($SentenciaOracle);
        oci_close($ConexionOracle);

        return array($email, $cuentaCliente);
    } catch (Exception $e) {
        echo "<br>Error al buscar correo y/o número del cliente " . $rfc;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}

function buscar_email_sql($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $rfc)
{
    try {
        $ConsultaSQLFind = "Select Email, Cuenta_Cliente From Facturas_Clientes_Cuentas Where rfc = ?";

        $ConexionSQLFind = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);
        $SentenciaSQLFind = $ConexionSQLFind->prepare($ConsultaSQLFind);
        $SentenciaSQLFind->execute(array($rfc));

        $email = '';
        $cuentaCliente = substr($rfc, 0, 6);

        while ($ResultadoSQLServer = $SentenciaSQLFind->fetch()) {
            $email = $ResultadoSQLServer['Email'];
            $cuentaCliente = $ResultadoSQLServer['Cuenta_Cliente'];
        }

        $SentenciaSQLFind = null;
        $ConexionSQLFind = null;

        return array($email, $cuentaCliente);
    } catch (Exception $e) {
        echo "<br>Error al buscar correo y/o número del cliente " . $rfc;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}
function actualizar_email($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX, $rfc, $email, $cuentaCliente)
{
    try {
        
        if (trim($email) == '') {
            //echo "<br>Email: vacio <br>";
            $email = $cuentaCliente;
        }
        $ConsultaSQLUpdate = "Update Facturas_Clientes Set email = ?, cuentaCliente = ? Where rfc = ?";

        $ConexionSQLUpdate = conectarSQLServer($ServidorSQLAX, $BaseDatosSQLAX, $UsuarioSQLAX, $PassSQLAX);
        $SentenciaSQLUpdate = $ConexionSQLUpdate->prepare($ConsultaSQLUpdate);
        $SentenciaSQLUpdate->execute(array($email, $cuentaCliente, $rfc));

        if ($SentenciaSQLUpdate) {
            echo "<li><b>" . $rfc . "</b> Actualizado con: " . $email . " - " . $cuentaCliente . "</li>";
        } else {
            echo "<li>Error al agregar " . $email . " y " . $cuentaCliente . " a <b>" . $rfc . "</b></li>";
        }
        $SentenciaSQLUpdate = null;
        $ConexionSQLUpdate = null;
    } catch (Exception $e) {
        echo "<br>Error al agregar " . $email . " y/o " . $cuentaCliente . " a " . $rfc;
        echo "<br>ERROR: " . $e->getMessage() . "<br>";
    }
}
?>