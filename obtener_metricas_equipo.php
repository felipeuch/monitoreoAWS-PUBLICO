<?php
include("verificar_sesion.php");
include("conexion.php");

header("Content-Type: application/json");

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    echo json_encode(["error" => "ID no válido"]);
    exit;
}

$id = (int) $_GET["id"];

$sql = "SELECT * FROM equipos WHERE id='$id' LIMIT 1";
$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows == 0) {
    echo json_encode(["error" => "Equipo no encontrado"]);
    exit;
}

$equipo = $resultado->fetch_assoc();
$instancia = $equipo["instancia_metricas"];

if (empty($instancia)) {
    echo json_encode(["error" => "Equipo sin instancia de métricas"]);
    exit;
}

$prometheus = "http://localhost:9090";

function consultarPrometheus($query, $prometheus) {
    $url = $prometheus . "/api/v1/query?query=" . urlencode($query);
    $respuesta = file_get_contents($url);

    if ($respuesta === false) {
        return null;
    }

    $json = json_decode($respuesta, true);

    if (!isset($json["data"]["result"][0]["value"][1])) {
        return null;
    }

    return round((float)$json["data"]["result"][0]["value"][1], 2);
}

$cpu_query = '100 - (avg by(instance) (rate(node_cpu_seconds_total{mode="idle",instance="' . $instancia . '"}[5m])) * 100)';

$ram_query = '(1 - (node_memory_MemAvailable_bytes{instance="' . $instancia . '"} / node_memory_MemTotal_bytes{instance="' . $instancia . '"})) * 100';

$disco_query = '(1 - (node_filesystem_avail_bytes{instance="' . $instancia . '",mountpoint="/",fstype!="rootfs"} / node_filesystem_size_bytes{instance="' . $instancia . '",mountpoint="/",fstype!="rootfs"})) * 100';

$cpu = consultarPrometheus($cpu_query, $prometheus);
$ram = consultarPrometheus($ram_query, $prometheus);
$disco = consultarPrometheus($disco_query, $prometheus);

echo json_encode([
    "equipo" => $equipo["nombre"],
    "ip" => $equipo["ip"],
    "instancia_metricas" => $instancia,
    "cpu" => $cpu,
    "ram" => $ram,
    "disco" => $disco
]);
?>
