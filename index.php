<?php
include("verificar_sesion.php");
include("conexion.php");

/* Total de equipos */
$sql_total_equipos = "SELECT COUNT(*) AS total FROM equipos";
$res_total_equipos = $conn->query($sql_total_equipos);
$total_equipos = ($res_total_equipos) ? $res_total_equipos->fetch_assoc()["total"] : 0;

/* Total de revisiones */
$sql_total_revisiones = "SELECT COUNT(*) AS total FROM monitoreo";
$res_total_revisiones = $conn->query($sql_total_revisiones);
$total_revisiones = ($res_total_revisiones) ? $res_total_revisiones->fetch_assoc()["total"] : 0;

/* Último monitoreo por equipo */
$sql_estados = "
    SELECT e.id, e.nombre, m.estado, m.ultimo_chequeo
    FROM equipos e
    LEFT JOIN monitoreo m
        ON m.id = (
            SELECT m2.id
            FROM monitoreo m2
            WHERE m2.equipo_id = e.id
            ORDER BY m2.ultimo_chequeo DESC, m2.id DESC
            LIMIT 1
        )
";
$res_estados = $conn->query($sql_estados);

$total_activos = 0;
$total_inactivos = 0;
$total_sin_monitoreo = 0;
$ultimo_chequeo = "Sin registros";
$ultimo_equipo = "Sin datos";
$ultima_ejecucion = "Sin ejecuciones";
$estado_monitoreo = "Sin datos";
$badge_monitoreo = "warn";
$porcentaje_activos = 0;
$porcentaje_inactivos = 0;
$porcentaje_sin_monitoreo = 0;

if ($res_estados && $res_estados->num_rows > 0) {
    while ($fila = $res_estados->fetch_assoc()) {
        if ($fila["estado"] == "Activo") {
            $total_activos++;
        } elseif ($fila["estado"] == "Inactivo") {
            $total_inactivos++;
        } else {
            $total_sin_monitoreo++;
        }
    }
    $res_estados->data_seek(0);
}

/* Último chequeo global */
$sql_ultimo = "
    SELECT equipos.nombre, monitoreo.ultimo_chequeo
    FROM monitoreo
    INNER JOIN equipos ON monitoreo.equipo_id = equipos.id
    ORDER BY monitoreo.ultimo_chequeo DESC, monitoreo.id DESC
    LIMIT 1
";
$res_ultimo = $conn->query($sql_ultimo);

if ($res_ultimo && $res_ultimo->num_rows > 0) {
    $fila_ultimo = $res_ultimo->fetch_assoc();
    $ultimo_chequeo = $fila_ultimo["ultimo_chequeo"];
    $ultimo_equipo = $fila_ultimo["nombre"];
    $ultima_ejecucion = $fila_ultimo["ultimo_chequeo"];

    $timestamp_ultima = strtotime($ultima_ejecucion);
    $timestamp_actual = time();
    $diferencia_minutos = ($timestamp_actual - $timestamp_ultima) / 60;

    if ($diferencia_minutos <= 15) {
        $estado_monitoreo = "Al día";
        $badge_monitoreo = "ok";
    } else {
        $estado_monitoreo = "Atrasado";
        $badge_monitoreo = "down";
    }
}

$extra_css = "graficos.css";
if ($total_equipos > 0) {
    $porcentaje_activos = ($total_activos / $total_equipos) * 100;
    $porcentaje_inactivos = ($total_inactivos / $total_equipos) * 100;
    $porcentaje_sin_monitoreo = ($total_sin_monitoreo / $total_equipos) * 100;
}

$sql_ultimas_actividades = "
    SELECT monitoreo.id, equipos.nombre, equipos.ip, monitoreo.estado, monitoreo.ultimo_chequeo, monitoreo.observacion
    FROM monitoreo
    INNER JOIN equipos ON monitoreo.equipo_id = equipos.id
    ORDER BY monitoreo.ultimo_chequeo DESC, monitoreo.id DESC
    LIMIT 5
";
$res_ultimas_actividades = $conn->query($sql_ultimas_actividades);

include("header.php");
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total de equipos</h3>
        <div class="stat-number"><?php echo $total_equipos; ?></div>
    </div>

    <div class="stat-card">
        <h3>Equipos activos</h3>
        <div class="stat-number"><?php echo $total_activos; ?></div>
    </div>

    <div class="stat-card">
        <h3>Equipos inactivos</h3>
        <div class="stat-number"><?php echo $total_inactivos; ?></div>
    </div>

    <div class="stat-card">
        <h3>Sin monitoreo</h3>
        <div class="stat-number"><?php echo $total_sin_monitoreo; ?></div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total de revisiones</h3>
        <div class="stat-number"><?php echo $total_revisiones; ?></div>
    </div>

    <div class="stat-card">
        <h3>Último chequeo</h3>
        <div class="stat-number" style="font-size: 1rem;">
            <?php echo htmlspecialchars($ultimo_chequeo); ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Último equipo revisado</h3>
        <div class="stat-number" style="font-size: 1rem;">
            <?php echo htmlspecialchars($ultimo_equipo); ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Estado general</h3>
        <div class="stat-number" style="font-size: 1rem;">
            <?php
            if ($total_equipos == 0) {
                echo "Sin equipos";
            } elseif ($total_inactivos > 0) {
                echo "Revisar";
            } elseif ($total_activos > 0) {
                echo "Operativo";
            } else {
                echo "Pendiente";
            }
            ?>
        </div>
    </div>
</div>

<div class="page-card chart-card">
    <div class="table-title-row">
        <div>
            <h2 class="page-title" style="font-size:1.8rem;">Distribución del estado de equipos</h2>
            <p class="page-subtitle" style="margin-bottom:0;">
                Visualización resumida del estado actual de los equipos registrados en la plataforma.
            </p>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-row">
            <div class="chart-label">Equipos activos</div>
            <div class="chart-bar-wrap">
                <div class="chart-bar chart-bar-active" style="width: <?php echo $porcentaje_activos; ?>%;"></div>
            </div>
            <div class="chart-value"><?php echo $total_activos; ?></div>
        </div>

        <div class="chart-row">
            <div class="chart-label">Equipos inactivos</div>
            <div class="chart-bar-wrap">
                <div class="chart-bar chart-bar-inactive" style="width: <?php echo $porcentaje_inactivos; ?>%;"></div>
            </div>
            <div class="chart-value"><?php echo $total_inactivos; ?></div>
        </div>

        <div class="chart-row">
            <div class="chart-label">Sin monitoreo</div>
            <div class="chart-bar-wrap">
                <div class="chart-bar chart-bar-pending" style="width: <?php echo $porcentaje_sin_monitoreo; ?>%;"></div>
            </div>
            <div class="chart-value"><?php echo $total_sin_monitoreo; ?></div>
        </div>
    </div>

    <div class="chart-legend">
        <div class="chart-legend-item">
            <span class="chart-dot chart-dot-active"></span>
            <span>Activos</span>
        </div>
        <div class="chart-legend-item">
            <span class="chart-dot chart-dot-inactive"></span>
            <span>Inactivos</span>
        </div>
        <div class="chart-legend-item">
            <span class="chart-dot chart-dot-pending"></span>
            <span>Sin monitoreo</span>
        </div>
    </div>
</div>

<section class="page-card" style="margin-bottom:28px;">
    <div class="table-title-row">
        <div>
            <h2 class="page-title" style="font-size:1.8rem;">Últimas actividades</h2>
            <p class="page-subtitle" style="margin-bottom:0;">
                Últimos registros generados por el sistema de monitoreo sobre los equipos incorporados a la plataforma.
            </p>
        </div>

        <div class="actions">
            <a href="ver_monitoreo.php" class="btn btn-secondary">Ver historial completo</a>
        </div>
    </div>

    <div class="activity-list" style="margin-top:22px;">
        <?php
        if ($res_ultimas_actividades && $res_ultimas_actividades->num_rows > 0) {
            while ($actividad = $res_ultimas_actividades->fetch_assoc()) {
                $claseEstado = "warn";
                if ($actividad["estado"] == "Activo") {
                    $claseEstado = "ok";
                } elseif ($actividad["estado"] == "Inactivo") {
                    $claseEstado = "down";
                }

                echo "<div class='activity-item'>";
                echo "  <div class='activity-main'>";
                echo "      <strong>" . htmlspecialchars($actividad["nombre"]) . "</strong>";
                echo "      <span>IP: " . htmlspecialchars($actividad["ip"]) . "</span>";
                echo "      <div class='activity-note'>" . htmlspecialchars($actividad["observacion"]) . "</div>";
                echo "  </div>";
                echo "  <div class='activity-time'>" . htmlspecialchars($actividad["ultimo_chequeo"]) . "</div>";
                echo "  <div><span class='badge " . $claseEstado . "'>" . htmlspecialchars($actividad["estado"]) . "</span></div>";
                echo "</div>";
            }
        } else {
            echo "<div class='soft-box'>";
            echo "  <p class='page-note'>Aún no existen actividades registradas en el sistema de monitoreo.</p>";
            echo "</div>";
        }
        ?>
    </div>
</section>

<div class="page-card" style="margin-bottom: 28px;">
    <div class="table-title-row">
        <div>
            <h2 class="page-title" style="font-size:1.8rem;">Automatización del monitoreo</h2>
            <p class="page-subtitle" style="margin-bottom:0;">
                El sistema ejecuta revisiones automáticas de conectividad mediante una tarea programada en Linux.
            </p>
        </div>

        <div class="actions">
            <a href="monitorear.php" class="btn btn-primary">Ejecutar monitoreo ahora</a>
            <a href="ver_monitoreo.php" class="btn btn-secondary">Ver historial</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-top:22px;">
        <div class="soft-box">
            <h3 style="margin-bottom:10px;">Estado del monitoreo</h3>
            <p class="page-note">
                <span class="badge <?php echo $badge_monitoreo; ?>"><?php echo $estado_monitoreo; ?></span>
            </p>
        </div>

        <div class="soft-box">
            <h3 style="margin-bottom:10px;">Frecuencia</h3>
            <p class="page-note">Cada 10 minutos</p>
        </div>

        <div class="soft-box">
            <h3 style="margin-bottom:10px;">Motor de ejecución</h3>
            <p class="page-note">Cron + PHP</p>
        </div>

        <div class="soft-box">
            <h3 style="margin-bottom:10px;">Última ejecución registrada</h3>
            <p class="page-note"><?php echo htmlspecialchars($ultima_ejecucion); ?></p>
        </div>
    </div>
</div>

<section class="page-card" style="margin-bottom: 28px;">
    <div class="hero-stack-responsive">
        <div>
            <h1 class="page-title" style="font-size: 2.5rem; line-height:1.15; margin-bottom:14px;">
                Plataforma web de supervisión y monitoreo para entornos educacionales
            </h1>

            <p class="page-subtitle" style="font-size:1.05rem; max-width:760px;">
                EduNexa Cloud Monitoring es una solución desarrollada para centralizar la administración
                y supervisión de equipos tecnológicos en instituciones educacionales. La plataforma permite
                registrar dispositivos, ejecutar verificaciones de conectividad, almacenar resultados históricos
                y establecer una base escalable para futuras integraciones avanzadas dentro del ecosistema AWS.
            </p>

            <div class="actions" style="margin-top:22px;">
                <a href="agregar_equipo.php" class="btn btn-primary">Registrar nuevo equipo</a>
                <a href="listar_equipos.php" class="btn btn-secondary">Ver inventario</a>
                <a href="ver_monitoreo.php" class="btn btn-secondary">Consultar monitoreo</a>
            </div>
        </div>

        <div class="page-card hero-status-card">
            <h3 style="margin-bottom:18px;">Estado general de la solución</h3>

            <div style="display:grid; gap:14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.04); padding:14px; border-radius:14px;">
                    <div>
                        <strong>Servidor principal</strong><br>
                        <span style="color:#94a3b8; font-size:0.92rem;">Instancia EC2 Linux en AWS</span>
                    </div>
                    <span class="badge ok">Operativo</span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.04); padding:14px; border-radius:14px;">
                    <div>
                        <strong>Aplicación web</strong><br>
                        <span style="color:#94a3b8; font-size:0.92rem;">Apache + PHP</span>
                    </div>
                    <span class="badge ok">Activa</span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.04); padding:14px; border-radius:14px;">
                    <div>
                        <strong>Base de datos</strong><br>
                        <span style="color:#94a3b8; font-size:0.92rem;">MariaDB local</span>
                    </div>
                    <span class="badge ok">Disponible</span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.04); padding:14px; border-radius:14px;">
                    <div>
                        <strong>Inventario actual</strong><br>
                        <span style="color:#94a3b8; font-size:0.92rem;"><?php echo $total_equipos; ?> equipos registrados</span>
                    </div>
                    <span class="badge warn">Vigente</span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.04); padding:14px; border-radius:14px;">
                    <div>
                        <strong>Evolución futura</strong><br>
                        <span style="color:#94a3b8; font-size:0.92rem;">Alertas, automatización e IA</span>
                    </div>
                    <span class="badge warn">Planificado</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:28px;">
    <div class="page-card">
        <h3 style="margin-bottom:10px;">Gestión centralizada</h3>
        <p class="page-subtitle" style="margin-bottom:0;">
            Permite registrar y administrar equipos tecnológicos desde una única interfaz web,
            facilitando el control operativo del entorno monitoreado.
        </p>
    </div>

    <div class="page-card">
        <h3 style="margin-bottom:10px;">Monitoreo básico real</h3>
        <p class="page-subtitle" style="margin-bottom:0;">
            Ejecuta verificaciones de conectividad sobre direcciones IP registradas y almacena
            los resultados en un historial consultable.
        </p>
    </div>

    <div class="page-card">
        <h3 style="margin-bottom:10px;">Escalabilidad en AWS</h3>
        <p class="page-subtitle" style="margin-bottom:0;">
            La arquitectura actual permite evolucionar hacia integraciones con servicios adicionales
            de AWS sin rediseñar completamente la solución.
        </p>
    </div>

    <div class="page-card">
        <h3 style="margin-bottom:10px;">Base para innovación</h3>
        <p class="page-subtitle" style="margin-bottom:0;">
            El sistema puede crecer hacia alertas automatizadas, dashboards mejorados, métricas avanzadas
            y asistentes inteligentes.
        </p>
    </div>
</section>

<section class="page-card" style="margin-bottom:28px;">
    <h2 class="page-title" style="font-size:1.8rem;">¿Qué hace actualmente la plataforma?</h2>
    <p class="page-subtitle">
        La versión actual del sistema está enfocada en ofrecer una base sólida, funcional y fácil de explicar.
        Sus capacidades principales permiten demostrar el concepto de monitoreo web aplicado a infraestructura
        tecnológica dentro de un contexto educacional.
    </p>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:18px;">
        <div style="background:rgba(255,255,255,0.04); border-radius:16px; padding:18px;">
            <h3 style="margin-bottom:10px;">Registro de equipos</h3>
            <p style="color:#cbd5e1; line-height:1.6;">
                Permite incorporar dispositivos mediante nombre, IP, ubicación, tipo de equipo,
                sistema operativo y observaciones adicionales.
            </p>
        </div>

        <div style="background:rgba(255,255,255,0.04); border-radius:16px; padding:18px;">
            <h3 style="margin-bottom:10px;">Consulta de inventario</h3>
            <p style="color:#cbd5e1; line-height:1.6;">
                Visualiza en forma tabular los equipos registrados, generando un inventario básico
                útil para administración y seguimiento.
            </p>
        </div>

        <div style="background:rgba(255,255,255,0.04); border-radius:16px; padding:18px;">
            <h3 style="margin-bottom:10px;">Ejecución de monitoreo</h3>
            <p style="color:#cbd5e1; line-height:1.6;">
                Realiza revisiones de conectividad hacia los equipos registrados y determina un estado
                de disponibilidad básico.
            </p>
        </div>

        <div style="background:rgba(255,255,255,0.04); border-radius:16px; padding:18px;">
            <h3 style="margin-bottom:10px;">Historial de resultados</h3>
            <p style="color:#cbd5e1; line-height:1.6;">
                Guarda los chequeos ejecutados para poder consultar evidencia histórica y mostrar
                el comportamiento de los dispositivos monitoreados.
            </p>
        </div>
    </div>
</section>

<section class="page-card" style="margin-bottom:28px;">
    <h2 class="page-title" style="font-size:1.8rem;">Flujo operativo del sistema</h2>
    <p class="page-subtitle">
        El funcionamiento general de la solución sigue un proceso simple y ordenado, pensado para facilitar
        la administración y visualización del estado de los equipos.
    </p>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:18px;">
        <div style="padding:18px; border-radius:16px; background:rgba(37,99,235,0.14); border:1px solid rgba(56,189,248,0.15);">
            <strong style="font-size:1.1rem;">1. Registro</strong>
            <p style="margin-top:10px; color:#dbeafe; line-height:1.6;">
                El administrador ingresa un nuevo equipo en la plataforma.
            </p>
        </div>

        <div style="padding:18px; border-radius:16px; background:rgba(37,99,235,0.14); border:1px solid rgba(56,189,248,0.15);">
            <strong style="font-size:1.1rem;">2. Validación</strong>
            <p style="margin-top:10px; color:#dbeafe; line-height:1.6;">
                El sistema utiliza los datos registrados para preparar futuras revisiones.
            </p>
        </div>

        <div style="padding:18px; border-radius:16px; background:rgba(37,99,235,0.14); border:1px solid rgba(56,189,248,0.15);">
            <strong style="font-size:1.1rem;">3. Monitoreo</strong>
            <p style="margin-top:10px; color:#dbeafe; line-height:1.6;">
                Se ejecuta un chequeo de conectividad y se determina el estado del equipo.
            </p>
        </div>

        <div style="padding:18px; border-radius:16px; background:rgba(37,99,235,0.14); border:1px solid rgba(56,189,248,0.15);">
            <strong style="font-size:1.1rem;">4. Almacenamiento</strong>
            <p style="margin-top:10px; color:#dbeafe; line-height:1.6;">
                El resultado queda guardado en base de datos como evidencia operativa.
            </p>
        </div>

        <div style="padding:18px; border-radius:16px; background:rgba(37,99,235,0.14); border:1px solid rgba(56,189,248,0.15);">
            <strong style="font-size:1.1rem;">5. Visualización</strong>
            <p style="margin-top:10px; color:#dbeafe; line-height:1.6;">
                El usuario consulta el historial y el inventario desde la web.
            </p>
        </div>
    </div>
</section>

<section class="two-col-mobile-stack" style="margin-bottom:28px;">
    <div class="page-card">
        <h2 class="page-title" style="font-size:1.6rem;">Áreas de valor del proyecto</h2>
        <ul style="padding-left:20px; color:#cbd5e1; line-height:1.9;">
            <li>Apoya la supervisión de activos tecnológicos institucionales.</li>
            <li>Centraliza información operativa en una interfaz web accesible.</li>
            <li>Facilita futuras decisiones de mejora en infraestructura TI.</li>
            <li>Sirve como base para evolución hacia monitoreo avanzado.</li>
            <li>Entrega una propuesta escalable dentro de AWS.</li>
        </ul>
    </div>

    <div class="page-card">
        <h2 class="page-title" style="font-size:1.6rem;">Proyección futura</h2>
        <ul style="padding-left:20px; color:#cbd5e1; line-height:1.9;">
            <li>Automatización de revisiones mediante tareas programadas.</li>
            <li>Alertas por correo ante fallos o indisponibilidad.</li>
            <li>Paneles gráficos y visualización estadística.</li>
            <li>Integración con más servicios nativos de AWS.</li>
            <li>Incorporación de inteligencia artificial con Amazon Bedrock.</li>
        </ul>
    </div>
</section>

<section class="page-card" style="margin-bottom:28px;">
    <h2 class="page-title" style="font-size:1.8rem;">Visión estratégica</h2>
    <p class="page-subtitle" style="margin-bottom:0;">
        EduNexa Cloud Monitoring busca transformarse en una plataforma web de referencia para la supervisión
        tecnológica en instituciones educacionales, partiendo desde una arquitectura simple pero bien estructurada.
        Esta primera versión demuestra viabilidad técnica, orden funcional y potencial de crecimiento, sentando
        las bases para futuras capacidades de automatización, observabilidad avanzada e inteligencia aplicada.
    </p>
</section>

<section class="page-card">
    <h2 class="page-title" style="font-size:1.8rem;">Accesos rápidos</h2>
    <p class="page-subtitle">
        Utiliza estos accesos para operar rápidamente los módulos principales del sistema.
    </p>

    <div class="actions">
        <a href="agregar_equipo.php" class="btn btn-primary">Agregar equipo</a>
        <a href="listar_equipos.php" class="btn btn-secondary">Ver equipos</a>
        <a href="monitorear.php" class="btn btn-secondary">Ejecutar monitoreo</a>
        <a href="ver_monitoreo.php" class="btn btn-secondary">Ver historial</a>
    </div>
</section>

<?php include("footer.php"); ?>
