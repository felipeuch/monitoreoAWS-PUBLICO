<?php
include("verificar_sesion.php");
include("conexion.php");

header("Content-Type: application/json; charset=utf-8");

$sql = "
    SELECT id, tipo, titulo, mensaje, nivel, modulo, estado, fecha_creacion
    FROM notificaciones
    ORDER BY fecha_creacion DESC
    LIMIT 10
";

$res = $conn->query($sql);

$notificaciones = [];

if ($res && $res->num_rows > 0) {
    while ($fila = $res->fetch_assoc()) {
        $notificaciones[] = $fila;
    }
}

$sql_count = "
    SELECT COUNT(*) AS total
    FROM notificaciones
    WHERE estado = 'No leida'
";

$res_count = $conn->query($sql_count);
$total_no_leidas = 0;

if ($res_count) {
    $total_no_leidas = (int)$res_count->fetch_assoc()["total"];
}

echo json_encode([
    "total_no_leidas" => $total_no_leidas,
    "notificaciones" => $notificaciones
]);
?>