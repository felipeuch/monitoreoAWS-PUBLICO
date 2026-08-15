<?php
include("verificar_sesion.php");
include("conexion.php");

require __DIR__ . '/vendor/autoload.php';

use Aws\Ec2\Ec2Client;

$rango = "";
$resultados = [];

/* =========================
   FUNCIÓN: OBTENER NOMBRE AWS POR IP PRIVADA
========================= */
function obtenerNombreEC2PorIP($ip) {
    try {
        $ec2 = new Ec2Client([
            'version' => 'latest',
            'region'  => 'us-east-2'
        ]);

        $result = $ec2->describeInstances([
            'Filters' => [
                [
                    'Name' => 'private-ip-address',
                    'Values' => [$ip]
                ]
            ]
        ]);

        foreach ($result['Reservations'] as $reservation) {
            foreach ($reservation['Instances'] as $instance) {
                if (!empty($instance['Tags'])) {
                    foreach ($instance['Tags'] as $tag) {
                        if ($tag['Key'] === 'Name') {
                            return $tag['Value'];
                        }
                    }
                }
            }
        }

        return null;
    } catch (Exception $e) {
        return null;
    }
}

/* =========================
   FUNCIÓN: OBTENER NOMBRE DETECTADO
   1. Primero intenta AWS EC2 Tag Name
   2. Luego intenta DNS inverso
   3. Si no encuentra, muestra No detectado
========================= */
function obtenerNombreDetectado($ip) {
    $nombre_aws = obtenerNombreEC2PorIP($ip);

    if (!empty($nombre_aws)) {
        return $nombre_aws;
    }

    $nombre_dns = gethostbyaddr($ip);

    if ($nombre_dns && $nombre_dns !== $ip) {
        return $nombre_dns;
    }

    return "No detectado";
}

/* Detectar IP del servidor y sugerir subred /24 */
$ip_servidor = $_SERVER["SERVER_ADDR"] ?? "";
$rango_sugerido = "";

if (!empty($ip_servidor) && filter_var($ip_servidor, FILTER_VALIDATE_IP)) {
    $partes_ip = explode(".", $ip_servidor);
    if (count($partes_ip) === 4) {
        $rango_sugerido = $partes_ip[0] . "." . $partes_ip[1] . "." . $partes_ip[2] . ".0/24";
    }
}

$rango = $rango_sugerido;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rango = trim($_POST["rango"]);

    if (!empty($rango)) {
        $comando = "nmap -sn -n -T4 " . escapeshellarg($rango);
        $salida = shell_exec($comando);

        if ($salida) {
            preg_match_all('/Nmap scan report for ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/', $salida, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $ip) {
                    $ip_segura = $conn->real_escape_string($ip);
                    $nombre_detectado = obtenerNombreDetectado($ip);

                    $sql = "SELECT id, nombre FROM equipos WHERE ip = '$ip_segura' LIMIT 1";
                    $res = $conn->query($sql);

                    if ($res && $res->num_rows > 0) {
                        $fila = $res->fetch_assoc();
                        $resultados[] = [
                            "ip" => $ip,
                            "registrado" => true,
                            "nombre" => $fila["nombre"],
                            "nombre_detectado" => $nombre_detectado,
                            "id" => $fila["id"]
                        ];
                    } else {
                        $resultados[] = [
                            "ip" => $ip,
                            "registrado" => false,
                            "nombre" => null,
                            "nombre_detectado" => $nombre_detectado,
                            "id" => null
                        ];
                    }
                }
            }
        }
    }
}

include("header.php");
?>

<div class="page-card">
    <h1 class="page-title">Descubrir equipos en red</h1>
    <p class="page-subtitle">
        Escanea una subred para detectar hosts activos y verificar si ya se encuentran registrados
        en la plataforma de monitoreo.
    </p>

    <div class="actions">
        <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
        <a href="listar_equipos.php" class="btn btn-secondary">Ver equipos registrados</a>
    </div>

    <div class="page-card section-space">
        <h2 class="form-section-title">Escaneo de red</h2>

        <form method="POST" action="">
            <label>Rango o subred a escanear</label>
            <input type="text" name="rango" value="<?php echo htmlspecialchars($rango); ?>" placeholder="Ejemplo: 172.31.47.0/24" required>
            <p class="page-note" style="margin-top:-8px; margin-bottom:18px;">
               Se ha sugerido automáticamente la subred del servidor actual para acelerar el descubrimiento.
            </p>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Escanear red</button>
            </div>
        </form>
    </div>

    <?php if (!empty($rango)): ?>
        <div class="soft-box section-space">
            <p class="page-note">
                Resultado del escaneo para el rango: <strong><?php echo htmlspecialchars($rango); ?></strong>
            </p>
        </div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>IP detectada</th>
                <th>Nombre detectado</th>
                <th>Estado en la plataforma</th>
                <th>Nombre asociado</th>
                <th>Acción</th>
            </tr>

            <?php
            if (!empty($resultados)) {
                foreach ($resultados as $item) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item["ip"]) . "</td>";
                    echo "<td>" . htmlspecialchars($item["nombre_detectado"]) . "</td>";

                    if ($item["registrado"]) {
                        echo "<td><span class='badge ok'>Registrado</span></td>";
                        echo "<td>" . htmlspecialchars($item["nombre"]) . "</td>";
                        echo "<td><a class='btn-sm btn-edit' href='editar_equipo.php?id=" . urlencode($item["id"]) . "'>Ver / Editar</a></td>";
                    } else {
                        echo "<td><span class='badge warn'>Nuevo</span></td>";
                        echo "<td>No registrado</td>";

                        $nombre_param = ($item["nombre_detectado"] !== "No detectado") ? $item["nombre_detectado"] : "";

                        echo "<td><a class='btn-sm btn-monitor' href='agregar_equipo_descubierto.php?ip=" . urlencode($item["ip"]) . "&nombre=" . urlencode($nombre_param) . "'>Agregar</a></td>";
                    }

                    echo "</tr>";
                }
            } elseif (!empty($rango)) {
                echo "<tr><td colspan='5'>No se detectaron hosts activos en el rango indicado.</td></tr>";
            } else {
                echo "<tr><td colspan='5'>Aún no se ha ejecutado ningún escaneo.</td></tr>";
            }
            ?>
        </table>
    </div>
</div>

<?php include("footer.php"); ?>
