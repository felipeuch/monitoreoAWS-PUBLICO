<?php
include("verificar_sesion.php");
include("conexion.php");
include("bitacora_funciones.php");

if (!isset($_GET["id"]) || !is_numeric($_GET["id"]) || (int)$_GET["id"] <= 0) {
    die("ID no válido.");
}

$id = (int) $_GET["id"];

$sql = "SELECT * FROM equipos WHERE id='$id' LIMIT 1";
$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows == 0) {
    die("Equipo no encontrado.");
}

$equipo = $resultado->fetch_assoc();
$nombre = $equipo["nombre"];
$ip = $equipo["ip"];
$tipo = $equipo["tipo"];
$sistema_operativo = $equipo["sistema_operativo"];
$instancia_metricas = $equipo["instancia_metricas"];
$metricas_habilitadas = $equipo["metricas_habilitadas"];

registrarBitacora($conn, "Métricas", "Ver métricas", "Se accedió a la vista de métricas del equipo " . $nombre . " con IP " . $ip);

$estado_actual = "Sin registros";
$clase_estado = "warn";

$sql_estado = "SELECT estado 
               FROM monitoreo 
               WHERE equipo_id='$id'
               ORDER BY ultimo_chequeo DESC, id DESC
               LIMIT 1";
$res_estado = $conn->query($sql_estado);

if ($res_estado && $res_estado->num_rows > 0) {
    $fila_estado = $res_estado->fetch_assoc();
    $estado_actual = $fila_estado["estado"];

    if ($estado_actual == "Activo") {
        $clase_estado = "ok";
    } elseif ($estado_actual == "Inactivo") {
        $clase_estado = "down";
    }
}

include("header.php");
?>

<div class="page-card">
    <h1 class="page-title">Métricas del equipo</h1>
    <p class="page-subtitle">
        Visualización de métricas avanzadas asociadas al equipo seleccionado.
    </p>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Equipo</h3>
            <div class="stat-number" style="font-size:1.1rem;"><?php echo htmlspecialchars($nombre); ?></div>
        </div>

        <div class="stat-card">
            <h3>IP del equipo</h3>
            <div class="stat-number" style="font-size:1.1rem;"><?php echo htmlspecialchars($ip); ?></div>
        </div>

        <div class="stat-card">
            <h3>Tipo</h3>
            <div class="stat-number" style="font-size:1.1rem;"><?php echo htmlspecialchars($tipo); ?></div>
        </div>

        <div class="stat-card">
            <h3>Estado actual</h3>
            <div class="stat-number" style="font-size:1.1rem;">
                <span class="badge <?php echo $clase_estado; ?>"><?php echo htmlspecialchars($estado_actual); ?></span>
            </div>
        </div>
    </div>

    <?php if ($metricas_habilitadas == 1 && !empty($instancia_metricas)) { ?>
        <div class="soft-box section-space">
            <p class="page-note">
                Este equipo cuenta con métricas avanzadas habilitadas mediante Prometheus y Grafana.
            </p>
            <p class="page-note" style="margin-top:10px;">
                Instancia de métricas asociada: <strong><?php echo htmlspecialchars($instancia_metricas); ?></strong>
            </p>
            <p class="page-note" style="margin-top:10px;">
                Sistema operativo asociado: <strong><?php echo htmlspecialchars($sistema_operativo); ?></strong>
            </p>
        </div>

        <div class="actions">
            <a href="http://3.16.24.177:3000" target="_blank" class="btn btn-primary">Abrir Grafana</a>
            <a href="listar_equipos.php" class="btn btn-secondary">Volver a equipos</a>
            <a href="index.php" class="btn btn-secondary">Ir al inicio</a>
        </div>
    <?php } else { ?>
        <div class="message">
            Este equipo no tiene métricas avanzadas habilitadas.
        </div>

        <div class="actions">
            <a href="editar_equipo.php?id=<?php echo $id; ?>" class="btn btn-primary">Configurar equipo</a>
            <a href="listar_equipos.php" class="btn btn-secondary">Volver a equipos</a>
        </div>
    <?php } ?>
</div>

<?php include("footer.php"); ?>
