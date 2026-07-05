<?php
require_once __DIR__ . '/GestorArchivos.php';

session_start();

$gestor = new GestorArchivos(__DIR__ . '/uploads');
$errorFormulario = null;
$tituloIngresado = '';

// Procesamiento de la subida (misma página que muestra el formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tituloIngresado = trim($_POST['titulo'] ?? '');

    if (!isset($_FILES['archivo'])) {
        $errorFormulario = 'Debes seleccionar un archivo.';
    } else {
        $resultado = $gestor->subir($_FILES['archivo'], $tituloIngresado);

        if ($resultado['exito']) {
            // Éxito: redirigimos al listado para que el usuario vea el archivo ya guardado
            $_SESSION['mensaje'] = $resultado['mensaje'];
            $_SESSION['tipo_mensaje'] = 'exito';
            header('Location: listado.php');
            exit;
        }

        // Si falla, nos quedamos en esta misma página mostrando el error
        $errorFormulario = $resultado['mensaje'];
    }
}

$paginaTitulo = 'Subir archivo — Gestor de Archivos';
$paginaActiva = 'subir';
require __DIR__ . '/partials/header.php';
?>

<section class="tarjeta tarjeta--formulario">
    <h2>Subir un nuevo archivo</h2>
    <p class="ayuda">Ponle un título para reconocerlo fácilmente en tu listado. Formatos permitidos: PDF, JPG, PNG — tamaño máximo 5&nbsp;MB.</p>

    <?php if ($errorFormulario): ?>
        <div class="alerta alerta-error" role="alert">
            <span class="alerta__icono" aria-hidden="true">⚠</span>
            <?= htmlspecialchars($errorFormulario) ?>
        </div>
    <?php endif; ?>

    <form action="subir.php" method="POST" enctype="multipart/form-data" class="formulario-subida">
        <div class="campo">
            <label for="titulo">Título del archivo</label>
            <input
                type="text"
                id="titulo"
                name="titulo"
                maxlength="100"
                placeholder="Ej. Cédula escaneada, Foto del recibo, Contrato firmado..."
                value="<?= htmlspecialchars($tituloIngresado) ?>"
                required>
            <p class="campo__ayuda">Así aparecerá en tu listado de archivos.</p>
        </div>

        <div class="campo">
            <label for="archivo">Archivo (PDF, JPG o PNG)</label>
            <input type="file" id="archivo" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>

        <button type="submit" class="boton boton-primario">Guardar archivo</button>
    </form>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
