<?php
session_start();
include("conexion.php");
include("bitacora_funciones.php");

if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST["usuario"]);
    $clave = $_POST["clave"];

    $usuario_seguro = $conn->real_escape_string($usuario);

    $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario_seguro' LIMIT 1";
    $resultado = $conn->query($sql);

    if ($resultado && $resultado->num_rows == 1) {
        $fila = $resultado->fetch_assoc();

        if (password_verify($clave, $fila["clave"])) {
            $_SESSION["usuario_id"] = $fila["id"];
            $_SESSION["usuario_nombre"] = $fila["nombre"];
            $_SESSION["usuario_login"] = $fila["usuario"];
             
            registrarBitacora($conn, "Autenticación", "Inicio de sesión", "Inicio de sesión correcto del usuario " . $fila["usuario"]);            

            header("Location: index.php");
            exit();
        } else {
            $mensaje = "Contraseña incorrecta.";
            registrarBitacora($conn, "Autenticación", "Login fallido", "Intento de inicio de sesión fallido para el usuario " . $usuario);
        }
    } else {
        $mensaje = "Usuario no encontrado.";
        registrarBitacora($conn, "Autenticación", "Login fallido", "Intento de inicio de sesión con usuario no registrado: " . $usuario);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso al sistema</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="login-page">
    <div class="login-wrapper">

        <div class="login-brand-panel">
            <div>
                <div class="login-logo">
                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="loginLogoGradient" x1="0" y1="0" x2="100" y2="100">
                                <stop offset="0%" stop-color="#2563eb"/>
                                <stop offset="100%" stop-color="#38bdf8"/>
                            </linearGradient>
                        </defs>
                        <rect x="10" y="10" width="80" height="80" rx="22" fill="url(#loginLogoGradient)"/>
                        <path d="M28 60L42 46L52 56L72 36" stroke="white" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="72" cy="36" r="5" fill="white"/>
                    </svg>

                    <div>
                        <h1>Cloud Monitoring</h1>
                        <p>Cloud Monitoring para entornos educacionales</p>
                    </div>
                </div>

                <h2 class="login-hero-title">
                    Acceso seguro al <span>panel de administración</span>
                </h2>

                <p class="login-hero-text">
                    Ingresa a la plataforma centralizada de monitoreo para gestionar inventario,
                    revisar el estado operativo de los equipos registrados y consultar el historial
                    de ejecución del sistema en la infraestructura desplegada sobre AWS.
                </p>

                <div class="login-feature-list">
                    <div class="login-feature-item">
                        <strong>Gestión centralizada</strong>
                        <span>Administra el inventario y la supervisión de equipos desde un único panel web.</span>
                    </div>

                    <div class="login-feature-item">
                        <strong>Base escalable</strong>
                        <span>Arquitectura preparada para crecer con más módulos, alertas e integraciones futuras.</span>
                    </div>

                    <div class="login-feature-item">
                        <strong>Visión institucional</strong>
                        <span>Solución diseñada para apoyar la supervisión tecnológica en contextos educacionales.</span>
                    </div>
                </div>
            </div>

            <div class="login-footer-note">
                © 2026 Cloud Monitoring · Plataforma desarrollada en AWS
            </div>
        </div>

        <div class="login-card">
            <div class="login-card-header">
                <h2>Ingreso de administrador</h2>
                <p>Accede con tus credenciales para entrar al sistema de monitoreo.</p>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="message" style="background: rgba(239,68,68,0.12); color:#fecaca; border:1px solid rgba(239,68,68,0.25);">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <label>Usuario</label>
                <input type="text" name="usuario" required>

                <label>Contraseña</label>
                <input type="password" name="clave" required>

                <button type="submit" class="btn btn-primary">Ingresar al sistema</button>
            </form>

            <div class="login-help-box">
                <p>
                    Este acceso está destinado al administrador de la plataforma. Desde aquí podrás
                    gestionar equipos, ejecutar revisiones de monitoreo y consultar los registros operativos.
                </p>
            </div>
        </div>

    </div>
</div>
</body>
</html>
