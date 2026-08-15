<?php
include("verificar_sesion.php");

if (!isset($_GET["ip"])) {
    header("Location: descubrir_equipos.php");
    exit();
}

$ip = trim($_GET["ip"]);
$nombre = isset($_GET["nombre"]) ? trim($_GET["nombre"]) : "";

$url = "agregar_equipo.php?ip=" . urlencode($ip);

if ($nombre !== "" && $nombre !== "No detectado") {
    $url .= "&nombre=" . urlencode($nombre);
}

header("Location: " . $url);
exit();
?>
