<?php
session_start();
include("conexion.php");
include("bitacora_funciones.php");

if (isset($_SESSION["usuario_nombre"])) {
    registrarBitacora($conn, "Autenticación", "Cierre de sesión", "Cierre de sesión del usuario " . $_SESSION["usuario_nombre"]);
}

session_unset();
session_destroy();

header("Location: login.php");
exit();
?>
