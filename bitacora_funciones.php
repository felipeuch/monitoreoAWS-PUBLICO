<?php
function registrarBitacora($conn, $modulo, $accion, $descripcion)
{
    $fecha_evento = date("Y-m-d H:i:s");

    $usuario_id = isset($_SESSION["usuario_id"]) ? (int) $_SESSION["usuario_id"] : "NULL";
    $usuario_nombre = isset($_SESSION["usuario_nombre"]) ? $conn->real_escape_string($_SESSION["usuario_nombre"]) : "Sistema";

    $modulo = $conn->real_escape_string($modulo);
    $accion = $conn->real_escape_string($accion);
    $descripcion = $conn->real_escape_string($descripcion);

    $ip_origen = $_SERVER["REMOTE_ADDR"] ?? "";
    $ip_origen = $conn->real_escape_string($ip_origen);

    if ($usuario_id === "NULL") {
        $sql = "INSERT INTO bitacora_sistema (fecha_evento, usuario_id, usuario_nombre, modulo, accion, descripcion, ip_origen)
                VALUES ('$fecha_evento', NULL, '$usuario_nombre', '$modulo', '$accion', '$descripcion', '$ip_origen')";
    } else {
        $sql = "INSERT INTO bitacora_sistema (fecha_evento, usuario_id, usuario_nombre, modulo, accion, descripcion, ip_origen)
                VALUES ('$fecha_evento', '$usuario_id', '$usuario_nombre', '$modulo', '$accion', '$descripcion', '$ip_origen')";
    }

    $conn->query($sql);
}
?>
