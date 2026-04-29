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
