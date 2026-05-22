

$nsaje = ""

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $ip = $_POST["ip"];
    $ubicacion = $_POST["ubicacion"];
    $tipo = $_POST["tipo"];
    $sistema_operativo = $_POST["sistema_operativo"];
    $descripcion = $_POST["descripcion"];

    $sql = "INSERT INTO equipos (nombre, ip, ubicacion, tipo, sistema_operativo, descripcion)
            VALUES ('$nombre', '$ip', '$ubicacion', '$tipo', '$sistema_operativo', '$descripcion')";

    if ($conn->query($sql) === TRUE) {
        $mensaje = "Equipo agregado correctamente";
    } else {
        $mensaje = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Equipo</title>
</head>
<body>
    <h1>Agregar Equipo</h1>

    <p><?php echo $mensaje; ?></p>

    <form method="POST" action="">
        <label>Nombre:</label><br>
        <input
