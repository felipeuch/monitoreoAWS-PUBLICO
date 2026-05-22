<?php
$host = "localhost";
$usuario = "monitor_user";
$contrasena = "TuClaveSegura123!";
$bd = "monitoreo_web";

$conn = new mysqli($host, $usuario, $contrasena, $bd);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
