<?php
ob_start();

include("verificar_sesion.php");
include("conexion.php");

$filtro_usuario = $_GET["usuario"] ?? "";
$filtro_modulo = $_GET["modulo"] ?? "";
$filtro_fecha_desde = $_GET["fecha_desde"] ?? "";
$filtro_fecha_hasta = $_GET["fecha_hasta"] ?? "";

$where = [];

if (!empty($filtro_usuario)) {
    $usuario_seguro = $conn->real_escape_string($filtro_usuario);
    $where[] = "usuario_nombre LIKE '%$usuario_seguro%'";
}

if (!empty($filtro_modulo)) {
    $modulo_seguro = $conn->real_escape_string($filtro_modulo);
    $where[] = "modulo LIKE '%$modulo_seguro%'";
}

if (!empty($filtro_fecha_desde)) {
    $fecha_desde_segura = $conn->real_escape_string($filtro_fecha_desde);
    $where[] = "fecha_evento >= '$fecha_desde_segura 00:00:00'";
}

if (!empty($filtro_fecha_hasta)) {
    $fecha_hasta_segura = $conn->real_escape_string($filtro_fecha_hasta);
    $where[] = "fecha_evento <= '$fecha_hasta_segura 23:59:59'";
}

$sql = "SELECT * FROM bitacora_sistema";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY fecha_evento DESC, id DESC";

$resultado = $conn->query($sql);

$fecha_archivo = date("Y-m-d_H-i-s");
$nombre_archivo = "bitacora_sistema_" . $fecha_archivo . ".csv";

ob_end_clean();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$salida = fopen('php://output', 'w');

fprintf($salida, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($salida, [
    'Fecha y hora',
    'Usuario',
    'Modulo',
    'Accion',
    'Descripcion',
    'IP origen'
], ';');

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        fputcsv($salida, [
            $fila["fecha_evento"],
            $fila["usuario_nombre"],
            $fila["modulo"],
            $fila["accion"],
            $fila["descripcion"],
            $fila["ip_origen"]
        ], ';');
    }
}

fclose($salida);
exit;
