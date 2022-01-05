<?php

/**
 * Realiza la conexión
 * 
 * Se llevan a cabo los siguientes pasos:
 * <ul>
 * 	<li>Pone el encabezado del tipo de codificación UTF-8.</li>
 * 	<li>Define las rutas donde se guardan los archivos de texto de las facturas.</li>
 * 	<li>Crea la conexión a la base de datos en SQL Server utilizando una ConexiónPDO.</li>
 * </ul>
 * 
 * @author Carlos Hugo
 * 
 * @copyright (c) 2019, ABC Leasing
 * 
 * @version 1.0
 */
//Datos de acceso
require_once('accesos.php');

/*//Deshabilitar para que muestre los errores / Si está en (0) Esconde todos los errores -> Se utiliza así en producción
//error_reporting(0);
error_reporting(E_ALL);
ini_set('display_errors', '1');*/

//Para generar la documentación a partir de NetBeans 8
//apigen generate --source [directorio a documentar] --destination [directorio de destino]
//header("Content-Type: text/html;charset=utf-8");

//Datos de conexión
$serverName = $ServidorSQLAX;
$dataBase = $BaseDatosSQLAX;
$uid = $UsuarioSQLAX;
$pwd = base64_decode($PassSQLAX);

try {
    $connectionPDO = new PDO("sqlsrv:Server=$serverName; Database=$dataBase", $uid, $pwd);
    $connectionPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Error al abrir la base de datos: " . $e->getMessage();
}

/**
 * Conexión de SQL Oracle
 * 
 * Hace una conexión a la base de datos Oracle
 * 
 * @author Carlos Hugo
 * 
 * @copyright (c) 2020, ABC Leasing
 * 
 * @param string $UsuarioOracle El usuario de conexión a SQL Oracle
 * @param string $PassOracle La contraseña de conexión a SQL Oracle
 * @param string $CadenaConexionOracle La cadena de conexión a SQL Oracle
 * 
 * @version 1.0
 */
function conectar_Oracle($UsuarioOracle, $PassOracle, $CadenaConexionOracle)
{
    $PassOracleDecode = base64_decode($PassOracle);
    // echo '<br>'.$PassOracleDecode.'<br>';
    $ConexionOracle = null;
    try {
        $ConexionOracle = oci_connect(
            $UsuarioOracle,
            $PassOracleDecode,
            $CadenaConexionOracle
        ) or die("Error al conectar: " . oci_error());
        if ($ConexionOracle == false)
            throw new Exception("Error Oracle " . oci_error());
        // else
        //     echo '<br>Conexión Correcta';

    } catch (Exception $e) {
        throw $e;
    }
    return $ConexionOracle;
}

/**
 * conectarSQLServer
 * 
 * Hace una conexión a la base de datos Boston de SQL Server
 * 
 * @author Carlos Hugo
 * 
 * @copyright (c) 2020, ABC Leasing
 * 
 * @param string $serverName Nombre del servidor Boston
 * @param string $dataBase Nombre de la base de datos abc_autos_leasing
 * @param string $uid El usuario de conexión a SQL Server
 * @param string $pwd La contraseña de conexión a SQL Server
 * 
 * @version 1.0
 */
function conectarSQLServer($serverName, $dataBase, $uid, $pwd)
{
    $pwdDecode = base64_decode($pwd);
    // echo '<br>' . $pwdDecode . '<br>';
    $ConexionBoston = null;
    try {
        $ConexionBoston = new PDO("sqlsrv:Server=$serverName; Database=$dataBase", $uid, $pwdDecode);
        $ConexionBoston->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //echo '<br>Correcto<br>';
    } catch (Exception $e) {
        echo "Error al abrir la base de datos: " . $e->getMessage();
    }
    return $ConexionBoston;
}
