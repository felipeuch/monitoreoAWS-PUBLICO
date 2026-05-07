<?php
include("verificar_sesion.php");
include("conexion.php");

$busqueda = "";

if (isset($_GET["busqueda"])) {
    $busqueda = trim($_GET["busqueda"]);
}

if (!empty($busqueda)) {
    $busqueda_segura = $conn->real_escape_string($busqueda);

    $sql = "SELECT e.*,
                   m.estado AS ultimo_estado,
                   m.ultimo_chequeo AS ultimo_chequeo
            FROM equipos e
            LEFT JOIN monitoreo m 
                ON m.id = (
                    SELECT m2.id
                    FROM monitoreo m2
                    WHERE m2.equipo_id = e.id
                    ORDER BY m2.ultimo_chequeo DESC, m2.id DESC
                    LIMIT 1
                )
            WHERE e.nombre LIKE '%$busqueda_segura%'
               OR e.ip LIKE '%$busqueda_segura%'
               OR e.ubicacion LIKE '%$busqueda_segura%'
               OR e.tipo LIKE '%$busqueda_segura%'
               OR e.sistema_operativo LIKE '%$busqueda_segura%'
            ORDER BY e.id DESC";
} else {
    $sql = "SELECT e.*,
                   m.estado AS ultimo_estado,
                   m.ultimo_chequeo AS ultimo_chequeo
            FROM equipos e
            LEFT JOIN monitoreo m 
                ON m.id = (
                    SELECT m2.id
                    FROM monitoreo m2
                    WHERE m2.equipo_id = e.id
                    ORDER BY m2.ultimo_chequeo DESC, m2.id DESC
                    LIMIT 1
                )
            ORDER BY e.id DESC";
}

$resultado = $conn->query($sql);

$totalEquipos = 0;
$totalLinux = 0;
$totalWindows = 0;
$totalOtros = 0;
$totalActivos = 0;
$totalInactivos = 0;
$totalSinMonitoreo = 0;

if ($resultado && $resultado->num_rows > 0) {
    $totalEquipos = $resultado->num_rows;

    while ($fila_temp = $resultado->fetch_assoc()) {
        $so = strtolower($fila_temp["sistema_operativo"]);
        $estado_temp = $fila_temp["ultimo_estado"];

        if (strpos($so, "linux") !== false) {
            $totalLinux++;
        } elseif (strpos($so, "windows") !== false) {
            $totalWindows++;
        } else {
            $totalOtros++;
        }

        if ($estado_temp == "Activo") {
            $totalActivos++;
        } elseif ($estado_temp == "Inactivo") {
            $totalInactivos++;
        } else {
            $totalSinMonitoreo++;
        }
    }

    $resultado->data_seek(0);
}

include("header.php");
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total de equipos</h3>
        <div class="stat-number"><?php echo $totalEquipos; ?></div>
    </div>

    <div class="stat-card">
        <h3>Equipos Linux</h3>
        <div class="stat-number"><?php echo $totalLinux; ?></div>
    </div>

    <div class="stat-card">
        <h3>Equipos Windows</h3>
        <div class="stat-number"><?php echo $totalWindows; ?></div>
    </div>

    <div class="stat-card">
        <h3>Otros sistemas</h3>
        <div class="stat-number"><?php echo $totalOtros; ?></div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Activos</h3>
        <div class="stat-number" id="statActivos"><?php echo $totalActivos; ?></div>
    </div>

    <div class="stat-card">
        <h3>Inactivos</h3>
        <div class="stat-number" id="statInactivos"><?php echo $totalInactivos; ?></div>
    </div>

    <div class="stat-card">
        <h3>Sin monitoreo</h3>
        <div class="stat-number" id="statSinMonitoreo"><?php echo $totalSinMonitoreo; ?></div>
    </div>

    <div class="stat-card">
        <h3>Estado general</h3>
        <div class="stat-number" id="statEstadoGeneral" style="font-size: 1rem;">
            <?php
            if ($totalEquipos == 0) {
                echo "Sin equipos";
            } elseif ($totalActivos > 0 && $totalInactivos == 0) {
                echo "Estable";
            } elseif ($totalInactivos > 0) {
                echo "Revisar";
            } else {
                echo "Pendiente";
            }
            ?>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="table-title-row">
        <div>
            <h1 class="page-title">Equipos registrados</h1>
            <p class="page-subtitle">
                Visualiza el inventario actual de dispositivos incorporados a la plataforma de monitoreo.
            </p>
        </div>

        <div class="actions">
            <a href="agregar_equipo.php" class="btn btn-primary">Agregar nuevo equipo</a>
            <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
        </div>
    </div>

    <div class="soft-box section-space">
        <p class="page-note">
            Esta sección concentra el inventario base del sistema. Cada registro representa un activo tecnológico
            que puede ser incorporado a futuras revisiones de conectividad y seguimiento operacional.
        </p>
    </div>

    <div class="page-card section-space">
        <h2 class="form-section-title">Buscar equipos</h2>

        <form method="GET" action="">
            <label>Buscar por nombre, IP, ubicación, tipo o sistema operativo</label>
            <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Ejemplo: laboratorio, windows, 192.168.1.10">

            <div class="actions">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="listar_equipos.php" class="btn btn-secondary">Limpiar filtro</a>
            </div>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>IP</th>
                <th>Ubicación</th>
                <th>Tipo</th>
                <th>Sistema Operativo</th>
                <th>Último estado</th>
                <th>Último chequeo</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>

            <?php
            if ($resultado && $resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    $estado = $fila["ultimo_estado"];
                    $ultimo_chequeo = $fila["ultimo_chequeo"];

                    $claseEstado = "warn";
                    $textoEstado = "Sin monitoreo";

                    if ($estado == "Activo") {
                        $claseEstado = "ok";
                        $textoEstado = "Activo";
                    } elseif ($estado == "Inactivo") {
                        $claseEstado = "down";
                        $textoEstado = "Inactivo";
                    }

                    echo "<tr data-equipo-id='" . $fila["id"] . "' data-equipo-nombre='" . htmlspecialchars($fila["nombre"], ENT_QUOTES) . "'>";
                    echo "<td>" . $fila["id"] . "</td>";
                    echo "<td><span class='highlight-value'>" . htmlspecialchars($fila["nombre"]) . "</span></td>";
                    echo "<td>" . htmlspecialchars($fila["ip"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["ubicacion"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["tipo"]) . "</td>";
                    echo "<td>" . htmlspecialchars($fila["sistema_operativo"]) . "</td>";
                    echo "<td class='estado-columna'><span class='badge " . $claseEstado . "'>" . $textoEstado . "</span></td>";
                    echo "<td class='chequeo-columna'>" . ($ultimo_chequeo ? htmlspecialchars($ultimo_chequeo) : "Sin registros") . "</td>";
                    echo "<td>" . htmlspecialchars($fila["descripcion"]) . "</td>";

                    echo "<td><div class='action-buttons'>";

                    if ($fila["metricas_habilitadas"] == 1 && !empty($fila["instancia_metricas"])) {
                        echo "<a class='btn-sm btn-monitor' href='dashboard_metricas.php?id=" . $fila["id"] . "'>Ver métricas</a> ";
                    }

                    echo "<a class='btn-sm btn-edit' href='editar_equipo.php?id=" . $fila["id"] . "'>Editar</a> ";
                    echo "<a class='btn-sm btn-delete' href='#' onclick=\"confirmarEliminacion(" . $fila["id"] . "); return false;\">Eliminar</a> ";
                    echo "</div></td>";

                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='10'>No se encontraron equipos</td></tr>";
            }
            ?>
        </table>
    </div>
</div>

<script>
function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'El equipo será eliminado del inventario.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'eliminar_equipo.php?id=' + id;
        }
    });
}

const estadosAnteriores = {};

function mostrarToastCambio(nombre, estado) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: estado === 'Activo' ? 'success' : 'warning',
        title: `${nombre} ahora está ${estado}`,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true
    });
}

function actualizarResumenSuperior(resumen) {
    const statActivos = document.getElementById("statActivos");
    const statInactivos = document.getElementById("statInactivos");
    const statSinMonitoreo = document.getElementById("statSinMonitoreo");
    const statEstadoGeneral = document.getElementById("statEstadoGeneral");

    if (statActivos) statActivos.textContent = resumen.activos;
    if (statInactivos) statInactivos.textContent = resumen.inactivos;
    if (statSinMonitoreo) statSinMonitoreo.textContent = resumen.sin_monitoreo;

    if (statEstadoGeneral) {
        if ((resumen.activos + resumen.inactivos + resumen.sin_monitoreo) === 0) {
            statEstadoGeneral.textContent = "Sin equipos";
        } else if (resumen.activos > 0 && resumen.inactivos === 0) {
            statEstadoGeneral.textContent = "Estable";
        } else if (resumen.inactivos > 0) {
            statEstadoGeneral.textContent = "Revisar";
        } else {
            statEstadoGeneral.textContent = "Pendiente";
        }
    }
}

async function actualizarEstadosEquipos() {
    try {
        const response = await fetch('obtener_estados_ajax.php');
        const data = await response.json();

        if (!data.ok) return;

        data.equipos.forEach(equipo => {
            const fila = document.querySelector(`tr[data-equipo-id="${equipo.id}"]`);
            if (!fila) return;

            const estadoColumna = fila.querySelector('.estado-columna');
            const chequeoColumna = fila.querySelector('.chequeo-columna');

            const estadoAnterior = estadosAnteriores[equipo.id];
            const estadoNuevo = equipo.estado;

            if (estadoColumna) {
                estadoColumna.innerHTML = `<span class="badge ${equipo.clase_estado}">${equipo.estado}</span>`;
            }

            if (chequeoColumna) {
                chequeoColumna.textContent = equipo.ultimo_chequeo;
            }

            if (estadoAnterior && estadoAnterior !== estadoNuevo) {
                mostrarToastCambio(equipo.nombre, estadoNuevo);
            }

            estadosAnteriores[equipo.id] = estadoNuevo;
        });

        if (data.resumen) {
            actualizarResumenSuperior(data.resumen);
        }

    } catch (error) {
        console.error("Error al actualizar estados:", error);
    }
}

function inicializarEstados() {
    const filas = document.querySelectorAll('tr[data-equipo-id]');

    filas.forEach(fila => {
        const equipoId = fila.getAttribute('data-equipo-id');
        const badge = fila.querySelector('.estado-columna .badge');

        if (equipoId && badge) {
            estadosAnteriores[equipoId] = badge.textContent.trim();
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    inicializarEstados();
    setInterval(actualizarEstadosEquipos, 5000);
});
</script>

<?php if (isset($_GET["eliminado"]) && $_GET["eliminado"] == 1) { ?>
<script>
Swal.fire({
    title: 'Eliminado',
    text: 'El equipo fue eliminado correctamente.',
    icon: 'success',
    confirmButtonText: 'Aceptar'
});
</script>
<?php } ?>

<?php include("footer.php"); ?>
