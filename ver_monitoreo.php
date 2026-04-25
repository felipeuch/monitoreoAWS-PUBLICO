<?php
include("verificar_sesion.php");
include("conexion.php");

$busqueda = "";
$estado_filtro = "";
$solo_ultimo = "";
$mensaje = "";

if (isset($_GET["busqueda"])) {
    $busqueda = trim($_GET["busqueda"]);
}

if (isset($_GET["estado"])) {
    $estado_filtro = trim($_GET["estado"]);
}

if (isset($_GET["solo_ultimo"])) {
    $solo_ultimo = $_GET["solo_ultimo"];
}

if (isset($_GET["mensaje"]) && $_GET["mensaje"] == "historial_limpiado") {
    $mensaje = "El historial de monitoreo fue eliminado correctamente.";
}

$condiciones = [];

if (!empty($busqueda)) {
    $busqueda_segura = $conn->real_escape_string($busqueda);
    $condiciones[] = "(equipos.nombre LIKE '%$busqueda_segura%' OR equipos.ip LIKE '%$busqueda_segura%')";
}

if (!empty($estado_filtro)) {
    $estado_seguro = $conn->real_escape_string($estado_filtro);
    $condiciones[] = "monitoreo.estado = '$estado_seguro'";
}

$where_sql = "";
if (count($condiciones) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $condiciones);
}

/* base de consulta según modo */
if ($solo_ultimo == "1") {
    $from_sql = "FROM monitoreo
                 INNER JOIN equipos ON monitoreo.equipo_id = equipos.id
                 INNER JOIN (
                     SELECT equipo_id, MAX(id) AS ultimo_id
                     FROM monitoreo
                     GROUP BY equipo_id
                 ) ultimos ON monitoreo.id = ultimos.ultimo_id
                 $where_sql";
} else {
    $from_sql = "FROM monitoreo
                 INNER JOIN equipos ON monitoreo.equipo_id = equipos.id
                 $where_sql";
}

/* estadísticas generales */
$sql_stats = "SELECT 
                COUNT(*) AS total_registros,
                SUM(CASE WHEN monitoreo.estado = 'Activo' THEN 1 ELSE 0 END) AS total_activos,
                SUM(CASE WHEN monitoreo.estado = 'Inactivo' THEN 1 ELSE 0 END) AS total_inactivos
              $from_sql";

$res_stats = $conn->query($sql_stats);

$totalRegistros = 0;
$totalActivos = 0;
$totalInactivos = 0;

if ($res_stats && $res_stats->num_rows > 0) {
    $fila_stats = $res_stats->fetch_assoc();
    $totalRegistros = (int)($fila_stats["total_registros"] ?? 0);
    $totalActivos = (int)($fila_stats["total_activos"] ?? 0);
    $totalInactivos = (int)($fila_stats["total_inactivos"] ?? 0);
}

/* paginación */
$pagina = isset($_GET["pagina"]) ? (int) $_GET["pagina"] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

$registros_por_pagina = 10;
$total_paginas = ($totalRegistros > 0) ? (int)ceil($totalRegistros / $registros_por_pagina) : 1;

if ($pagina > $total_paginas) {
    $pagina = $total_paginas;
}

$offset = ($pagina - 1) * $registros_por_pagina;

/* consulta paginada */
$sql = "SELECT monitoreo.id, equipos.nombre, equipos.ip, monitoreo.estado, monitoreo.latencia,
               monitoreo.puerto, monitoreo.ultimo_chequeo, monitoreo.observacion
        $from_sql
        ORDER BY monitoreo.ultimo_chequeo DESC
        LIMIT $registros_por_pagina OFFSET $offset";

$resultado = $conn->query($sql);

/* conservar filtros en enlaces de paginación */
$query_base = [];
if ($busqueda !== "") {
    $query_base["busqueda"] = $busqueda;
}
if ($estado_filtro !== "") {
    $query_base["estado"] = $estado_filtro;
}
if ($solo_ultimo == "1") {
    $query_base["solo_ultimo"] = "1";
}

include("header.php");
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total de revisiones</h3>
        <div class="stat-number"><?php echo $totalRegistros; ?></div>
    </div>

    <div class="stat-card">
        <h3>Estados activos</h3>
        <div class="stat-number"><?php echo $totalActivos; ?></div>
    </div>

    <div class="stat-card">
        <h3>Estados inactivos</h3>
        <div class="stat-number"><?php echo $totalInactivos; ?></div>
    </div>

    <div class="stat-card">
        <h3>Modo de visualización</h3>
        <div class="stat-number" style="font-size: 1rem;">
            <?php echo ($solo_ultimo == "1") ? "Último por equipo" : "Historial completo"; ?>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="table-title-row">
        <div>
            <h1 class="page-title">Historial de monitoreo</h1>
            <p class="page-subtitle">
                Revisa los resultados almacenados de las ejecuciones de monitoreo realizadas sobre los equipos registrados.
            </p>
        </div>

        <div class="actions">
            <a href="monitorear.php" class="btn btn-primary">Ejecutar revisión</a>
            <a href="limpiar_monitoreo.php" class="btn btn-secondary" onclick="return confirm('¿Seguro que deseas eliminar todo el historial de monitoreo?');">Limpiar historial</a> 
            <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
        </div>
    </div>

<?php if (!empty($mensaje)): ?>
    <div class="message"><?php echo $mensaje; ?></div>
<?php endif; ?>

    <div class="soft-box section-space">
        <p class="page-note">
            Esta vista entrega trazabilidad básica de los chequeos realizados por la plataforma,
            permitiendo identificar disponibilidad y evidencia histórica de los activos monitoreados.
        </p>
    </div>

    <div class="page-card section-space">
        <h2 class="form-section-title">Filtrar historial</h2>

        <form method="GET" action="">
            <label>Buscar por nombre de equipo o IP</label>
            <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Ejemplo: PC-LAB-01 o 192.168.1.10">

            <label>Estado</label>
            <select name="estado">
                <option value="">Todos</option>
                <option value="Activo" <?php echo ($estado_filtro == "Activo") ? "selected" : ""; ?>>Activo</option>
                <option value="Inactivo" <?php echo ($estado_filtro == "Inactivo") ? "selected" : ""; ?>>Inactivo</option>
            </select>

            <label>
                <input type="checkbox" name="solo_ultimo" value="1" <?php echo ($solo_ultimo == "1") ? "checked" : ""; ?>>
                Mostrar solo el último registro por equipo
            </label>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Aplicar filtro</button>
                <a href="ver_monitoreo.php" class="btn btn-secondary">Limpiar filtro</a>
            </div>
        </form>
    </div>

    <div class="soft-box section-space">
        <p class="page-note">
            Mostrando página <strong><?php echo $pagina; ?></strong> de
            <strong><?php echo max($total_paginas, 1); ?></strong>.
            Total de registros de monitoreo: <strong><?php echo $totalRegistros; ?></strong>.
        </p>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <th>Equipo</th>
                <th>IP</th>
                <th>Estado</th>
                <th>Latencia</th>
                <th>Puerto</th>
                <th>Último chequeo</th>
                <th>Observación</th>
            </tr>

            <?php
            if ($resultado && $resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    $claseEstado = "warn";
                    if ($fila["estado"] == "Activo") {
                        $claseEstado = "ok";
                    } elseif ($fila["estado"] == "Inactivo") {
                        $claseEstado = "down";
                    }

                    echo "<tr>";
                    echo "<td>" . $fila["id"] . "</td>";
                    echo "<td><span class='highlight-value'>" . htmlspecialchars($fila["nombre"]) . "</span></td>";
                    echo "<td>" . htmlspecialchars($fila["ip"]) . "</td>";
                    echo "<td><span class='badge " . $claseEstado . "'>" . htmlspecialchars($fila["estado"]) . "</span></td>";
                    echo "<td>" . htmlspecialchars($fila["latencia"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["puerto"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["ultimo_chequeo"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["observacion"]) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8'>No hay registros de monitoreo</td></tr>";
            }
            ?>
        </table>
    </div>

    <?php if ($total_paginas > 1) { ?>
        <div class="actions" style="margin-top: 20px; justify-content: center;">
            <?php
            if ($pagina > 1) {
                $query_prev = $query_base;
                $query_prev["pagina"] = $pagina - 1;
                echo '<a class="btn btn-secondary" href="ver_monitoreo.php?' . htmlspecialchars(http_build_query($query_prev)) . '">Anterior</a>';
            }

            for ($i = 1; $i <= $total_paginas; $i++) {
                if ($i == $pagina) {
                    echo '<span class="btn btn-primary" style="pointer-events:none; opacity:0.9;">' . $i . '</span>';
                } else {
                    $query_pag = $query_base;
                    $query_pag["pagina"] = $i;
                    echo '<a class="btn btn-secondary" href="ver_monitoreo.php?' . htmlspecialchars(http_build_query($query_pag)) . '">' . $i . '</a>';
                }
            }

            if ($pagina < $total_paginas) {
                $query_next = $query_base;
                $query_next["pagina"] = $pagina + 1;
                echo '<a class="btn btn-secondary" href="ver_monitoreo.php?' . htmlspecialchars(http_build_query($query_next)) . '">Siguiente</a>';
            }
            ?>
        </div>
    <?php } ?>
</div>

<?php include("footer.php"); ?>
