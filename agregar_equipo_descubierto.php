<?php
include("verificar_sesion.php");

if (!isset($_GET["ip"])) {
    header("Location: descubrir_equipos.php");
    exit();
}

$ip = $_GET["ip"];
header("Location: agregar_equipo.php?ip=" . urlencode($ip));
exit();
?>
