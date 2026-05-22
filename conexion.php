<?php
date_default_timezone_set('America/Santiago');

$host = "monitoreo-web-db.cviu6wa0qj18.us-east-2.rds.amazonaws.com";
$usuario = "admin";
$contrasena = "Qazxc5467.";
$bd = "monitoreo_web";
$puerto = 3306;
$certificado = "/var/www/ssl/global-bundle.pem";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, $certificado, NULL, NULL);

if (!mysqli_real_connect($conn, $host, $usuario, $contrasena, $bd, $puerto, NULL, MYSQLI_CLIENT_SSL)) {
    die("Error de conexión: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");
?>
