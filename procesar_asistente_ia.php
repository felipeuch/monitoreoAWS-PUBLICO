<?php
include("verificar_sesion.php");
include("conexion.php");
include("bedrock_helper.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Método no permitido."]);
    exit;
}

$pregunta = trim($_POST["pregunta"] ?? "");

if ($pregunta === "") {
    echo json_encode(["error" => "Debes ingresar una consulta."]);
    exit;
}

$pregunta_minuscula = mb_strtolower($pregunta, 'UTF-8');

$contexto = "";

$prometheus = "http://localhost:9090";


function construirContextoMetricasEquipo($equipo, $prometheus) {
    $nombre = $equipo["nombre"];
    $ip = $equipo["ip"];
    $instancia = $equipo["instancia_metricas"];

    $cpu_query = '100 - (avg by(instance) (rate(node_cpu_seconds_total{mode="idle",instance="' . $instancia . '"}[5m])) * 100)';

    $ram_query = '(1 - (node_memory_MemAvailable_bytes{instance="' . $instancia . '"} / node_memory_MemTotal_bytes{instance="' . $instancia . '"})) * 100';

    $disco_query = '(1 - (node_filesystem_avail_bytes{instance="' . $instancia . '",mountpoint="/",fstype!="rootfs"} / node_filesystem_size_bytes{instance="' . $instancia . '",mountpoint="/",fstype!="rootfs"})) * 100';

    $cpu = consultarPrometheus($cpu_query, $prometheus);
    $ram = consultarPrometheus($ram_query, $prometheus);
    $disco = consultarPrometheus($disco_query, $prometheus);

    $nivel_cpu = obtenerNivelAlerta($cpu, "cpu");
    $nivel_ram = obtenerNivelAlerta($ram, "ram");
    $nivel_disco = obtenerNivelAlerta($disco, "disco");

    $contexto = "Métricas actuales del equipo consultado:\n";
    $contexto .= "- Equipo: $nombre\n";
    $contexto .= "- IP: $ip\n";
    $contexto .= "- Instancia Prometheus: $instancia\n";
    $contexto .= "- CPU actual: " . ($cpu !== null ? $cpu . "%" : "Sin datos") . " | Nivel: $nivel_cpu\n";
    $contexto .= "- RAM utilizada: " . ($ram !== null ? $ram . "%" : "Sin datos") . " | Nivel: $nivel_ram\n";
    $contexto .= "- Disco utilizado: " . ($disco !== null ? $disco . "%" : "Sin datos") . " | Nivel: $nivel_disco\n";

    $contexto .= "\nCriterios de interpretación:\n";
    $contexto .= "- CPU sobre 85% se considera crítico.\n";
    $contexto .= "- RAM sobre 85% se considera crítico.\n";
    $contexto .= "- Disco sobre 80% se considera advertencia.\n";

    return $contexto;
}




function consultarPrometheus($query, $prometheus) {
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

function obtenerNivelAlerta($valor, $tipo) {
    if ($valor === null) {
        return "Sin datos";
    }

    if ($tipo === "cpu" && $valor >= 85) {
        return "Crítico";
    }

    if ($tipo === "ram" && $valor >= 85) {
        return "Crítico";
    }

    if ($tipo === "disco" && $valor >= 80) {
        return "Advertencia";
    }

    return "Normal";
}



/* RESUMEN RECIENTE */
if (strpos($pregunta_minuscula, "resumen") !== false || strpos($pregunta_minuscula, "actividad") !== false) {
    $eventos = 0;
    $monitoreos = 0;
    $automaticos = 0;
    $manuales = 0;

    $sql1 = "SELECT COUNT(*) AS total FROM bitacora_sistema WHERE fecha_evento >= NOW() - INTERVAL 1 DAY";
    $res1 = $conn->query($sql1);
    if ($res1) {
        $eventos = (int)$res1->fetch_assoc()["total"];
    }

    $sql2 = "SELECT COUNT(*) AS total FROM monitoreo WHERE ultimo_chequeo >= NOW() - INTERVAL 1 DAY";
    $res2 = $conn->query($sql2);
    if ($res2) {
        $monitoreos = (int)$res2->fetch_assoc()["total"];
    }

    $sql3 = "SELECT COUNT(*) AS total FROM ejecuciones_monitoreo WHERE fecha_ejecucion >= NOW() - INTERVAL 1 DAY AND tipo = 'Automático'";
    $res3 = $conn->query($sql3);
    if ($res3) {
        $automaticos = (int)$res3->fetch_assoc()["total"];
    }

    $sql4 = "SELECT COUNT(*) AS total FROM ejecuciones_monitoreo WHERE fecha_ejecucion >= NOW() - INTERVAL 1 DAY AND tipo = 'Manual'";
    $res4 = $conn->query($sql4);
    if ($res4) {
        $manuales = (int)$res4->fetch_assoc()["total"];
    }

    $contexto = "Datos del sistema en las últimas 24 horas:\n";
    $contexto .= "- Eventos en bitácora: $eventos\n";
    $contexto .= "- Registros de monitoreo: $monitoreos\n";
    $contexto .= "- Ejecuciones automáticas: $automaticos\n";
    $contexto .= "- Ejecuciones manuales: $manuales\n";
}

/* EQUIPOS ACTIVOS */
elseif (
    strpos($pregunta_minuscula, "activo") !== false &&
    strpos($pregunta_minuscula, "inactivo") === false
) {
    $equipos_activos = [];

    $sql = "
        SELECT e.nombre, e.ip, m.estado, m.ultimo_chequeo
        FROM equipos e
        INNER JOIN monitoreo m ON m.id = (
            SELECT m2.id
            FROM monitoreo m2
            WHERE m2.equipo_id = e.id
            ORDER BY m2.id DESC
            LIMIT 1
        )
        WHERE m.estado = 'Activo'
        ORDER BY e.nombre ASC
    ";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($fila = $res->fetch_assoc()) {
            $equipos_activos[] = $fila["nombre"] . " (" . $fila["ip"] . "), último chequeo: " . $fila["ultimo_chequeo"];
        }
    }

    $contexto = "Equipos actualmente activos:\n";
    if (count($equipos_activos) > 0) {
        $contexto .= "- " . implode("\n- ", $equipos_activos);
    } else {
        $contexto .= "- No hay equipos activos en este momento.";
    }
}


/* EQUIPOS INACTIVOS */
elseif (strpos($pregunta_minuscula, "inactivo") !== false) {
    $equipos_inactivos = [];

    $sql = "
        SELECT e.nombre, e.ip, m.estado, m.ultimo_chequeo
        FROM equipos e
        INNER JOIN monitoreo m ON m.id = (
            SELECT m2.id
            FROM monitoreo m2
            WHERE m2.equipo_id = e.id
            ORDER BY m2.id DESC
            LIMIT 1
        )
        WHERE m.estado = 'Inactivo'
    ";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($fila = $res->fetch_assoc()) {
            $equipos_inactivos[] = $fila["nombre"] . " (" . $fila["ip"] . "), último chequeo: " . $fila["ultimo_chequeo"];
        }
    }

    $contexto = "Equipos actualmente inactivos:\n";
    if (count($equipos_inactivos) > 0) {
        $contexto .= "- " . implode("\n- ", $equipos_inactivos);
    } else {
        $contexto .= "- No hay equipos inactivos en este momento.";
    }
}

/* CAMBIOS RECIENTES */
elseif (strpos($pregunta_minuscula, "cambio") !== false || strpos($pregunta_minuscula, "reciente") !== false) {
    $cambios = [];

    $sql = "
        SELECT e.nombre, m.estado, m.ultimo_chequeo
        FROM monitoreo m
        INNER JOIN equipos e ON e.id = m.equipo_id
        ORDER BY m.id DESC
        LIMIT 10
    ";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($fila = $res->fetch_assoc()) {
            $cambios[] = $fila["nombre"] . " → " . $fila["estado"] . " (" . $fila["ultimo_chequeo"] . ")";
        }
    }

    $contexto = "Últimos cambios o registros recientes de monitoreo:\n";
    $contexto .= "- " . implode("\n- ", $cambios);
}

/* ACTIVIDAD ADMINISTRATIVA */
elseif (strpos($pregunta_minuscula, "administrador") !== false || strpos($pregunta_minuscula, "administrativa") !== false || strpos($pregunta_minuscula, "bitácora") !== false) {
    $eventos_admin = [];

    $sql = "
        SELECT fecha_evento, usuario_nombre, modulo, accion, descripcion
        FROM bitacora_sistema
        ORDER BY id DESC
        LIMIT 10
    ";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($fila = $res->fetch_assoc()) {
            $eventos_admin[] = $fila["fecha_evento"] . " | " . $fila["usuario_nombre"] . " | " . $fila["modulo"] . " | " . $fila["accion"];
        }
    }

    $contexto = "Actividad administrativa reciente registrada en bitácora:\n";
    $contexto .= "- " . implode("\n- ", $eventos_admin);
}


/* LISTAR EQUIPOS ACTIVOS CON MÉTRICAS */
elseif (
    strpos($pregunta_minuscula, "equipos con métricas") !== false ||
    strpos($pregunta_minuscula, "equipos con metricas") !== false ||
    strpos($pregunta_minuscula, "ver métricas") !== false ||
    strpos($pregunta_minuscula, "ver metricas") !== false ||
    strpos($pregunta_minuscula, "revisar métricas") !== false ||
    strpos($pregunta_minuscula, "revisar metricas") !== false
) {
    $equipos_metricas = [];

    $sql = "
        SELECT e.id, e.nombre, e.ip, e.instancia_metricas, m.estado, m.ultimo_chequeo
        FROM equipos e
        INNER JOIN monitoreo m ON m.id = (
            SELECT m2.id
            FROM monitoreo m2
            WHERE m2.equipo_id = e.id
            ORDER BY m2.id DESC
            LIMIT 1
        )
        WHERE m.estado = 'Activo'
        AND e.instancia_metricas IS NOT NULL
        AND e.instancia_metricas <> ''
        ORDER BY e.nombre ASC
    ";

    $res = $conn->query($sql);

    $_SESSION["equipos_metricas_ia"] = [];

    if ($res && $res->num_rows > 0) {
        $contador = 1;

        while ($fila = $res->fetch_assoc()) {
            $equipos_metricas[] = $contador . ". " . $fila["nombre"] . " - " . $fila["ip"] . " - instancia: " . $fila["instancia_metricas"];
            
            $_SESSION["equipos_metricas_ia"][$contador] = [
                "id" => $fila["id"],
                "nombre" => $fila["nombre"],
                "ip" => $fila["ip"],
                "instancia_metricas" => $fila["instancia_metricas"]
            ];

            $contador++;
        }

        $contexto = "Equipos activos con métricas disponibles:\n";
        $contexto .= "- " . implode("\n- ", $equipos_metricas);
        $contexto .= "\n\nIndica al usuario que puede escribir el número o el nombre del equipo que desea revisar.";
    } else {
        $contexto = "No se encontraron equipos activos con métricas disponibles.";
    }
}

/* SELECCIÓN DE EQUIPO DESDE LISTA DE MÉTRICAS */
elseif (
    isset($_SESSION["equipos_metricas_ia"]) &&
    count($_SESSION["equipos_metricas_ia"]) > 0
) {
    $equipo_seleccionado = null;

    // Caso 1: el usuario escribe un número
    if (is_numeric($pregunta_minuscula)) {
        $numero = (int)$pregunta_minuscula;

        if (isset($_SESSION["equipos_metricas_ia"][$numero])) {
            $equipo_seleccionado = $_SESSION["equipos_metricas_ia"][$numero];
        }
    }

    // Caso 2: el usuario escribe el nombre o parte del nombre
    if (!$equipo_seleccionado) {
        foreach ($_SESSION["equipos_metricas_ia"] as $equipo_lista) {
            $nombre_equipo = mb_strtolower($equipo_lista["nombre"], 'UTF-8');
            $ip_equipo = mb_strtolower($equipo_lista["ip"], 'UTF-8');

            if (
                strpos($nombre_equipo, $pregunta_minuscula) !== false ||
                strpos($pregunta_minuscula, $nombre_equipo) !== false ||
                strpos($pregunta_minuscula, $ip_equipo) !== false
            ) {
                $equipo_seleccionado = $equipo_lista;
                break;
            }
        }
    }

    if ($equipo_seleccionado) {
        $contexto = construirContextoMetricasEquipo($equipo_seleccionado, $prometheus);

        // Opcional: limpiar la lista después de elegir
        unset($_SESSION["equipos_metricas_ia"]);
    } else {
        $contexto = "El usuario intentó seleccionar un equipo desde la lista de métricas, pero no se encontró coincidencia.\n";
        $contexto .= "Indica que debe escribir el número de la lista o el nombre exacto del equipo.";
    }
}

/* MÉTRICAS DE EQUIPO */
elseif (
    strpos($pregunta_minuscula, "métrica") !== false ||
    strpos($pregunta_minuscula, "metricas") !== false ||
    strpos($pregunta_minuscula, "cpu") !== false ||
    strpos($pregunta_minuscula, "ram") !== false ||
    strpos($pregunta_minuscula, "disco") !== false ||
    strpos($pregunta_minuscula, "cómo está") !== false ||
    strpos($pregunta_minuscula, "como está") !== false ||
    strpos($pregunta_minuscula, "estado del servidor") !== false
) {
    $equipo_encontrado = null;

    $sql = "
        SELECT id, nombre, ip, instancia_metricas
        FROM equipos
        WHERE instancia_metricas IS NOT NULL
        AND instancia_metricas <> ''
    ";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($fila = $res->fetch_assoc()) {
            $nombre_minuscula = mb_strtolower($fila["nombre"], 'UTF-8');
            $ip_equipo = $fila["ip"];

            if (
                strpos($pregunta_minuscula, $nombre_minuscula) !== false ||
                strpos($pregunta_minuscula, $ip_equipo) !== false
            ) {
                $equipo_encontrado = $fila;
                break;
            }
        }
    }

    if ($equipo_encontrado) {
        $contexto = construirContextoMetricasEquipo($equipo_encontrado, $prometheus);
    } else {
        $contexto = "El usuario consultó por métricas de un equipo, pero no se encontró un equipo registrado cuyo nombre o IP coincida con la pregunta.\n";
        $contexto .= "Indica que debe escribir el nombre exacto del equipo registrado en la plataforma.";
    }
}



/* ESTADO GENERAL */
else {
    $total_equipos = 0;
    $activos = 0;
    $inactivos = 0;

    $sql_total = "SELECT COUNT(*) AS total FROM equipos";
    $res_total = $conn->query($sql_total);
    if ($res_total) {
        $total_equipos = (int)$res_total->fetch_assoc()["total"];
    }

    $sql_estados = "
        SELECT estado, COUNT(*) AS total
        FROM (
            SELECT m1.equipo_id, m1.estado
            FROM monitoreo m1
            INNER JOIN (
                SELECT equipo_id, MAX(id) AS ultimo_id
                FROM monitoreo
                GROUP BY equipo_id
            ) ult ON m1.id = ult.ultimo_id
        ) t
        GROUP BY estado
    ";

    $res_estados = $conn->query($sql_estados);
    if ($res_estados) {
        while ($fila = $res_estados->fetch_assoc()) {
            if ($fila["estado"] === "Activo") {
                $activos = (int)$fila["total"];
            } elseif ($fila["estado"] === "Inactivo") {
                $inactivos = (int)$fila["total"];
            }
        }
    }

    $contexto = "Estado general actual del sistema:\n";
    $contexto .= "- Equipos registrados: $total_equipos\n";
    $contexto .= "- Equipos activos: $activos\n";
    $contexto .= "- Equipos inactivos: $inactivos\n";
}

$prompt = "Eres un asistente de monitoreo TI. Responde de forma clara, breve y profesional, usando solamente la información entregada a continuación. No inventes datos.\n\n";
$prompt .= "Pregunta del usuario: " . $pregunta . "\n\n";
$prompt .= "Contexto del sistema:\n" . $contexto;

$resultado_ia = consultarBedrock($prompt);

if (!$resultado_ia["ok"]) {
    echo json_encode(["error" => $resultado_ia["error"]]);
    exit;
}

echo json_encode([
    "respuesta" => $resultado_ia["respuesta"]
]);
?>
