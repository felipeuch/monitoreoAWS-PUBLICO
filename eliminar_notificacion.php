<?php
include("verificar_sesion.php");
include("conexion.php");

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["ok" => false, "error" => "Método no permitido"]);
    exit;
}

$id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

if ($id <= 0) {
    echo json_encode(["ok" => false, "error" => "Notificación inválida"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM notificaciones WHERE id = ?");

if (!$stmt) {
    echo json_encode(["ok" => false, "error" => "No se pudo preparar la eliminación"]);
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["ok" => true]);
} else {
    echo json_encode(["ok" => false, "error" => "No se pudo eliminar la notificación"]);
}
?>
