<?php
include("verificar_sesion.php");
include("conexion.php");
include("bitacora_funciones.php");
require __DIR__ . '/vendor/autoload.php';

use Aws\Lambda\LambdaClient;
use Aws\Exception\AwsException;

date_default_timezone_set('America/Santiago');

$fecha_hoy = date("Y-m-d");
$fecha_generacion = date("Y-m-d H:i:s");

/* 1. Total eventos de bitácora del día */
$sql_eventos = "SELECT COUNT(*) AS total 
                FROM bitacora_sistema 
                WHERE DATE(fecha_evento) = '$fecha_hoy'";
$res_eventos = $conn->query($sql_eventos);
$total_eventos = 0;

if ($res_eventos && $res_eventos->num_rows > 0) {
    $fila_eventos = $res_eventos->fetch_assoc();
    $total_eventos = (int)$fila_eventos["total"];
}

/* 2. Total cambios de estado del día */
$sql_cambios = "SELECT COUNT(*) AS total 
                FROM monitoreo 
                WHERE DATE(ultimo_chequeo) = '$fecha_hoy'";
$res_cambios = $conn->query($sql_cambios);
$total_cambios_estado = 0;

if ($res_cambios && $res_cambios->num_rows > 0) {
    $fila_cambios = $res_cambios->fetch_assoc();
    $total_cambios_estado = (int)$fila_cambios["total"];
}

/* 3. Total inactivos del día */
$sql_inactivos = "SELECT COUNT(*) AS total 
                  FROM monitoreo 
                  WHERE DATE(ultimo_chequeo) = '$fecha_hoy'
                  AND estado = 'Inactivo'";
$res_inactivos = $conn->query($sql_inactivos);
$total_inactivos = 0;

if ($res_inactivos && $res_inactivos->num_rows > 0) {
    $fila_inactivos = $res_inactivos->fetch_assoc();
    $total_inactivos = (int)$fila_inactivos["total"];
}

/* 4. Total ejecuciones automáticas */
$sql_auto = "SELECT COUNT(*) AS total 
             FROM ejecuciones_monitoreo 
             WHERE DATE(fecha_ejecucion) = '$fecha_hoy'
             AND tipo = 'Automático'";
$res_auto = $conn->query($sql_auto);
$total_ejecuciones_automaticas = 0;

if ($res_auto && $res_auto->num_rows > 0) {
    $fila_auto = $res_auto->fetch_assoc();
    $total_ejecuciones_automaticas = (int)$fila_auto["total"];
}

/* 5. Total ejecuciones manuales */
$sql_manual = "SELECT COUNT(*) AS total 
               FROM ejecuciones_monitoreo 
               WHERE DATE(fecha_ejecucion) = '$fecha_hoy'
               AND tipo = 'Manual'";
$res_manual = $conn->query($sql_manual);
$total_ejecuciones_manuales = 0;

if ($res_manual && $res_manual->num_rows > 0) {
    $fila_manual = $res_manual->fetch_assoc();
    $total_ejecuciones_manuales = (int)$fila_manual["total"];
}

/* 6. Determinar actividad principal */
$actividad_principal = "Actividad general del sistema";

if (($total_ejecuciones_automaticas + $total_ejecuciones_manuales) > 0) {
    $actividad_principal = "Monitoreo";
} elseif ($total_eventos > 0) {
    $actividad_principal = "Administración y bitácora";
}

/* 7. Generar observación general */
if ($total_inactivos > 0) {
    $observacion_general = "Se detectaron incidencias durante la jornada, con al menos un equipo en estado inactivo.";
} elseif ($total_cambios_estado > 0) {
    $observacion_general = "Se registraron cambios de estado durante el día, sin incidencias críticas persistentes.";
} else {
    $observacion_general = "La plataforma operó con normalidad durante la jornada, sin incidencias relevantes.";
}

/* 8. Guardar resumen en BD */
$sql_insert = "INSERT INTO resumenes_diarios (
    fecha_resumen,
    total_eventos,
    total_cambios_estado,
    total_inactivos,
    total_ejecuciones_automaticas,
    total_ejecuciones_manuales,
    actividad_principal,
    observacion_general,
    fecha_generacion
) VALUES (
    '$fecha_hoy',
    '$total_eventos',
    '$total_cambios_estado',
    '$total_inactivos',
    '$total_ejecuciones_automaticas',
    '$total_ejecuciones_manuales',
    '" . $conn->real_escape_string($actividad_principal) . "',
    '" . $conn->real_escape_string($observacion_general) . "',
    '$fecha_generacion'
)";

if ($conn->query($sql_insert) === TRUE) {

    /* 9. Invocar Lambda para enviar correo */
    try {
        $lambda = new LambdaClient([
            'version' => 'latest',
            'region'  => 'us-east-2'
        ]);

        $payload = [
            "fecha_resumen" => $fecha_hoy,
            "total_eventos" => $total_eventos,
            "total_cambios_estado" => $total_cambios_estado,
            "total_inactivos" => $total_inactivos,
            "total_ejecuciones_automaticas" => $total_ejecuciones_automaticas,
            "total_ejecuciones_manuales" => $total_ejecuciones_manuales,
            "actividad_principal" => $actividad_principal,
            "observacion_general" => $observacion_general
        ];

        $lambda->invoke([
            'FunctionName'   => 'enviar-resumen-diario',
            'InvocationType' => 'RequestResponse',
            'Payload'        => json_encode($payload)
        ]);

        registrarBitacora(
            $conn,
            "Resumen Diario",
            "Generar resumen",
            "Se generó el resumen diario del sistema y se envió por Lambda/SNS."
        );

        header("Location: resumenes_diarios.php?generado=1");
        exit();

    } catch (AwsException $e) {

        registrarBitacora(
            $conn,
            "Resumen Diario",
            "Generar resumen",
            "Se generó el resumen diario, pero falló la invocación de Lambda: " . $e->getMessage()
        );

        header("Location: resumenes_diarios.php?generado=1&correo_error=1");
        exit();
    }

} else {
    header("Location: resumenes_diarios.php?error=1");
    exit();
}
?>
