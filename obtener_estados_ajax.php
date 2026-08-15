<?php
include("verificar_sesion.php");
include("conexion.php");

header('Content-Type: application/json; charset=utf-8');

$sql = "
    SELECT e.id,
           e.nombre,
           e.ip,
           m.estado AS ultimo_estado,
           m.ultimo_chequeo AS ultimo_chequeo
    FROM equipos e
    LEFT JOIN monitoreo m
        ON m.id = (
            SELECT m2.id
            FROM monitoreo m2
            WHERE m2.equipo_id = e.id
            ORDER BY m2.ultimo_chequeo DESC, m2.id DESC
            LIMIT 1
        )
    ORDER BY e.id DESC
";

$resultado = $conn->query($sql);

$equipos = [];
$totalActivos = 0;
$totalInactivos = 0;
$totalSinMonitoreo = 0;

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $estado = $fila["ultimo_estado"];

        if ($estado === "Activo") {
            $textoEstado = "Activo";
            $claseEstado = "ok";
            $totalActivos++;
        } elseif ($estado === "Inactivo") {
            $textoEstado = "Inactivo";
            $claseEstado = "down";
            $totalInactivos++;
        } else {
            $textoEstado = "Sin monitoreo";
            $claseEstado = "warn";
            $totalSinMonitoreo++;
        }

        $equipos[] = [
            "id" => (int)$fila["id"],
            "nombre" => $fila["nombre"],
            "ip" => $fila["ip"],
            "estado" => $textoEstado,
            "clase_estado" => $claseEstado,
            "ultimo_chequeo" => $fila["ultimo_chequeo"] ?: "Sin registros"
        ];
    }
}

echo json_encode([
    "ok" => true,
    "equipos" => $equipos,
    "resumen" => [
        "activos" => $totalActivos,
        "inactivos" => $totalInactivos,
        "sin_monitoreo" => $totalSinMonitoreo
    ]
]);
