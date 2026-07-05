<?php
// Este partial espera que la página que lo incluye ya haya definido:
//   $paginaTitulo  (string) título de la pestaña del navegador
//   $paginaActiva  (string) 'inicio' | 'subir' | 'listado'
$paginaActiva = $paginaActiva ?? 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($paginaTitulo ?? 'Gestor de Archivos') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="cabinet">

    <header class="site-header">
        <div class="site-header__marca">
            <span class="site-header__icono" aria-hidden="true">🗂</span>
            <div>
                <h1>Gestor de Archivos</h1>
                <p>Subida, consulta y eliminación segura de documentos</p>
            </div>
        </div>
    </header>

    <nav class="folder-tabs" aria-label="Secciones">
        <a href="index.php"    class="folder-tab <?= $paginaActiva === 'inicio'   ? 'folder-tab--activa' : '' ?>">Inicio</a>
        <a href="subir.php"    class="folder-tab <?= $paginaActiva === 'subir'    ? 'folder-tab--activa' : '' ?>">Subir archivo</a>
        <a href="listado.php"  class="folder-tab <?= $paginaActiva === 'listado'  ? 'folder-tab--activa' : '' ?>">Ver archivos</a>
    </nav>

    <main class="folder-panel">
