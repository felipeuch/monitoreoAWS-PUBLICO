<?php
include("conexion.php");
include("verificar_sesion.php");
include("bitacora_funciones.php");
$mensaje = "";

$mensaje = "";
$errores = [];

$nombre = "";
$ip = "";
$ubicacion = "";
$tipo = "";
$sistema_operativo = "";
$descripcion = "";
$metricas_habilitadas = 0;
$instancia_metricas = "";

if (isset($_GET["ip"]) && $_GET["ip"] !== "") {
    $ip = trim($_GET["ip"]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $ip = trim($_POST["ip"]);
    $ubicacion = trim($_POST["ubicacion"]);
    $tipo = trim($_POST["tipo"]);
    $sistema_operativo = trim($_POST["sistema_operativo"]);
    $descripcion = trim($_POST["descripcion"]);
    $metricas_habilitadas = isset($_POST["metricas_habilitadas"]) ? 1 : 0;
    $instancia_metricas = trim($_POST["instancia_metricas"]);

    if ($nombre === "") {
        $errores[] = "El nombre del equipo es obligatorio.";
    }

    if ($ip === "") {
        $errores[] = "La dirección IP es obligatoria.";
    } elseif (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $errores[] = "La dirección IP no tiene un formato válido.";
    } else {
        $ip_segura = $conn->real_escape_string($ip);
        $sql_ip = "SELECT id FROM equipos WHERE ip = '$ip_segura' LIMIT 1";
        $res_ip = $conn->query($sql_ip);

        if ($res_ip && $res_ip->num_rows > 0) {
            $errores[] = "Ya existe un equipo registrado con esa dirección IP.";
        }
    }

    if ($metricas_habilitadas == 1) {
        if ($instancia_metricas === "") {
            $errores[] = "Debes ingresar la instancia de métricas si las métricas están habilitadas.";
        } elseif (!preg_match('/^(\d{1,3}\.){3}\d{1,3}:\d+$/', $instancia_metricas)) {
            $errores[] = "La instancia de métricas debe tener formato IP:PUERTO, por ejemplo 172.31.46.87:9100.";
        } else {
            $partes = explode(":", $instancia_metricas);
            $ip_metrica = $partes[0];
            $puerto_metrica = $partes[1];

            if (!filter_var($ip_metrica, FILTER_VALIDATE_IP)) {
                $errores[] = "La IP de la instancia de métricas no es válida.";
            }

            if (!is_numeric($puerto_metrica) || (int)$puerto_metrica < 1 || (int)$puerto_metrica > 65535) {
                $errores[] = "El puerto de la instancia de métricas no es válido.";
            }
        }
    } else {
        $instancia_metricas = "";
    }

    if (empty($errores)) {
        $nombre_seguro = $conn->real_escape_string($nombre);
        $ip_segura = $conn->real_escape_string($ip);
        $ubicacion_segura = $conn->real_escape_string($ubicacion);
        $tipo_seguro = $conn->real_escape_string($tipo);
        $sistema_operativo_seguro = $conn->real_escape_string($sistema_operativo);
        $descripcion_segura = $conn->real_escape_string($descripcion);
        $instancia_metricas_segura = $conn->real_escape_string($instancia_metricas);

        $sql = "INSERT INTO equipos (nombre, ip, ubicacion, tipo, sistema_operativo, descripcion, metricas_habilitadas, instancia_metricas)
                VALUES ('$nombre_seguro', '$ip_segura', '$ubicacion_segura', '$tipo_seguro', '$sistema_operativo_seguro', '$descripcion_segura', '$metricas_habilitadas', '$instancia_metricas_segura')";

        if ($conn->query($sql) === TRUE) {
            if (function_exists('registrarBitacora')) {
                registrarBitacora($conn, "Equipos", "Agregar equipo", "Se agregó el equipo " . $nombre . " con IP " . $ip);
            }

            $mensaje = "Equipo agregado correctamente.";

            $nombre = "";
            $ip = "";
            $ubicacion = "";
            $tipo = "";
            $sistema_operativo = "";
            $descripcion = "";
            $metricas_habilitadas = 0;
            $instancia_metricas = "";
        } else {
            $errores[] = "Error al guardar el equipo: " . $conn->error;
        }
    }
}


include("header.php");
?>

<div class="page-card">
    <h1 class="page-title">Registro de equipos</h1>
    <p class="page-subtitle">
        Ingresa la información base del activo tecnológico para incorporarlo al inventario
        y habilitar futuras revisiones de monitoreo dentro de la plataforma.
    </p>

    <div class="actions">
        <a href="listar_equipos.php" class="btn btn-secondary">Ver equipos registrados</a>
        <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="message"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="message" style="background: rgba(239,68,68,0.12); color:#fecaca; border:1px solid rgba(239,68,68,0.25);">
            <?php foreach ($errores as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>  

    <div class="form-layout">
        <div class="page-card">
            <h2 class="form-section-title">Formulario de ingreso</h2>
            <p class="page-note" style="margin-bottom: 18px;">
                Completa los siguientes datos para registrar un nuevo dispositivo dentro del sistema.
            </p>

            <div class="form-divider"></div>

            <form method="POST" action="">
                <label>Nombre del equipo</label>
                <input type="text" name="nombre" required value="<?php echo htmlspecialchars($nombre); ?>" placeholder="Ejemplo: Servidor principal">

                <label>Dirección IP</label>
                <input type="text" id="ip" name="ip" required value="<?php echo htmlspecialchars($ip); ?>" placeholder="Ejemplo: 172.31.46.87" <?php echo isset($_GET["ip"]) && $_GET["ip"] !== "" ? "readonly" : ""; ?>>
                <label>Ubicación</label>
                <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($ubicacion); ?>" placeholder="Ejemplo:Sala de servidores">

                <label>Tipo de equipo</label>
                <input type="text" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>" placeholder="Ejemplo: Servidor">

                <label>Sistema operativo</label>
                <input type="text" name="sistema_operativo" value="<?php echo htmlspecialchars($sistema_operativo); ?>" placeholder="Linux, Windows">

                <label>Descripción</label>
                <textarea name="descripcion" placeholder="Ejemplo: Instancia principal de monitoreo" ><?php echo htmlspecialchars($descripcion); ?></textarea>
                 
                <label style="margin-top:16px;">
                <input type="checkbox" id="metricas_habilitadas" name="metricas_habilitadas" value="1" <?php echo $metricas_habilitadas ? "checked" : ""; ?>>
                Habilitar métricas para este equipo
                </label>                    

                <label>Instancia de métricas</label>
                <input type="text" id="instancia_metricas" name="instancia_metricas" value="<?php echo htmlspecialchars($instancia_metricas); ?>" placeholder="Ejemplo: 192.168.1.10:9100">
                <button type="submit" class="btn btn-primary">Guardar equipo</button>
          </form>
        </div>

        <div class="info-card">
            <h3>Recomendaciones de registro</h3>
            <p>
                Mantener un registro ordenado y consistente mejora la calidad del inventario y facilita
                el análisis posterior dentro del sistema de monitoreo.
            </p>

            <ul>
                <li>Usa nombres claros y fáciles de identificar.</li>
                <li>Registra IPs válidas y revisables por la plataforma.</li>
                <li>Especifica la ubicación física o lógica del equipo.</li>
                <li>Describe brevemente la función del dispositivo.</li>
                <li>Mantén consistencia en los tipos y sistemas operativos.</li>
            </ul>

            <div class="form-divider"></div>

            <h3>Uso dentro del proyecto</h3>
            <p>
                Cada equipo registrado podrá formar parte del inventario institucional y ser incorporado
                a futuras ejecuciones de monitoreo, permitiendo construir una visión centralizada de los
                activos tecnológicos supervisados.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const chkMetricas = document.getElementById("metricas_habilitadas");
    const inputIp = document.getElementById("ip");
    const inputInstancia = document.getElementById("instancia_metricas");

    // Detecta si la IP venía prellenada desde descubrir_equipos.php
    const ipVeniaPrellenada = inputIp.hasAttribute("readonly");

    function actualizarInstanciaMetricas() {
        const ip = inputIp.value.trim();

        if (chkMetricas.checked && ip !== "") {
            inputInstancia.value = ip + ":9100";

            // Bloquea la IP
            inputIp.readOnly = true;

            // Bloquea la instancia para que no la puedan borrar
            inputInstancia.readOnly = true;
        } else {
            // Limpia la instancia si se desmarca métricas
            inputInstancia.value = "";

            // Permite editar la instancia nuevamente
            inputInstancia.readOnly = false;

            // Solo permite editar la IP si NO venía bloqueada desde descubrir equipos
            if (!ipVeniaPrellenada) {
                inputIp.readOnly = false;
            }
        }
    }

    chkMetricas.addEventListener("change", actualizarInstanciaMetricas);
    inputIp.addEventListener("input", actualizarInstanciaMetricas);

    actualizarInstanciaMetricas();
});
</script>

<?php include("footer.php"); ?>
        
