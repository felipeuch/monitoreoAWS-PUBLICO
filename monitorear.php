<?php
include("verificar_sesion.php");
include("conexion.php");
include("bitacora_funciones.php");

$sql = "SELECT * FROM equipos";
$resultado = $conn->query($sql);

$mensaje = "No hay equipos registrados para monitorear.";
$cambios_detectados = 0;
$total_equipos_revisados = 0;

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $total_equipos_revisados++;

        $equipo_id = $fila["id"];
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
        }
    }

    if ($cambios_detectados > 0) {
        $mensaje = "Monitoreo ejecutado correctamente. Se registraron $cambios_detectados cambio(s) de estado.";
    } else {
        $mensaje = "Monitoreo ejecutado correctamente. No se detectaron cambios de estado.";
    }

    /* Registrar ejecución manual */
    $fecha_ejecucion = date("Y-m-d H:i:s");
    $tipo = "Manual";
    $equipos_revisados = $total_equipos_revisados;
    $estado_ejecucion = "Correcto";
    $observacion_ejecucion = "Monitoreo manual ejecutado desde la plataforma";

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
}

registrarBitacora($conn, "Monitoreo", "Monitoreo manual", $mensaje);
include("header.php");
?>

<div class="page-card">
    <h1 class="page-title">Ejecución de monitoreo</h1>
    <p class="page-subtitle">
        El sistema ha ejecutado una revisión de conectividad sobre los equipos registrados.
    </p>

    <div class="message"><?php echo $mensaje; ?></div>

    <div class="actions">
        <a href="ver_monitoreo.php" class="btn btn-primary">Ver historial</a>
        <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
    </div>
</div>

<?php include("footer.php"); ?>
