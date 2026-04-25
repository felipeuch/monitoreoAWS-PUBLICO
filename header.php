<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_actual = basename($_SERVER["PHP_SELF"]);

function claseMenuActiva($archivo, $pagina_actual) {
    return ($archivo === $pagina_actual) ? ' class="active"' : '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Monitoring</title>
    <link rel="stylesheet" href="estilos.css">
    <?php if (isset($extra_css)) { ?>
       <link rel="stylesheet" href="<?php echo $extra_css; ?>">
    <?php } ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<header>
    <div class="container">
        <div class="nav">
            <div class="brand">
                <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="64" height="64" rx="16" fill="#2563EB"/>
                    <path d="M18 40L28 30L36 36L48 22" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="18" cy="40" r="3" fill="white"/>
                    <circle cx="28" cy="30" r="3" fill="white"/>
                    <circle cx="36" cy="36" r="3" fill="white"/>
                    <circle cx="48" cy="22" r="3" fill="white"/>
                </svg>

                <div class="brand-text">
                    <h1>Cloud Monitoring</h1>
                    <p>Monitoreo web inteligente para AWS</p>
                </div>
            </div>

            <div class="menu">
                <a href="index.php"<?php echo claseMenuActiva("index.php", $pagina_actual); ?>>Inicio</a>
                <a href="agregar_equipo.php"<?php echo claseMenuActiva("agregar_equipo.php", $pagina_actual); ?>>Agregar equipo</a>
                <a href="listar_equipos.php"<?php echo claseMenuActiva("listar_equipos.php", $pagina_actual); ?>>Equipos</a>
                <a href="descubrir_equipos.php"<?php echo claseMenuActiva("descubrir_equipos.php", $pagina_actual); ?>>Descubrir</a>
                <a href="ver_monitoreo.php"<?php echo claseMenuActiva("ver_monitoreo.php", $pagina_actual); ?>>Monitoreo</a>
                <a href="bitacora.php"<?php echo claseMenuActiva("bitacora.php", $pagina_actual); ?>>Bitácora</a>
                <a href="monitorear.php"<?php echo claseMenuActiva("monitorear.php", $pagina_actual); ?>>Revisión</a>
                <a href="resumenes_diarios.php"<?php echo claseMenuActiva("resumenes_diarios.php", $pagina_actual); ?>>Resumen diario</a>
                <a href="logout.php" class="btn btn-secondary btn-logout">Salir</a>
            </div>
        </div>
    </div>
</header>

<main>
    <div class="container">
