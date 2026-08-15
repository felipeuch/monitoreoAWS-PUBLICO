<?php
include("verificar_sesion.php");
include("conexion.php");
include("sns_alerta.php");
include("bitacora_funciones.php");

if (!isset($_GET["id"]) || !is_numeric($_GET["id"]) || (int)$_GET["id"] <= 0) {
    die("ID no válido.");
}

$id = (int) $_GET["id"];

$sql = "SELECT * FROM equipos WHERE id = '$id'";
$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows == 0) {
    die("Equipo no encontrado.");
}

$equipo = $resultado->fetch_assoc();
$ip = $equipo["ip"];
$nombre = $equipo["nombre"];

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
               WHERE equipo_id = '$id'
               ORDER BY ultimo_chequeo DESC, id DESC
               LIMIT 1";

$res_ultimo = $conn->query($sql_ultimo);
$ultimo_estado = null;

if ($res_ultimo && $res_ultimo->num_rows > 0) {
    $fila_ultimo = $res_ultimo->fetch_assoc();
    $ultimo_estado = $fila_ultimo["estado"];
}

$cambio_detectado = false;

if ($ultimo_estado !== $estado) {
    $insertar = "INSERT INTO monitoreo (equipo_id, estado, latencia, puerto, ultimo_chequeo, observacion)
                 VALUES ('$id', '$estado', '$latencia', '$puerto', '$ultimo_chequeo', '$observacion')";

    if ($conn->query($insertar) === TRUE) {
        $cambio_detectado = true;
        $mensaje = "Monitoreo ejecutado correctamente. Se detectó un cambio de estado para el equipo: " . htmlspecialchars($nombre);
    } else {
        $mensaje = "Error al guardar el monitoreo: " . $conn->error;
    }
} else {
    $mensaje = "Monitoreo ejecutado correctamente. No hubo cambio de estado para el equipo: " . htmlspecialchars($nombre);
}

registrarBitacora($conn, "Métricas", "Ver métricas", "Se accedió a la vista de métricas del equipo " . $nombre . " con IP " . $ip);

if ($cambio_detectado && ($estado == "Inactivo" || $estado == "Activo")) {
    $asunto = "Notificación de monitoreo: equipo " . $estado;

    $mensajeCorreo = "Se ha generado una notificación desde la plataforma EduNexa Cloud Monitoring.\n\n";
    $mensajeCorreo .= "Equipo: " . $nombre . "\n";
    $mensajeCorreo .= "IP: " . $ip . "\n";
    $mensajeCorreo .= "Fecha de chequeo: " . $ultimo_chequeo . "\n";
    $mensajeCorreo .= "Estado detectado: " . $estado . "\n";
    $mensajeCorreo .= "Resultado: " . $observacion . "\n";

    enviarAlertaSNS($asunto, $mensajeCorreo);
}

include("header.php");
?>

<div class="page-card">
    <h1 class="page-title">Monitoreo individual</h1>
    <p class="page-subtitle">
        Se ha ejecutado una revisión puntual sobre el equipo seleccionado dentro del inventario.
    </p>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Equipo</h3>
            <div class="stat-number" style="font-size: 1.1rem;"><?php echo htmlspecialchars($nombre); ?></div>
        </div>

        <div class="stat-card">
            <h3>IP monitoreada</h3>
            <div class="stat-number" style="font-size: 1.1rem;"><?php echo htmlspecialchars($ip); ?></div>
        </div>

        <div class="stat-card">
            <h3>Estado</h3>
            <div class="stat-number" style="font-size: 1.1rem;">
                <?php if ($estado == "Activo") { ?>
                    <span class="badge ok">Activo</span>
                <?php } else { ?>
                    <span class="badge down">Inactivo</span>
                <?php } ?>
            </div>
        </div>

        <div class="stat-card">
            <h3>Último chequeo</h3>
            <div class="stat-number" style="font-size: 1rem;"><?php echo $ultimo_chequeo; ?></div>
        </div>
    </div>

    <div class="message"><?php echo $mensaje; ?></div>

    <div class="soft-box section-space">
        <p class="page-note">
            Resultado del monitoreo: <strong><?php echo htmlspecialchars($observacion); ?></strong>
        </p>
    </div>

    <div class="actions">
        <a href="ver_monitoreo.php" class="btn btn-primary">Ver historial</a>
        <a href="listar_equipos.php" class="btn btn-secondary">Volver a equipos</a>
        <a href="index.php" class="btn btn-secondary">Ir al inicio</a>
    </div>
</div>

<?php include("footer.php"); ?>
