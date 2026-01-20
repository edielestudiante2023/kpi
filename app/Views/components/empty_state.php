<?php
/**
 * Componente reutilizable para estados vacios (sin datos).
 *
 * Uso:
 * <?= view('components/empty_state', [
 *     'icon' => 'bi-inbox',                    // Icono Bootstrap Icons (opcional)
 *     'title' => 'No hay datos',               // Titulo (opcional)
 *     'message' => 'No se encontraron...',     // Mensaje descriptivo (opcional)
 *     'actionUrl' => '/crear',                 // URL de accion (opcional)
 *     'actionText' => 'Crear nuevo',           // Texto del boton de accion (opcional)
 *     'actionIcon' => 'bi-plus-lg',            // Icono del boton (opcional)
 *     'actionClass' => 'btn-primary',          // Clase del boton (opcional)
 * ]) ?>
 */

$icon = $icon ?? 'bi-inbox';
$title = $title ?? 'Sin datos';
$message = $message ?? 'No hay elementos para mostrar.';
$actionUrl = $actionUrl ?? '';
$actionText = $actionText ?? '';
$actionIcon = $actionIcon ?? 'bi-plus-lg';
$actionClass = $actionClass ?? 'btn-primary';
?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi <?= esc($icon) ?> text-muted" style="font-size: 4rem;"></i>
    </div>
    <h5 class="text-muted mb-2"><?= esc($title) ?></h5>
    <p class="text-muted mb-4"><?= esc($message) ?></p>
    <?php if ($actionUrl && $actionText): ?>
        <a href="<?= esc($actionUrl) ?>" class="btn <?= esc($actionClass) ?>">
            <i class="bi <?= esc($actionIcon) ?> me-1"></i><?= esc($actionText) ?>
        </a>
    <?php endif; ?>
</div>
