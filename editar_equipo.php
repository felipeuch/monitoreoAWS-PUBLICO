<?php
include("verificar_sesion.php");
include("conexion.php");
include("bitacora_funciones.php");

if (!isset($_GET["id"]) || !is_numeric($_GET["id"]) || (int)$_GET["id"] <= 0) {
    die("ID no válido.");
}

$id = (int) $_GET["id"];

$mensaje = "";
$errores = [];

$sql = "SELECT * FROM equipos WHERE id = '$id'";
$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows == 0) {
    die("Equipo no encontrado.");
}

$equipo = $resultado->fetch_assoc();

$nombre_valor = $_SERVER["REQUEST_METHOD"] == "POST" ? $nombre : $equipo["nombre"];
$ip_valor = $_SERVER["REQUEST_METHOD"] == "POST" ? $ip : $equipo["ip"];
$ubicacion_valor = $_SERVER["REQUEST_METHOD"] == "POST" ? $ubicacion : $equipo["ubicacion"];
$tipo_valor = $_SERVER["REQUEST_METHOD"] == "POST" ? $tipo : $equipo["tipo"];
$sistema_operativo_valor = $_SERVER["REQUEST_METHOD"] == "POST" ? $sistema_operativo : $equipo["sistema_operativo"];
$descripcion_valor = $_SERVER["REQUEST_METHOD"] == "POST" ? $descripcion : $equipo["descripcion"];
$metricas_habilitadas_valor = $_SERVER["REQUEST_METHOD"] == "POST" ? $metricas_habilitadas : $equipo["metricas_habilitadas"];
$instancia_metricas_valor = $_SERVER["REQUEST_METHOD"] == "POST" ? $instancia_metricas : $equipo["instancia_metricas"];


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
        $sql_ip = "SELECT id FROM equipos WHERE ip = '$ip_segura' AND id != '$id' LIMIT 1";
        $res_ip = $conn->query($sql_ip);

        if ($res_ip && $res_ip->num_rows > 0) {
            $errores[] = "Ya existe otro equipo registrado con esa dirección IP.";
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

        $update = "UPDATE equipos 
                   SET nombre='$nombre_seguro',
                       ip='$ip_segura',
                       ubicacion='$ubicacion_segura',
                       tipo='$tipo_seguro',
                       sistema_operativo='$sistema_operativo_seguro',
                       descripcion='$descripcion_segura',
                       metricas_habilitadas='$metricas_habilitadas',
                       instancia_metricas='$instancia_metricas_segura'
                   WHERE id='$id'";

        if ($conn->query($update) === TRUE) {
            $mensaje = "Equipo actualizado correctamente.";
            registrarBitacora($conn, "Equipos", "Editar equipo", "Se actualizó el equipo " . $nombre . " con IP " . $ip);

            $sql = "SELECT * FROM equipos WHERE id = '$id'";
            $resultado = $conn->query($sql);
            $equipo = $resultado->fetch_assoc();
        } else {
            $errores[] = "Error al actualizar el equipo: " . $conn->error;
        }
    }
}


include("header.php");
?>

<div class="page-card">
    <h1 class="page-title">Editar equipo</h1>
    <p class="page-subtitle">
        Modifica la información del equipo seleccionado dentro del inventario de la plataforma.
    </p>

    <div class="actions">
        <a href="listar_equipos.php" class="btn btn-secondary">Volver a equipos</a>
        <a href="index.php" class="btn btn-secondary">Ir al inicio</a>
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
            <h2 class="form-section-title">Actualización de datos</h2>
            <p class="page-note" style="margin-bottom: 18px;">
                Edita los datos necesarios y guarda los cambios para actualizar el inventario.
            </p>

            <div class="form-divider"></div>

            <form method="POST" action="">
              <label>Nombre del equipo</label>
                <input type="text" name="nombre" required value="<?php echo htmlspecialchars($nombre_valor); ?>" placeholder="Ejemplo: Servidor principal">

                <label>Dirección IP</label>
                <input type="text" name="ip" required value="<?php echo htmlspecialchars($ip_valor); ?>" placeholder="Ejemplo: 172.31.46.87">

                <label>Ubicación</label>
                <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($ubicacion_valor); ?>" placeholder="Ejemplo:Sala de servidores">

                <label>Tipo de equipo</label>
                <input type="text" name="tipo" value="<?php echo htmlspecialchars($tipo_valor); ?>" placeholder="Ejemplo: Servidor">

                <label>Sistema operativo</label>
                <input type="text" name="sistema_operativo" value="<?php echo htmlspecialchars($sistema_operativo_valor); ?>" placeholder="Linux, Windows">

                <label>Descripción</label>
                <textarea name="descripcion" placeholder="Ejemplo: Instancia principal de monitoreo" ><?php echo htmlspecialchars($descripcion_valor); ?></textarea>
                 
                <label style="margin-top:16px;">
                  <input type="checkbox" name="metricas_habilitadas" value="1" <?php echo ($metricas_habilitadas_valor == 1) ? "checked" : ""; ?>>
                   Habilitar métricas para este equipo
                </label>                    

                <label>Instancia de métricas</label>
                 <input type="text" name="instancia_metricas" value="<?php echo htmlspecialchars($instancia_metricas_valor); ?>" placeholder="Ejemplo: 172.31.46.87:9100">
                <button type="submit" class="btn btn-primary">Guardar equipo</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </form>
        </div>

        <div class="info-card">
            <h3>Información del registro</h3>
            <p>
                La edición permite mantener actualizado el inventario institucional y mejorar la calidad
                de los datos que usa la plataforma para futuras revisiones.
            </p>

            <ul>
                <li>ID del equipo: <?php echo $equipo['id']; ?></li>
                <li>Revisa que la IP siga siendo válida.</li>
                <li>Mantén nombres y tipos consistentes.</li>
                <li>Evita dejar información importante incompleta.</li>
            </ul>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
