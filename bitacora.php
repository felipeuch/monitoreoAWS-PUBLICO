<?php
include("verificar_sesion.php");
include("conexion.php");

$filtro_usuario = $_GET["usuario"] ?? "";
$filtro_modulo = $_GET["modulo"] ?? "";
$filtro_fecha_desde = $_GET["fecha_desde"] ?? "";
$filtro_fecha_hasta = $_GET["fecha_hasta"] ?? "";

$pagina = isset($_GET["pagina"]) ? (int) $_GET["pagina"] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;


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

$sql_total = "SELECT COUNT(*) AS total FROM bitacora_sistema";

if (!empty($where)) {
    $sql_total .= " WHERE " . implode(" AND ", $where);
}

$resultado_total = $conn->query($sql_total);
$fila_total = $resultado_total->fetch_assoc();
$total_registros = (int) $fila_total["total"];
$total_paginas = ceil($total_registros / $registros_por_pagina);


$sql = "SELECT * FROM bitacora_sistema";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY fecha_evento DESC, id DESC";
$sql .= " LIMIT $registros_por_pagina OFFSET $offset";

$resultado = $conn->query($sql);

include("header.php");
?>

<div class="page-card">
    <h1 class="page-title">Bitácora del sistema</h1>
    <p class="page-subtitle">
        Registro de auditoría de acciones realizadas dentro de la plataforma.
    </p>

    <div class="page-card section-space">
        <h2 class="form-section-title">Filtros</h2>

        <form method="GET" action="">
            <label>Usuario</label>
            <input type="text" name="usuario" value="<?php echo htmlspecialchars($filtro_usuario); ?>">

            <label>Módulo</label>
            <input type="text" name="modulo" value="<?php echo htmlspecialchars($filtro_modulo); ?>">

            <label>Fecha desde</label>
            <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">

            <label>Fecha hasta</label>
            <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">

            <div class="actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="bitacora.php" class="btn btn-secondary">Limpiar</a>
                <a href="exportar_bitacora_csv.php?usuario=<?php echo urlencode($filtro_usuario); ?>&modulo=<?php echo urlencode($filtro_modulo); ?>&fecha_desde=<?php echo urlencode($filtro_fecha_desde); ?>&fecha_hasta=<?php echo urlencode($filtro_fecha_hasta); ?>" class="btn btn-secondary">Exportar CSV</a>  
            </div>
        </form>
    </div>

    <div class="soft-box section-space">
        <p class="page-note">
            Mostrando página <strong><?php echo $pagina; ?></strong> de <strong><?php echo max($total_paginas, 1); ?></strong>.
            Total de registros encontrados: <strong><?php echo $total_registros; ?></strong>.
        </p>
    </div> 


    <div class="table-wrapper">
        <table>
            <tr>
                <th>Fecha y hora</th>
                <th>Usuario</th>
                <th>Módulo</th>
                <th>Acción</th>
                <th>Descripción</th>
                <th>IP origen</th>
            </tr>

            <?php
            if ($resultado && $resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($fila["fecha_evento"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["usuario_nombre"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["modulo"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["accion"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["descripcion"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["ip_origen"]) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No hay registros en la bitácora.</td></tr>";
            }
            ?>
        </table>
    </div>
   <?php if ($total_paginas > 1) { ?>
    <div class="actions" style="margin-top: 20px; justify-content: center;">
        <?php if ($pagina > 1) { ?>
            <a class="btn btn-secondary" href="bitacora.php?usuario=<?php echo urlencode($filtro_usuario); ?>&modulo=<?php echo urlencode($filtro_modulo); ?>&fecha_desde=<?php echo urlencode($filtro_fecha_desde); ?>&fecha_hasta=<?php echo urlencode($filtro_fecha_hasta); ?>&pagina=<?php echo $pagina - 1; ?>">Anterior</a>
        <?php } ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++) { ?>
            <?php if ($i == $pagina) { ?>
                <span class="btn btn-primary" style="pointer-events:none; opacity:0.9;"><?php echo $i; ?></span>
            <?php } else { ?>
                <a class="btn btn-secondary" href="bitacora.php?usuario=<?php echo urlencode($filtro_usuario); ?>&modulo=<?php echo urlencode($filtro_modulo); ?>&fecha_desde=<?php echo urlencode($filtro_fecha_desde); ?>&fecha_hasta=<?php echo urlencode($filtro_fecha_hasta); ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php } ?>
        <?php } ?>

        <?php if ($pagina < $total_paginas) { ?>
            <a class="btn btn-secondary" href="bitacora.php?usuario=<?php echo urlencode($filtro_usuario); ?>&modulo=<?php echo urlencode($filtro_modulo); ?>&fecha_desde=<?php echo urlencode($filtro_fecha_desde); ?>&fecha_hasta=<?php echo urlencode($filtro_fecha_hasta); ?>&pagina=<?php echo $pagina + 1; ?>">Siguiente</a>
        <?php } ?>
    </div>
<?php } ?>
</div>

<?php include("footer.php"); ?>
