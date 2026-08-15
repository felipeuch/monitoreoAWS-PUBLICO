<?php
include("conexion.php");
include("sns_alerta.php");
include("notificaciones_funciones.php");

$prometheus = "http://localhost:9090";

$UMBRAL_CPU = 85;
$UMBRAL_RAM = 85;
$UMBRAL_DISCO = 80;

function consultarPrometheusAlerta($query, $prometheus) {
    $url = $prometheus . "/api/v1/query?query=" . urlencode($query);
    $respuesta = @file_get_contents($url);

    if ($respuesta === false) {
        return null;
    }

    $json = json_decode($respuesta, true);

    if (!isset($json["data"]["result"][0]["value"][1])) {
        return null;
    }

    return round((float)$json["data"]["result"][0]["value"][1], 2);
}

function registrarAlertaMetrica($conn, $equipo_id, $equipo_nombre, $ip, $tipo, $valor, $umbral, $nivel, $mensaje) {
    $stmt = $conn->prepare("
        INSERT INTO alertas_metricas
        (equipo_id, equipo_nombre, ip, tipo_alerta, valor, umbral, nivel, mensaje)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Error prepare alerta: " . $conn->error);
        return false;
    }

    $stmt->bind_param(
        "isssddss",
        $equipo_id,
        $equipo_nombre,
        $ip,
        $tipo,
        $valor,
        $umbral,
        $nivel,
        $mensaje
    );

    return $stmt->execute();
}

function existeAlertaReciente($conn, $equipo_id, $tipo, $minutos = 10) {
    $stmt = $conn->prepare("
        SELECT id
        FROM alertas_metricas
        WHERE equipo_id = ?
        AND tipo_alerta = ?
        AND fecha_alerta >= NOW() - INTERVAL ? MINUTE
        LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("isi", $equipo_id, $tipo, $minutos);
    $stmt->execute();
    $resultado = $stmt->get_result();

    return $resultado && $resultado->num_rows > 0;
}

$sql = "
    SELECT id, nombre, ip, instancia_metricas
    FROM equipos
    WHERE instancia_metricas IS NOT NULL
    AND instancia_metricas <> ''
";

$res = $conn->query($sql);

if (!$res || $res->num_rows == 0) {
    echo "No hay equipos con métricas configuradas.\n";
    exit;
}

while ($equipo = $res->fetch_assoc()) {
    $equipo_id = (int)$equipo["id"];
    $nombre = $equipo["nombre"];
    $ip = $equipo["ip"];
    $instancia = $equipo["instancia_metricas"];

    $cpu_query = '100 - (avg by(instance) (rate(node_cpu_seconds_total{mode="idle",instance="' . $instancia . '"}[1m])) * 100)';

    $ram_query = '(1 - (node_memory_MemAvailable_bytes{instance="' . $instancia . '"} / node_memory_MemTotal_bytes{instance="' . $instancia . '"})) * 100';

    $disco_query = '(1 - (node_filesystem_avail_bytes{instance="' . $instancia . '",mountpoint="/",fstype!="rootfs"} / node_filesystem_size_bytes{instance="' . $instancia . '",mountpoint="/",fstype!="rootfs"})) * 100';

    $cpu = consultarPrometheusAlerta($cpu_query, $prometheus);
    $ram = consultarPrometheusAlerta($ram_query, $prometheus);
    $disco = consultarPrometheusAlerta($disco_query, $prometheus);

    echo "Equipo: $nombre ($ip)\n";
    echo "CPU: " . ($cpu !== null ? $cpu . "%" : "Sin datos") . "\n";
    echo "RAM: " . ($ram !== null ? $ram . "%" : "Sin datos") . "\n";
    echo "Disco: " . ($disco !== null ? $disco . "%" : "Sin datos") . "\n";

    if ($cpu !== null && $cpu >= $UMBRAL_CPU) {
        if (!existeAlertaReciente($conn, $equipo_id, "CPU", 10)) {
            $asunto = "Alerta crítica CPU - $nombre";

            $mensaje = "Se detectó una alerta crítica de CPU en el equipo $nombre.\n\n";
            $mensaje .= "Equipo: $nombre\n";
            $mensaje .= "IP: $ip\n";
            $mensaje .= "Instancia Prometheus: $instancia\n";
            $mensaje .= "CPU actual: $cpu%\n";
            $mensaje .= "Umbral configurado: $UMBRAL_CPU%\n";
            $mensaje .= "Nivel: Crítico\n\n";
            $mensaje .= "Se recomienda revisar los procesos activos o la carga reciente del servidor.";

            registrarAlertaMetrica($conn, $equipo_id, $nombre, $ip, "CPU", $cpu, $UMBRAL_CPU, "Crítico", $mensaje);
            enviarAlertaSNS($asunto, $mensaje);

            registrarNotificacion(
                $conn,
                "METRICA",
                "CPU elevada en $nombre",
                "El equipo $nombre presenta CPU elevada con $cpu%. Umbral configurado: $UMBRAL_CPU%.",
                "Critico",
                "Metricas",
                $equipo_id
            );

            registrarNotificacion(
                $conn,
                "METRICA",
                "RAM elevada en $nombre",
                "El equipo $nombre presenta RAM elevada con $ram%. Umbral configurado: $UMBRAL_RAM%.",
                "Critico",
                "Metricas",
                $equipo_id
            );

            registrarNotificacion(
                $conn,
                "METRICA",
                "Disco elevado en $nombre",
                "El equipo $nombre presenta uso de disco de $disco%. Umbral configurado: $UMBRAL_DISCO%.",
                "Advertencia",
                "Metricas",
                $equipo_id
            );

            echo "Alerta CPU enviada por SNS.\n";
        } else {
            echo "Ya existe una alerta CPU reciente. No se envía duplicado.\n";
        }
    }

    if ($ram !== null && $ram >= $UMBRAL_RAM) {
        if (!existeAlertaReciente($conn, $equipo_id, "RAM", 10)) {
            $asunto = "Alerta crítica RAM - $nombre";

            $mensaje = "Se detectó una alerta crítica de memoria RAM en el equipo $nombre.\n\n";
            $mensaje .= "Equipo: $nombre\n";
            $mensaje .= "IP: $ip\n";
            $mensaje .= "Instancia Prometheus: $instancia\n";
            $mensaje .= "RAM actual: $ram%\n";
            $mensaje .= "Umbral configurado: $UMBRAL_RAM%\n";
            $mensaje .= "Nivel: Crítico\n\n";
            $mensaje .= "Se recomienda revisar consumo de memoria y procesos activos.";

            registrarAlertaMetrica($conn, $equipo_id, $nombre, $ip, "RAM", $ram, $UMBRAL_RAM, "Crítico", $mensaje);
            enviarAlertaSNS($asunto, $mensaje);

            echo "Alerta RAM enviada por SNS.\n";
        } else {
            echo "Ya existe una alerta RAM reciente. No se envía duplicado.\n";
        }
    }

    if ($disco !== null && $disco >= $UMBRAL_DISCO) {
        if (!existeAlertaReciente($conn, $equipo_id, "DISCO", 10)) {
            $asunto = "Advertencia disco - $nombre";

            $mensaje = "Se detectó una advertencia de uso de disco en el equipo $nombre.\n\n";
            $mensaje .= "Equipo: $nombre\n";
            $mensaje .= "IP: $ip\n";
            $mensaje .= "Instancia Prometheus: $instancia\n";
            $mensaje .= "Disco actual: $disco%\n";
            $mensaje .= "Umbral configurado: $UMBRAL_DISCO%\n";
            $mensaje .= "Nivel: Advertencia\n\n";
            $mensaje .= "Se recomienda revisar espacio disponible en el sistema.";

            registrarAlertaMetrica($conn, $equipo_id, $nombre, $ip, "DISCO", $disco, $UMBRAL_DISCO, "Advertencia", $mensaje);
            enviarAlertaSNS($asunto, $mensaje);

            echo "Alerta DISCO enviada por SNS.\n";
        } else {
            echo "Ya existe una alerta DISCO reciente. No se envía duplicado.\n";
        }
    }

    echo "-----------------------------\n";
}
?>
