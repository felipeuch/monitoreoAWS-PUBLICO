<?php
include("verificar_sesion.php");
include("conexion.php");
include("bitacora_funciones.php");

if (!isset($_GET["id"]) || !is_numeric($_GET["id"]) || (int)$_GET["id"] <= 0) {
    die("ID no válido.");
}

$id = (int) $_GET["id"];

$sql_buscar = "SELECT nombre, ip FROM equipos WHERE id='$id' LIMIT 1";
$res_buscar = $conn->query($sql_buscar);

$nombre = "Equipo desconocido";
$ip = "";

if ($res_buscar && $res_buscar->num_rows > 0) {
    $fila = $res_buscar->fetch_assoc();
    $nombre = $fila["nombre"];
    $ip = $fila["ip"];
}

/* borrar primero registros relacionados */
$sql_monitoreo = "DELETE FROM monitoreo WHERE equipo_id='$id'";
$conn->query($sql_monitoreo);

/* luego borrar equipo */
$sql_equipo = "DELETE FROM equipos WHERE id='$id'";

if ($conn->query($sql_equipo) === TRUE) {
    registrarBitacora($conn, "Equipos", "Eliminar equipo", "Se eliminó el equipo " . $nombre . " con IP " . $ip);
    header("Location: listar_equipos.php?eliminado=1");
    exit();
} else {
    die("Error al eliminar el equipo: " . $conn->error);
}
?>
