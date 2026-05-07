<?php
include("verificar_sesion.php");
include("sns_alerta.php");

$ok = enviarAlertaSNS(
    "Prueba de alerta FELIPE",
    "Esta es una prueba de notificación enviada desde la plataforma FELIPE Cloud Monitoring."
);

if ($ok) {
    echo "Alerta enviada correctamente.";
} else {
    echo "Error al enviar la alerta.";
}
?>
