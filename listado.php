<?php
require_once __DIR__ . '/GestorArchivos.php';

session_start();

$gestor = new GestorArchivos(__DIR__ . '/uploads');
$archivos = $gestor->listar();

$mensaje = $_SESSION['mensaje'] ?? null;
$tipoMensaje = $_SESSION['tipo_mensaje'] ?? null;
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

$paginaTitulo = 'Ver archivos — Gestor de Archivos';
$paginaActiva = 'listado';
require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/mensaje.php';
?>

<section class="tarjeta">
    <div class="tarjeta__encabezado">
        <h2>Archivos guardados</h2>
        <a href="subir.php" class="boton boton-primario boton-pequeno">+ Subir archivo</a>
    </div>

    <?php if (empty($archivos)): ?>
        <div class="estado-vacio">
            <p class="estado-vacio__icono" aria-hidden="true">🗃️</p>
            <p class="estado-vacio__titulo">Todavía no hay archivos guardados</p>
            <p class="ayuda">Sube tu primer PDF, JPG o PNG y aparecerá aquí con el título que le des.</p>
            <a href="subir.php" class="boton boton-primario">Subir el primer archivo</a>
        </div>
    <?php else: ?>
        <div class="lista-fichas">
            <?php foreach ($archivos as $archivo): ?>
                <article class="ficha">
                    <span class="ficha__sello"><?= htmlspecialchars($archivo['extension']) ?></span>

                    <div class="ficha__info">
                        <p class="ficha__titulo"><?= htmlspecialchars($archivo['titulo']) ?></p>
                        <p class="ficha__meta">
                            <?= htmlspecialchars($archivo['tamano']) ?>
                            &nbsp;·&nbsp;
                            subido el <?= htmlspecialchars($archivo['fecha']) ?>
                        </p>
                    </div>

                    <div class="ficha__acciones">
                        <a class="boton boton-secundario boton-pequeno"
                           href="descargar.php?archivo=<?= urlencode($archivo['nombre_sistema']) ?>">
                            Descargar
                        </a>
                        <form action="eliminar.php" method="POST"
                              onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($archivo['titulo'])) ?>»? Esta acción no se puede deshacer.');">
                            <input type="hidden" name="archivo" value="<?= htmlspecialchars($archivo['nombre_sistema']) ?>">
                            <button type="submit" class="boton boton-peligro boton-pequeno">Eliminar</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
