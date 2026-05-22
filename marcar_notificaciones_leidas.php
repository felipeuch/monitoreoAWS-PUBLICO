<?php
include("verificar_sesion.php");
include("conexion.php");

header("Content-Type: application/json; charset=utf-8");

$conn->query("UPDATE notificaciones SET estado = 'Leida' WHERE estado = 'No leida'");

echo json_encode(["ok" => true]);
?>