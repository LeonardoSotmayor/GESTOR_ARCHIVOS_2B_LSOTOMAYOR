<?php
require_once __DIR__ . '/GestorArchivos.php';

$gestor = new GestorArchivos(__DIR__ . '/uploads');
$archivos = $gestor->listar();
$totalArchivos = count($archivos);
$ultimo = $archivos[0] ?? null;

$paginaTitulo = 'Inicio — Gestor de Archivos';
$paginaActiva = 'inicio';
require __DIR__ . '/partials/header.php';
?>

<section class="hero">
    <p class="hero__eyebrow">Archivador digital</p>
    <h2>Guarda, encuentra y elimina tus documentos sin complicarte.</h2>
    <p class="hero__texto">
        Cada archivo se guarda con el título que tú le des, para que lo reconozcas
        de un vistazo. Solo se aceptan <strong>PDF, JPG y PNG</strong>, hasta 5&nbsp;MB.
    </p>
    <div class="hero__acciones">
        <a href="subir.php" class="boton boton-primario">Subir un archivo</a>
        <a href="listado.php" class="boton boton-secundario">Ver archivos guardados</a>
    </div>
</section>

<section class="tarjetas-resumen">
    <article class="tarjeta-indice">
        <span class="tarjeta-indice__numero"><?= $totalArchivos ?></span>
        <span class="tarjeta-indice__etiqueta"><?= $totalArchivos === 1 ? 'archivo guardado' : 'archivos guardados' ?></span>
    </article>

    <article class="tarjeta-indice">
        <span class="tarjeta-indice__numero">5&nbsp;MB</span>
        <span class="tarjeta-indice__etiqueta">tamaño máximo por archivo</span>
    </article>

    <article class="tarjeta-indice">
        <span class="tarjeta-indice__numero">PDF · JPG · PNG</span>
        <span class="tarjeta-indice__etiqueta">tipos aceptados</span>
    </article>
</section>

<?php if ($ultimo): ?>
<section class="tarjeta tarjeta--ultimo">
    <h3>Último archivo guardado</h3>
    <div class="ficha-ultimo">
        <span class="ficha-ultimo__sello"><?= htmlspecialchars($ultimo['extension']) ?></span>
        <div>
            <p class="ficha-ultimo__titulo"><?= htmlspecialchars($ultimo['titulo']) ?></p>
            <p class="ficha-ultimo__meta"><?= htmlspecialchars($ultimo['tamano']) ?> · subido el <?= htmlspecialchars($ultimo['fecha']) ?></p>
        </div>
        <a href="listado.php" class="boton boton-secundario boton-pequeno">Ver todos</a>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
