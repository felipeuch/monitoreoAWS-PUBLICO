<?php
include(__DIR__ . "/conexion.php");
include(__DIR__ . "/sns_alerta.php");

$sql = "SELECT * FROM equipos";
$resultado = $conn->query($sql);

$cambios_detectados = 0;
$total_equipos_revisados = 0;

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $total_equipos_revisados++;

        $equipo_id = $fila["id"];
        $nombre = $fila["nombre"];
        $ip = $fila["ip"];

        $ping = shell_exec("ping -c 1 -W 1 $ip");

        if (strpos($ping, "1 received") !== false || strpos($ping, "1 packets received") !== false) {
            $estado = "Activo";
            $latencia = "Disponible";
            $observacion = "El equipo responde al ping";
        } else {
            $estado = "Inactivo";
            $latencia = "No responde";
            $observacion = "El equipo no responde al ping";
        }

        $puerto = "N/A";
        $ultimo_chequeo = date("Y-m-d H:i:s");

        $sql_ultimo = "SELECT estado
                       FROM monitoreo
                       WHERE equipo_id = '$equipo_id'
                       ORDER BY ultimo_chequeo DESC, id DESC
                       LIMIT 1";

        $res_ultimo = $conn->query($sql_ultimo);

        $ultimo_estado = null;

        if ($res_ultimo && $res_ultimo->num_rows > 0) {
            $fila_ultimo = $res_ultimo->fetch_assoc();
            $ultimo_estado = $fila_ultimo["estado"];
        }

        if ($ultimo_estado !== $estado) {
            $insertar = "INSERT INTO monitoreo (equipo_id, estado, latencia, puerto, ultimo_chequeo, observacion)
                         VALUES ('$equipo_id', '$estado', '$latencia', '$puerto', '$ultimo_chequeo', '$observacion')";
            $conn->query($insertar);
            $cambios_detectados++;

        $tipo_equipo = isset($fila["tipo"]) ? $fila["tipo"] : "No especificado";
        $sistema_operativo = isset($fila["sistema_operativo"]) ? $fila["sistema_operativo"] : "No especificado";

        $asunto = "Cloud Monitoring | Cambio de estado: $nombre";

        $mensaje = "Se detectó un cambio de estado en el monitoreo automático.\n\n";
        $mensaje .= "----------------------------------------\n";
        $mensaje .= "Equipo: $nombre\n";
        $mensaje .= "IP: $ip\n";      
        $mensaje .= "Tipo: $tipo_equipo\n";
        $mensaje .= "Sistema operativo: $sistema_operativo\n";
        $mensaje .= "Estado anterior: " . ($ultimo_estado === null ? "Sin registro previo" : $ultimo_estado) . "\n";
        $mensaje .= "Estado actual: $estado\n";
        $mensaje .= "Fecha y hora del chequeo: $ultimo_chequeo\n";
        $mensaje .= "----------------------------------------\n\n";
        $mensaje .= "Este aviso fue generado automáticamente por Cloud Monitoring.";
            enviarAlertaSNS($asunto, $mensaje);
        }
    }

    $fecha_ejecucion = date("Y-m-d H:i:s");
    $tipo = "Automático";
    $equipos_revisados = $total_equipos_revisados;
    $estado_ejecucion = "Correcto";
    $observacion_ejecucion = "Monitoreo automático ejecutado por cron";

    $sql_ejecucion = "INSERT INTO ejecuciones_monitoreo (
        fecha_ejecucion,
        tipo,
        equipos_revisados,
        cambios_detectados,
        estado_ejecucion,
        observacion
    ) VALUES (
        '$fecha_ejecucion',
        '$tipo',
        '$equipos_revisados',
        '$cambios_detectados',
        '$estado_ejecucion',
        '" . $conn->real_escape_string($observacion_ejecucion) . "'
    )";

    $conn->query($sql_ejecucion);

    echo "Monitoreo automático ejecutado correctamente.\n";
} else {
    echo "No hay equipos registrados para monitorear.\n";
}
?>
