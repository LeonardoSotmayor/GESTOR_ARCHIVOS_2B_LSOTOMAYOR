<?php
// Espera $mensaje y $tipoMensaje ya definidos por la página que lo incluye.
if (!empty($mensaje)):
?>
    <div class="alerta alerta-<?= htmlspecialchars($tipoMensaje) ?>" role="status">
        <span class="alerta__icono" aria-hidden="true"><?= $tipoMensaje === 'exito' ? '✓' : '⚠' ?></span>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>
