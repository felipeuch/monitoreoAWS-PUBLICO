<?php
function registrarNotificacion($conn, $tipo, $titulo, $mensaje, $nivel = "Info", $modulo = null, $referencia_id = null) {
    $stmt = $conn->prepare("
        INSERT INTO notificaciones
        (tipo, titulo, mensaje, nivel, modulo, referencia_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Error al preparar notificación: " . $conn->error);
        return false;
    }

    $stmt->bind_param(
        "sssssi",
        $tipo,
        $titulo,
        $mensaje,
        $nivel,
        $modulo,
        $referencia_id
    );

    return $stmt->execute();
}
?>