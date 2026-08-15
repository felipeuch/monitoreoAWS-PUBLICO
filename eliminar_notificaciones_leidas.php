<?php
include("verificar_sesion.php");
include("conexion.php");

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["ok" => false, "error" => "Método no permitido"]);
    exit;
}

$sql = "DELETE FROM notificaciones WHERE estado = 'Leida'";

if ($conn->query($sql)) {
    echo json_encode(["ok" => true]);
} else {
    echo json_encode(["ok" => false, "error" => "No se pudieron eliminar las notificaciones"]);
}
?>
