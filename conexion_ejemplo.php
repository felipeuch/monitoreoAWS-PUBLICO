<?php
date_default_timezone_set('America/Santiago');

$host = "localhost";
$usuario = "USUARIO_BASE_DE_DATOS";
$contrasena = "CONTRASENA_BASE_DE_DATOS";
$bd = "NOMBRE_BASE_DE_DATOS";

$conn = new mysqli($host, $usuario, $contrasena, $bd);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>