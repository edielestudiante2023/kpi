<?php
/**
 * Componente reutilizable para alertas/mensajes.
 *
 * Uso:
 * <?= view('components/alert', ['type' => 'success', 'message' => 'Guardado']) ?>
 *
 * O para múltiples errores:
 * <?= view('components/alert', ['type' => 'danger', 'messages' => ['Error 1', 'Error 2']]) ?>
 *
 * Tipos: success, danger, warning, info
 */

$type = $type ?? 'info';
$dismissible = $dismissible ?? true;
$autoDismiss = $autoDismiss ?? ($type === 'success' ? 5000 : 0);

$icons = [
    'success' => 'bi-check-circle-fill',
    'danger'  => 'bi-exclamation-circle-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'info'    => 'bi-info-circle-fill'
];

$titles = [
    'success' => 'Exito',
    'danger'  => 'Error',
    'warning' => 'Advertencia',
    'info'    => 'Informacion'
];

$icon = $icons[$type] ?? 'bi-info-circle-fill';
$title = $titles[$type] ?? 'Info';
$alertId = 'alert-' . uniqid();
?>

<div id="<?= $alertId ?>" class="alert alert-<?= esc($type) ?> <?= $dismissible ? 'alert-dismissible' : '' ?> fade show d-flex align-items-start" role="alert">
    <i class="bi <?= $icon ?> me-2 mt-1"></i>
    <div class="flex-grow-1">
        <?php if (isset($message)): ?>
            <?= esc($message) ?>
        <?php elseif (isset($messages) && is_array($messages)): ?>
            <?php if (count($messages) === 1): ?>
                <?= esc($messages[0]) ?>
            <?php else: ?>
                <ul class="mb-0 ps-3">
                    <?php foreach ($messages as $msg): ?>
                        <li><?= esc($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if ($dismissible): ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    <?php endif; ?>
</div>

<?php if ($autoDismiss > 0): ?>
<script>
setTimeout(function() {
    var alert = document.getElementById('<?= $alertId ?>');
    if (alert) {
        alert.classList.remove('show');
        setTimeout(function() { alert.remove(); }, 150);
    }
}, <?= $autoDismiss ?>);
</script>
<?php endif; ?>
