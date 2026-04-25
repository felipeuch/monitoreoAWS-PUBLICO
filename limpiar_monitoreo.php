<?php
include("verificar_sesion.php");
include("conexion.php");

$sql = "DELETE FROM monitoreo";

if ($conn->query($sql) === TRUE) {
    header("Location: ver_monitoreo.php?mensaje=historial_limpiado");
    exit();
} else {
    echo "Error al limpiar el historial: " . $conn->error;
}
?>
