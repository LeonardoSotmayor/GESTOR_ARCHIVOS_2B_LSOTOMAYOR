<?php
require_once __DIR__ . '/GestorArchivos.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['archivo'])) {
    $_SESSION['mensaje'] = 'Solicitud inválida.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: listado.php');
    exit;
}

$gestor = new GestorArchivos(__DIR__ . '/uploads');

// El nombre recibido se valida por completo dentro de GestorArchivos::eliminar()
// (whitelist de formato + comprobación de ruta real) antes de tocar el disco.
$resultado = $gestor->eliminar($_POST['archivo']);

$_SESSION['mensaje'] = $resultado['mensaje'];
$_SESSION['tipo_mensaje'] = $resultado['exito'] ? 'exito' : 'error';

header('Location: listado.php');
exit;
