<?php
require_once __DIR__ . '/GestorArchivos.php';

$gestor = new GestorArchivos(__DIR__ . '/uploads');

$nombre = $_GET['archivo'] ?? '';
$ruta = $gestor->rutaDescarga($nombre);

if ($ruta === null) {
    http_response_code(404);
    echo 'Archivo no encontrado o nombre inválido.';
    exit;
}

// Servimos el archivo forzando la descarga, con Content-Type genérico,
// para que el navegador nunca intente "ejecutar" o interpretar el archivo.
$nombreDescarga = $gestor->nombreDescarga($nombre);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');

readfile($ruta);
exit;
