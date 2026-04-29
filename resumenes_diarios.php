<?php
include("verificar_sesion.php");
include("conexion.php");
include("bitacora_funciones.php");

registrarBitacora($conn, "Resumen Diario", "Ver resúmenes", "Se accedió a la vista de resúmenes diarios.");

$pagina = isset($_GET["pagina"]) ? (int) $_GET["pagina"] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

$registros_por_pagina = 5;
$offset = ($pagina - 1) * $registros_por_pagina;

/* total de registros */
$sql_total = "SELECT COUNT(*) AS total FROM resumenes_diarios";
$res_total = $conn->query($sql_total);
$fila_total = $res_total->fetch_assoc();
$total_registros = (int)$fila_total["total"];
$total_paginas = ceil($total_registros / $registros_por_pagina);

/* consulta paginada */
$sql = "SELECT * FROM resumenes_diarios
        ORDER BY fecha_resumen DESC, id DESC
        LIMIT $registros_por_pagina OFFSET $offset";
$resultado = $conn->query($sql);

include("header.php");
?>

<div class="page-card">
    <h1 class="page-title">Resumen diario del sistema</h1>
    <p class="page-subtitle">
        Esta sección permite generar y consultar resúmenes ejecutivos de la actividad diaria del sistema.
    </p>

    <div class="actions">
        <a href="generar_resumen_diario.php" class="btn btn-primary">Generar resumen diario</a>
        <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
    </div>

    <div class="soft-box section-space">
        <p class="page-note">
            El resumen diario consolida información de bitácora, monitoreo y ejecuciones, permitiendo una lectura rápida del comportamiento general de la plataforma.
        </p>
    </div>

    <div class="soft-box section-space">
        <p class="page-note">
            Mostrando página <strong><?php echo $pagina; ?></strong> de
            <strong><?php echo max($total_paginas, 1); ?></strong>.
            Total de resúmenes: <strong><?php echo $total_registros; ?></strong>.
        </p>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>Fecha resumen</th>
                <th>Total eventos</th>
                <th>Cambios de estado</th>
                <th>Inactivos</th>
                <th>Ejecuciones automáticas</th>
                <th>Ejecuciones manuales</th>
                <th>Actividad principal</th>
                <th>Observación general</th>
                <th>Fecha generación</th>
            </tr>

            <?php
            if ($resultado && $resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($fila["fecha_resumen"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["total_eventos"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["total_cambios_estado"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["total_inactivos"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["total_ejecuciones_automaticas"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["total_ejecuciones_manuales"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["actividad_principal"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["observacion_general"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["fecha_generacion"]) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='9'>No hay resúmenes diarios generados.</td></tr>";
            }
            ?>
        </table>
    </div>

    <?php if ($total_paginas > 1) { ?>
        <div class="actions" style="margin-top: 20px; justify-content: center;">
            <?php if ($pagina > 1) { ?>
                <a class="btn btn-secondary" href="resumenes_diarios.php?pagina=<?php echo $pagina - 1; ?>">Anterior</a>
            <?php } ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++) { ?>
                <?php if ($i == $pagina) { ?>
                    <span class="btn btn-primary" style="pointer-events:none; opacity:0.9;"><?php echo $i; ?></span>
                <?php } else { ?>
                    <a class="btn btn-secondary" href="resumenes_diarios.php?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php } ?>
            <?php } ?>

            <?php if ($pagina < $total_paginas) { ?>
                <a class="btn btn-secondary" href="resumenes_diarios.php?pagina=<?php echo $pagina + 1; ?>">Siguiente</a>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<?php if (isset($_GET["generado"]) && $_GET["generado"] == 1) { ?>
<script>
Swal.fire({
    title: 'Resumen generado',
    text: 'El resumen diario fue generado correctamente.',
    icon: 'success',
    confirmButtonText: 'Aceptar'
});
</script>
<?php } ?>

<?php if (isset($_GET["correo_error"]) && $_GET["correo_error"] == 1) { ?>
<script>
Swal.fire({
    title: 'Resumen generado',
    text: 'El resumen se guardó correctamente, pero hubo un problema al enviar el correo.',
    icon: 'warning',
    confirmButtonText: 'Aceptar'
});
</script>
<?php } ?>

<?php if (isset($_GET["error"]) && $_GET["error"] == 1) { ?>
<script>
Swal.fire({
    title: 'Error',
    text: 'No se pudo generar el resumen diario.',
    icon: 'error',
    confirmButtonText: 'Aceptar'
});
</script>
<?php } ?>

<?php include("footer.php"); ?>
